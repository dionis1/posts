<?php

namespace App\Actions\Posts;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class CreatePostAction extends PostAction
{
    public function __invoke(User $actor, array $input): Post
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
}
