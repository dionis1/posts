<?php

namespace App\Actions\Posts;

use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\Support\Facades\DB;

final class PublishScheduledPostAction extends PostAction
{
    public function __invoke(int $postId, int $version): ?Post
    {
        return DB::transaction(function () use ($postId, $version) {
            $post = Post::query()->lockForUpdate()->find($postId);
            if (! $post || $post->status !== PostStatus::Scheduled || $post->lock_version !== $version || ! $post->scheduled_at || $post->scheduled_at->isFuture()) {
                return null;
            }
            $this->saveVersion($post, [
                'status' => PostStatus::Published, 'published_at' => now(), 'scheduled_at' => null,
            ], $version);
            $this->record($post, null, 'post.published', ['source' => 'scheduler']);
            $this->notify($post, null, 'post.published');

            return $post;
        }, 3);
    }
}
