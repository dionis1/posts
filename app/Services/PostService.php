<?php

namespace App\Services;

use App\Enums\PostStatus;
use App\Enums\Role;
use App\Models\Activity;
use App\Models\Media;
use App\Models\Post;
use App\Models\PostRevision;
use App\Models\User;
use App\Notifications\WorkflowChanged;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PostService
{
    public const CONTENT_FIELDS = ['title', 'body', 'slug', 'excerpt', 'category_id', 'cover_media_id', 'seo_title', 'seo_description', 'canonical_url', 'locale'];

    public function create(User $actor, array $input): Post
    {
        Gate::forUser($actor)->authorize('create', Post::class);

        return DB::transaction(function () use ($actor, $input) {
            $data = $this->validateContent($actor, $input);
            $post = new Post(Arr::except($data, 'tags'));
            $post->forceFill(['user_id' => $actor->id, 'status' => PostStatus::Draft, 'lock_version' => 1])->save();
            $post->tags()->sync($data['tags'] ?? []);
            $this->record($post, $actor, 'post.created');

            return $post->refresh();
        }, 3);
    }

    public function update(User $actor, Post $post, array $input, int $version): Post
    {
        return DB::transaction(function () use ($actor, $post, $input, $version) {
            $post = $this->locked($post, $version);
            Gate::forUser($actor)->authorize('update', $post);
            $data = $this->validateContent($actor, $input, $post);
            $oldStatus = $post->status->value;
            $this->saveVersion($post, array_merge(Arr::except($data, 'tags'), [
                'status' => PostStatus::Draft,
                'scheduled_at' => null,
                'published_at' => null,
            ]), $version);
            $post->tags()->sync($data['tags'] ?? []);
            $this->record($post, $actor, 'post.updated', ['previous_status' => $oldStatus]);

            return $post->refresh();
        }, 3);
    }

    public function transition(User $actor, Post $post, string $target, int $version, ?string $feedback = null, ?string $scheduledAt = null): Post
    {
        return DB::transaction(function () use ($actor, $post, $target, $version, $feedback, $scheduledAt) {
            $post = $this->locked($post, $version);
            $status = PostStatus::tryFrom($target);
            if (! $status || ! in_array($status, $post->status->next(), true)) {
                throw ValidationException::withMessages(['status' => __('This workflow transition is not allowed.')]);
            }
            Gate::forUser($actor)->authorize('transition', [$post, $target]);
            $from = $post->status;
            $rejecting = $from === PostStatus::Review && $status === PostStatus::Draft;
            Validator::make(['feedback' => $feedback, 'scheduled_at' => $scheduledAt], [
                'feedback' => [$rejecting ? 'required' : 'nullable', 'string', 'max:2000'],
                'scheduled_at' => [$status === PostStatus::Scheduled ? 'required' : 'nullable', 'date'],
            ])->validate();
            $scheduled = $status === PostStatus::Scheduled ? CarbonImmutable::parse($scheduledAt, 'UTC')->utc() : null;
            if ($scheduled && $scheduled->lessThanOrEqualTo(now())) {
                throw ValidationException::withMessages(['scheduled_at' => __('Choose a future publishing time (UTC).')]);
            }
            $event = match ($status) {
                PostStatus::Review => 'post.submitted',
                PostStatus::Approved => 'post.approved',
                PostStatus::Scheduled => 'post.scheduled',
                PostStatus::Published => 'post.published',
                PostStatus::Archived => 'post.archived',
                PostStatus::Draft => $rejecting ? 'post.rejected' : 'post.reopened',
            };
            $this->saveVersion($post, [
                'status' => $status,
                'scheduled_at' => $scheduled,
                'published_at' => $status === PostStatus::Published ? now() : null,
            ], $version);
            $this->record($post, $actor, $event, ['from' => $from->value, 'to' => $status->value, 'feedback' => $feedback]);
            $this->notify($post, $actor, $event, $feedback);

            return $post->refresh();
        }, 3);
    }

    public function restore(User $actor, Post $post, PostRevision $revision, int $version): Post
    {
        return DB::transaction(function () use ($actor, $post, $revision, $version) {
            $post = $this->locked($post, $version);
            Gate::forUser($actor)->authorize('restore', $post);
            abort_unless($revision->post_id === $post->id, 404);
            $data = $this->validateContent($actor, Arr::only($revision->content, [...self::CONTENT_FIELDS, 'tags']), $post);
            $this->saveVersion($post, array_merge(Arr::except($data, 'tags'), [
                'status' => PostStatus::Draft, 'scheduled_at' => null, 'published_at' => null,
            ]), $version);
            $post->tags()->sync($data['tags'] ?? []);
            $this->record($post, $actor, 'post.restored', ['restored_revision_id' => $revision->id, 'restored_version' => $revision->version]);

            return $post->refresh();
        }, 3);
    }

    public function delete(User $actor, Post $post, int $version): void
    {
        DB::transaction(function () use ($actor, $post, $version) {
            $post = $this->locked($post, $version);
            Gate::forUser($actor)->authorize('delete', $post);
            $this->saveVersion($post, [], $version);
            $this->record($post, $actor, 'post.deleted');
            $post->delete();
        }, 3);
    }

    /** A stale, duplicate, early, or cancelled scheduled job safely does nothing. */
    public function publishScheduled(int $postId, int $version): ?Post
    {
        return DB::transaction(function () use ($postId, $version) {
            $post = Post::query()->lockForUpdate()->find($postId);
            if (! $post || $post->status !== PostStatus::Scheduled || $post->lock_version !== $version || ! $post->scheduled_at || $post->scheduled_at->isFuture()) {
                return null;
            }
            $this->saveVersion($post, [
                'status' => PostStatus::Published, 'published_at' => now(), 'scheduled_at' => null,
            ], $version);
            $this->record($post, null, 'post.published', ['source' => 'scheduler']);
            $this->notify($post, null, 'post.published');

            return $post;
        }, 3);
    }

    private function validateContent(User $actor, array $input, ?Post $post = null): array
    {
        $current = $post ? Arr::only($post->getAttributes(), self::CONTENT_FIELDS) : ['locale' => 'en'];
        if ($post) {
            $current['tags'] = $post->tags()->pluck('tags.id')->all();
        }
        $input = array_merge($current, Arr::only($input, [...self::CONTENT_FIELDS, 'tags']));
        $input['slug'] = $input['slug'] ?? Str::limit(Str::slug($input['title'] ?? 'post') ?: 'post', 160, '').'-'.Str::lower(Str::random(6));
        $data = Validator::make($input, [
            'title' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:100000'],
            'slug' => ['required', 'string', 'max:200', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('posts', 'slug')->ignore($post?->id)],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'cover_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'seo_title' => ['nullable', 'string', 'max:60'],
            'seo_description' => ['nullable', 'string', 'max:160'],
            'canonical_url' => ['nullable', 'url:http,https', 'max:2048'],
            'locale' => ['required', Rule::in(['en', 'de'])],
            'tags' => ['sometimes', 'array', 'max:20'],
            'tags.*' => ['integer', 'distinct', 'exists:tags,id'],
        ])->validate();
        if (! empty($data['cover_media_id'])) {
            $media = Media::find($data['cover_media_id']);
            if (! $media || $media->status !== 'ready' || ($media->user_id !== $actor->id && ! $actor->role->canEditOthers())) {
                throw ValidationException::withMessages(['cover_media_id' => __('Choose a processed image you are allowed to use.')]);
            }
        }

        return $data;
    }

    private function locked(Post $post, int $version): Post
    {
        $post = Post::query()->lockForUpdate()->findOrFail($post->id);
        if ($post->lock_version !== $version) {
            $this->conflict();
        }

        return $post;
    }

    private function saveVersion(Post $post, array $attributes, int $version): void
    {
        $post->forceFill($attributes);
        $changed = Post::query()->whereKey($post->id)->where('lock_version', $version)->update(array_merge($post->getDirty(), [
            'lock_version' => $version + 1,
            'updated_at' => now(),
        ]));
        if ($changed !== 1) {
            $this->conflict();
        }
        $post->refresh();
    }

    private function conflict(): never
    {
        throw ValidationException::withMessages(['lock_version' => __('Someone changed this post. Reload the latest version before saving.')]);
    }

    private function record(Post $post, ?User $actor, string $event, array $properties = []): void
    {
        $snapshot = Arr::only($post->getAttributes(), self::CONTENT_FIELDS);
        $snapshot['tags'] = $post->tags()->orderBy('tags.id')->pluck('tags.id')->all();
        $snapshot['status'] = $post->status->value;
        $post->revisions()->create(['user_id' => $actor?->id, 'version' => $post->lock_version, 'content' => $snapshot]);
        Activity::create([
            'post_id' => $post->id, 'actor_id' => $actor?->id, 'event' => $event,
            'properties' => array_merge($properties, ['version' => $post->lock_version]),
        ]);
    }

    private function notify(Post $post, ?User $actor, string $event, ?string $feedback = null): void
    {
        $recipients = User::query()->where(function ($query) use ($post, $event) {
            $query->whereKey($post->user_id);
            if ($event === 'post.submitted') {
                $query->orWhereIn('role', [Role::Admin->value, Role::Editor->value, Role::Reviewer->value]);
            }
        })->when($actor, fn ($query) => $query->where('id', '!=', $actor->id))->get();
        Notification::send($recipients, new WorkflowChanged($post->id, $post->title, $event, $feedback));
    }
}
