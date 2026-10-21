<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Posts\CreatePostAction;
use App\Actions\Posts\DeletePostAction;
use App\Actions\Posts\RestorePostAction;
use App\Actions\Posts\TransitionPostAction;
use App\Actions\Posts\UpdatePostAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PostContentRequest;
use App\Http\Requests\Api\PostIndexRequest;
use App\Http\Requests\Api\PostTransitionRequest;
use App\Http\Requests\Api\PostVersionRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\PostRevision;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    private function requireAbility(Request $request, string $ability): void
    {
        abort_unless($request->user()->tokenCan($ability), 403, 'This token does not grant the required ability.');
    }

    public function index(PostIndexRequest $request)
    {
        $this->requireAbility($request, 'read');
        $data = $request->validated();
        $posts = Post::visibleTo($request->user())->with(['author', 'category', 'tags'])
            ->search($data['search'] ?? null)
            ->when($data['status'] ?? null, fn ($query, $value) => $query->where('status', $value))
            ->when($data['category_id'] ?? null, fn ($query, $value) => $query->where('category_id', $value))
            ->when($data['tag_id'] ?? null, fn ($query, $value) => $query->whereHas('tags', fn ($tags) => $tags->where('tags.id', $value)))
            ->latest('id')->paginate($data['per_page'] ?? 20)->withQueryString();

        return PostResource::collection($posts);
    }

    public function show(Request $request, Post $post)
    {
        $this->requireAbility($request, 'read');
        Gate::authorize('view', $post);

        return new PostResource($post->load(['author', 'category', 'tags']));
    }

    public function store(PostContentRequest $request, CreatePostAction $action)
    {
        $this->requireAbility($request, 'write');
        $post = $action($request->user(), $request->validated());

        return (new PostResource($post->load(['author', 'category', 'tags'])))->response()->setStatusCode(201);
    }

    public function update(PostContentRequest $request, Post $post, UpdatePostAction $action)
    {
        $this->requireAbility($request, 'write');
        $data = $request->validated();
        $post = $action($request->user(), $post, $data, $data['lock_version']);

        return new PostResource($post->load(['author', 'category', 'tags']));
    }

    public function transition(PostTransitionRequest $request, Post $post, TransitionPostAction $action)
    {
        $this->requireAbility($request, 'write');
        $data = $request->validated();
        $post = $action($request->user(), $post, $data['status'], $data['lock_version'], $data['feedback'] ?? null, $data['scheduled_at'] ?? null);

        return new PostResource($post->load(['author', 'category', 'tags']));
    }

    public function restore(PostVersionRequest $request, Post $post, PostRevision $revision, RestorePostAction $action)
    {
        $this->requireAbility($request, 'write');
        $data = $request->validated();
        abort_unless($revision->post_id === $post->id, 404);
        $post = $action($request->user(), $post, $revision, $data['lock_version']);

        return new PostResource($post->load(['author', 'category', 'tags']));
    }

    public function destroy(PostVersionRequest $request, Post $post, DeletePostAction $action)
    {
        $this->requireAbility($request, 'write');
        $data = $request->validated();
        $action($request->user(), $post, $data['lock_version']);

        return response()->noContent();
    }
}
