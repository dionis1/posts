<?php

namespace App\Actions\Posts;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class UpdatePostAction extends PostAction
{
    public function __invoke(User $actor, Post $post, array $input, int $version): Post
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
}
