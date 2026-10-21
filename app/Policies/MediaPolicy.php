<?php

namespace App\Policies;

use App\Enums\MediaStatus;
use App\Enums\Role;
use App\Models\Media;
use App\Models\Post;
use App\Models\User;

class MediaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role !== Role::Reviewer;
    }

    public function view(?User $user, Media $media): bool
    {
        if ($media->status !== MediaStatus::Ready) {
            return false;
        }

        return ($user && ($user->id === $media->user_id || in_array($user->role, [Role::Admin, Role::Editor], true)))
            || Post::published()->where('cover_media_id', $media->id)->exists();
    }

    public function delete(User $user, Media $media): bool
    {
        return $user->id === $media->user_id || in_array($user->role, [Role::Admin, Role::Editor], true);
    }
}
