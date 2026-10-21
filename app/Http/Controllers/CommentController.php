<?php

namespace App\Http\Controllers;

use App\Actions\Comments\CreateCommentAction;
use App\Actions\Comments\ModerateCommentAction;
use App\Enums\CommentStatus;
use App\Http\Requests\CommentInput;
use App\Http\Requests\ModerateCommentRequest;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Comment::class);

        return view('comments.index', ['comments' => Comment::with(['author', 'post'])->where('status', CommentStatus::Pending)->oldest()->paginate(20)]);
    }

    public function store(CommentInput $request, Post $post, CreateCommentAction $create)
    {
        $create($request->user(), $post, $request->validated());

        return back()->with('status', __('Your comment is awaiting moderation.'));
    }

    public function moderate(ModerateCommentRequest $request, Comment $comment, ModerateCommentAction $moderate)
    {
        $moderate($request->user(), $comment, CommentStatus::from($request->validated('status')));

        return back()->with('status', __('Comment moderation saved.'));
    }
}
