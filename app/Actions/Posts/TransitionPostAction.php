<?php

namespace App\Actions\Posts;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class TransitionPostAction extends PostAction
{
    public function __invoke(User $actor, Post $post, string $target, int $version, ?string $feedback = null, ?string $scheduledAt = null): Post
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
}
