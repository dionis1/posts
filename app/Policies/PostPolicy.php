<?php

namespace App\Policies;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Post $post): bool
    {
        return $post->user_id === $user->id || $user->role->canReview();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Post $post): bool
    {
        if (in_array($post->status, [PostStatus::Published, PostStatus::Scheduled], true)) {
            return false;
        }

        return $user->role->canEditOthers()
            || ($post->user_id === $user->id && $post->status === PostStatus::Draft);
    }

    public function submit(User $user, Post $post): bool
    {
        return $this->update($user, $post) && $post->status === PostStatus::Draft;
    }

    public function approve(User $user, Post $post): bool
    {
        // An administrator is deliberately subject to separation of duties, too.
        return $user->id !== $post->user_id && $user->role->canReview() && $post->status === PostStatus::Review;
    }

    public function reject(User $user, Post $post): bool
    {
        return $this->approve($user, $post);
    }

    public function schedule(User $user, Post $post): bool
    {
        return $user->role->canEditOthers() && $post->status === PostStatus::Approved;
    }

    public function publish(User $user, Post $post): bool
    {
        return $this->schedule($user, $post);
    }

    public function archive(User $user, Post $post): bool
    {
        return $user->role->canEditOthers()
            && in_array($post->status, [PostStatus::Approved, PostStatus::Scheduled, PostStatus::Published], true);
    }

    public function reopen(User $user, Post $post): bool
    {
        return $user->role->canEditOthers()
            && in_array($post->status, [PostStatus::Approved, PostStatus::Scheduled, PostStatus::Published, PostStatus::Archived], true);
    }

    public function restore(User $user, Post $post): bool
    {
        return $this->update($user, $post);
    }

    public function transition(User $user, Post $post, string $target): bool
    {
        $status = PostStatus::tryFrom($target);
        if (! $status || ! in_array($status, $post->status->next(), true)) {
            return false;
        }

        return match ($status) {
            PostStatus::Review => $this->submit($user, $post),
            PostStatus::Approved => $this->approve($user, $post),
            PostStatus::Scheduled => $this->schedule($user, $post),
            PostStatus::Published => $this->publish($user, $post),
            PostStatus::Archived => $this->archive($user, $post),
            PostStatus::Draft => $post->status === PostStatus::Review
                ? $this->reject($user, $post)
                : $this->reopen($user, $post),
        };
    }

    public function delete(User $user, Post $post): bool
    {
        return in_array($post->status, [PostStatus::Draft, PostStatus::Archived], true)
            && ($user->role->canEditOthers() || $post->user_id === $user->id);
    }
}
