<?php

namespace App\Actions\Comments;

use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class CreateCommentAction
{
    public function __invoke(User $actor, Post $post, array $data): Comment
    {
        Gate::forUser($actor)->authorize('create', [Comment::class, $post]);
        if (preg_match_all('~https?://~i', $data['body']) > 3) {
            throw ValidationException::withMessages(['body' => __('Please include no more than three links.')]);
        }

        return DB::transaction(function () use ($actor, $post, $data) {
            $post = Post::published()->lockForUpdate()->findOrFail($post->id);
            $parent = ! empty($data['parent_id']) ? Comment::where('post_id', $post->id)->where('status', CommentStatus::Approved)->lockForUpdate()->findOrFail($data['parent_id']) : null;
            if ($parent && $parent->depth >= 3) {
                throw ValidationException::withMessages(['parent_id' => __('This discussion has reached the maximum reply depth.')]);
            }

            return Comment::create(['post_id' => $post->id, 'user_id' => $actor->id, 'body' => $data['body'], 'parent_id' => $parent?->id, 'depth' => $parent ? $parent->depth + 1 : 0, 'status' => CommentStatus::Pending]);
        }, 3);
    }
}
