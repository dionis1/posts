<?php

namespace App\Actions\Posts;

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class DeletePostAction extends PostAction
{
    public function __invoke(User $actor, Post $post, int $version): void
    {
        DB::transaction(function () use ($actor, $post, $version) {
            $post = $this->locked($post, $version);
            Gate::forUser($actor)->authorize('delete', $post);
            $this->saveVersion($post, [], $version);
            $this->record($post, $actor, 'post.deleted');
            $post->delete();
        }, 3);
    }
}
