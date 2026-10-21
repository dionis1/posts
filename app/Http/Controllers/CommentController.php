<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CommentController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Comment::class);
        $comments = Comment::with(['author', 'post'])->where('status', 'pending')->oldest()->paginate(20);

        return view('comments.index', compact('comments'));
    }

    public function store(Request $request, Post $post)
    {
        abort_unless(Post::published()->whereKey($post->id)->exists(), 404);
        $data = $request->validate([
            'body' => ['required', 'string', 'min:3', 'max:3000'],
            'parent_id' => ['nullable', 'integer', Rule::exists('comments', 'id')->where('post_id', $post->id)->where('status', 'approved')],
            'website' => ['nullable', 'size:0'],
        ]);
        if (preg_match_all('~https?://~i', $data['body']) > 3) {
            throw ValidationException::withMessages(['body' => __('Please include no more than three links.')]);
        }
        $parent = ! empty($data['parent_id']) ? Comment::findOrFail($data['parent_id']) : null;
        if ($parent && $parent->depth >= 3) {
            throw ValidationException::withMessages(['parent_id' => __('This discussion has reached the maximum reply depth.')]);
        }
        Comment::create([
            'post_id' => $post->id, 'user_id' => $request->user()->id,
            'body' => $data['body'], 'parent_id' => $parent?->id,
            'depth' => $parent ? $parent->depth + 1 : 0, 'status' => 'pending',
        ]);

        return back()->with('status', __('Your comment is awaiting moderation.'));
    }

    public function moderate(Request $request, Comment $comment)
    {
        Gate::authorize('moderate', $comment);
        $data = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected'])]]);
        if ($data['status'] === 'approved' && $comment->parent_id && $comment->parent?->status !== 'approved') {
            throw ValidationException::withMessages(['status' => __('Approve the parent comment before its reply.')]);
        }
        $comment->update(['status' => $data['status'], 'moderated_by' => $request->user()->id, 'moderated_at' => now()]);
        // Descendants remain hidden if an approved parent is rejected later.
        if ($data['status'] === 'rejected') {
            $this->rejectDescendants($comment, $request->user()->id);
        }

        return back()->with('status', __('Comment moderation saved.'));
    }

    private function rejectDescendants(Comment $comment, int $moderatorId): void
    {
        foreach ($comment->children as $child) {
            $child->update(['status' => 'rejected', 'moderated_by' => $moderatorId, 'moderated_at' => now()]);
            $this->rejectDescendants($child, $moderatorId);
        }
    }
}
