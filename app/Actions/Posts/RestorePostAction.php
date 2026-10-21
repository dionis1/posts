<?php

namespace App\Actions\Posts;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\PostRevision;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class RestorePostAction extends PostAction
{
    public function __invoke(User $actor, Post $post, PostRevision $revision, int $version): Post
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
}
