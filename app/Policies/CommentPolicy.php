<?php

namespace App\Policies;

use App\Enums\PostStatus;
use App\Enums\Role;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;

class CommentPolicy
{
    public function create(User $user, Post $post): bool
    {
        return $user->hasVerifiedEmail() && $post->status === PostStatus::Published && $post->published_at?->isPast();
    }

    public function viewAny(User $user): bool
    {
        return in_array($user->role, [Role::Admin, Role::Editor, Role::Reviewer], true);
    }

    public function moderate(User $user, Comment $comment): bool
    {
        return $this->viewAny($user);
    }
}
