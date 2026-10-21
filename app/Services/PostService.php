<?php

namespace App\Services;

use App\Actions\Posts\CreatePostAction;
use App\Actions\Posts\DeletePostAction;
use App\Actions\Posts\PublishScheduledPostAction;
use App\Actions\Posts\RestorePostAction;
use App\Actions\Posts\TransitionPostAction;
use App\Actions\Posts\UpdatePostAction;
use App\Models\Post;
use App\Models\PostRevision;
use App\Models\User;

/** Convenience facade over independently injectable publishing actions. */
final class PostService
{
    public function __construct(
        private CreatePostAction $createAction,
        private UpdatePostAction $updateAction,
        private TransitionPostAction $transitionAction,
        private RestorePostAction $restoreAction,
        private DeletePostAction $deleteAction,
        private PublishScheduledPostAction $publishScheduledAction,
    ) {}

    public function create(User $actor, array $input): Post
    {
        return ($this->createAction)($actor, $input);
    }

    public function update(User $actor, Post $post, array $input, int $version): Post
    {
        return ($this->updateAction)($actor, $post, $input, $version);
    }

    public function transition(User $actor, Post $post, string $target, int $version, ?string $feedback = null, ?string $scheduledAt = null): Post
    {
        return ($this->transitionAction)($actor, $post, $target, $version, $feedback, $scheduledAt);
    }

    public function restore(User $actor, Post $post, PostRevision $revision, int $version): Post
    {
        return ($this->restoreAction)($actor, $post, $revision, $version);
    }

    public function delete(User $actor, Post $post, int $version): void
    {
        ($this->deleteAction)($actor, $post, $version);
    }

    public function publishScheduled(int $postId, int $version): ?Post
    {
        return ($this->publishScheduledAction)($postId, $version);
    }
}
