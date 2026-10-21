<?php

namespace App\Actions\Posts;

use App\Enums\MediaStatus;
use App\Enums\Role;
use App\Models\Activity;
use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use App\Notifications\WorkflowChanged;
use App\Support\PostContentRules;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

abstract class PostAction
{
    public const CONTENT_FIELDS = ['title', 'body', 'slug', 'excerpt', 'category_id', 'cover_media_id', 'seo_title', 'seo_description', 'canonical_url', 'locale'];

    protected function validateContent(User $actor, array $input, ?Post $post = null): array
    {
        $current = $post ? Arr::only($post->getAttributes(), self::CONTENT_FIELDS) : ['locale' => 'en'];
        if ($post) {
            $current['tags'] = $post->tags()->pluck('tags.id')->all();
        }
        $input = array_merge($current, Arr::only($input, [...self::CONTENT_FIELDS, 'tags']));
        $input['slug'] = $input['slug'] ?? Str::limit(Str::slug($input['title'] ?? 'post') ?: 'post', 160, '').'-'.Str::lower(Str::random(6));
        $data = Validator::make($input, PostContentRules::for($post?->id))->validate();
        if (! empty($data['cover_media_id'])) {
            $media = Media::find($data['cover_media_id']);
            if (! $media || $media->status !== MediaStatus::Ready || ($media->user_id !== $actor->id && ! $actor->role->canEditOthers())) {
                throw ValidationException::withMessages(['cover_media_id' => __('Choose a processed image you are allowed to use.')]);
            }
        }

        return $data;
    }

    protected function locked(Post $post, int $version): Post
    {
        $post = Post::query()->lockForUpdate()->findOrFail($post->id);
        if ($post->lock_version !== $version) {
            $this->conflict();
        }

        return $post;
    }

    protected function saveVersion(Post $post, array $attributes, int $version): void
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

    protected function conflict(): never
    {
        throw ValidationException::withMessages(['lock_version' => __('Someone changed this post. Reload the latest version before saving.')]);
    }

    protected function record(Post $post, ?User $actor, string $event, array $properties = []): void
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

    protected function notify(Post $post, ?User $actor, string $event, ?string $feedback = null): void
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
