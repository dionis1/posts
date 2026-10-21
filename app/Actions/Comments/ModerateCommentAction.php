<?php

namespace App\Actions\Comments;

use App\Enums\CommentStatus;
use App\Models\Activity;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class ModerateCommentAction
{
    public function __invoke(User $actor, Comment $comment, CommentStatus $status): void
    {
        Gate::forUser($actor)->authorize('moderate', $comment);
        abort_if($status === CommentStatus::Pending, 422);
        DB::transaction(function () use ($actor, $comment, $status) {
            $comment = Comment::lockForUpdate()->findOrFail($comment->id);
            $parent = $comment->parent_id ? Comment::lockForUpdate()->find($comment->parent_id) : null;
            if ($status === CommentStatus::Approved && $comment->parent_id && $parent?->status !== CommentStatus::Approved) {
                throw ValidationException::withMessages(['status' => __('Approve the parent comment before its reply.')]);
            }
            $values = ['status' => $status->value, 'moderated_by' => $actor->id, 'moderated_at' => now()];
            $comment->update($values);
            if ($status === CommentStatus::Rejected) {
                $parents = [$comment->id];
                while ($parents !== []) {
                    $children = Comment::whereIn('parent_id', $parents)->pluck('id')->all();
                    Comment::whereIn('id', $children)->update($values);
                    $parents = $children;
                }
            }
            Activity::create(['post_id' => $comment->post_id, 'actor_id' => $actor->id, 'event' => 'comment.moderated', 'properties' => ['comment_id' => $comment->id, 'status' => $status->value]]);
        }, 3);
    }
}
