<?php

namespace App\Http\Controllers;

use App\Actions\Posts\CreatePostAction;
use App\Actions\Posts\RestorePostAction;
use App\Actions\Posts\TransitionPostAction;
use App\Actions\Posts\UpdatePostAction;
use App\Http\Requests\CalendarRequest;
use App\Http\Requests\MarkdownPreviewRequest;
use App\Http\Requests\PostInput;
use App\Http\Requests\PostTransitionRequest;
use App\Http\Requests\RevisionComparisonRequest;
use App\Http\Requests\VersionRequest;
use App\Models\Post;
use App\Models\PostRevision;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EditorialController extends Controller
{
    public function index(Request $request)
    {
        return view('posts.index');
    }

    public function create()
    {
        Gate::authorize('create', Post::class);

        return view('posts.edit', $this->editorData(new Post(['locale' => app()->getLocale()])));
    }

    public function store(PostInput $request, CreatePostAction $action)
    {
        Gate::authorize('create', Post::class);
        $post = $action($request->user(), $request->validated());

        return redirect()->route('posts.edit', $post)->with('status', __('Draft created.'));
    }

    public function edit(Post $post)
    {
        Gate::authorize('view', $post);

        return view('posts.edit', $this->editorData($post));
    }

    private function editorData(Post $post): array
    {
        return ['post' => $post];
    }

    public function update(PostInput $request, Post $post, UpdatePostAction $action)
    {
        Gate::authorize('update', $post);
        $request->validate(['lock_version' => 'required|integer|min:1']);
        $action($request->user(), $post, $request->validated(), (int) $request->lock_version);

        return redirect()->route('posts.edit', $post)->with('status', __('Changes saved.'));
    }

    public function transition(PostTransitionRequest $request, Post $post, TransitionPostAction $action)
    {
        $data = $request->validated();
        $action($request->user(), $post, $data['target'], (int) $data['lock_version'], $data['feedback'] ?? null, $data['scheduled_at'] ?? null);

        return back()->with('status', __('Workflow updated.'));
    }

    public function revisions(RevisionComparisonRequest $request, Post $post)
    {
        Gate::authorize('view', $post);
        $revisions = $post->revisions()->with('author')->orderByDesc('version')->paginate(20);
        $left = $post->revisions()->when($request->filled('left'), fn ($q) => $q->whereKey($request->integer('left')))->orderByDesc('version')->first();
        $right = $post->revisions()->when($request->filled('right'), fn ($q) => $q->whereKey($request->integer('right')), fn ($q) => $q->where('version', '<', $left?->version ?? 0))->orderByDesc('version')->first();

        return view('posts.revisions', compact('post', 'revisions', 'left', 'right'));
    }

    public function restore(VersionRequest $request, Post $post, PostRevision $revision, RestorePostAction $action)
    {
        $data = $request->validated();
        abort_unless($revision->post_id === $post->id, 404);
        $action($request->user(), $post, $revision, (int) $data['lock_version']);

        return redirect()->route('posts.edit', $post)->with('status', __('Revision restored as a new draft.'));
    }

    public function preview(MarkdownPreviewRequest $request)
    {
        $data = $request->validated();
        $post = new Post(['body' => $data['body']]);

        return response()->json(['html' => $post->renderedBody()]);
    }

    public function activity(Post $post)
    {
        Gate::authorize('view', $post);

        return view('posts.activity', ['post' => $post, 'activities' => $post->activities()->with('actor')->latest('created_at')->paginate(30)]);
    }

    public function calendar(CalendarRequest $request)
    {
        $month = CarbonImmutable::createFromFormat('!Y-m', $request->input('month', now()->format('Y-m')));
        $posts = Post::visibleTo($request->user())->whereIn('status', ['scheduled', 'published'])
            ->where(function ($q) use ($month) {
                $q->whereBetween('scheduled_at', [$month, $month->endOfMonth()])->orWhereBetween('published_at', [$month, $month->endOfMonth()]);
            })->with('author')->get();

        return view('posts.calendar', compact('month', 'posts'));
    }
}
