<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PostStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\PostRevision;
use App\Services\PostService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PostController extends Controller
{
    public function __construct(private PostService $posts) {}

    private function requireAbility(Request $request, string $ability): void
    {
        abort_unless($request->user()->tokenCan($ability), 403, 'This token does not grant the required ability.');
    }

    public function index(Request $request)
    {
        $this->requireAbility($request, 'read');
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'status' => ['nullable', Rule::enum(PostStatus::class)],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'tag_id' => ['nullable', 'integer', 'exists:tags,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
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

    public function store(Request $request)
    {
        $this->requireAbility($request, 'write');
        $post = $this->posts->create($request->user(), $this->validatedContent($request));

        return (new PostResource($post->load(['author', 'category', 'tags'])))->response()->setStatusCode(201);
    }

    public function update(Request $request, Post $post)
    {
        $this->requireAbility($request, 'write');
        $data = $this->validatedContent($request, $post);
        $post = $this->posts->update($request->user(), $post, $data, $data['lock_version']);

        return new PostResource($post->load(['author', 'category', 'tags']));
    }

    public function transition(Request $request, Post $post)
    {
        $this->requireAbility($request, 'write');
        $data = $request->validate([
            'status' => ['required', Rule::enum(PostStatus::class)],
            'lock_version' => ['required', 'integer', 'min:1'],
            'feedback' => ['nullable', 'string', 'max:3000'],
            'scheduled_at' => ['nullable', 'date_format:Y-m-d\TH:i:sP', 'after:now'],
        ]);
        $post = $this->posts->transition($request->user(), $post, $data['status'], $data['lock_version'], $data['feedback'] ?? null, $data['scheduled_at'] ?? null);

        return new PostResource($post->load(['author', 'category', 'tags']));
    }

    public function restore(Request $request, Post $post, PostRevision $revision)
    {
        $this->requireAbility($request, 'write');
        $data = $request->validate(['lock_version' => ['required', 'integer', 'min:1']]);
        abort_unless($revision->post_id === $post->id, 404);
        $post = $this->posts->restore($request->user(), $post, $revision, $data['lock_version']);

        return new PostResource($post->load(['author', 'category', 'tags']));
    }

    public function destroy(Request $request, Post $post)
    {
        $this->requireAbility($request, 'write');
        $data = $request->validate(['lock_version' => ['required', 'integer', 'min:1']]);
        $this->posts->delete($request->user(), $post, $data['lock_version']);

        return response()->noContent();
    }

    private function validatedContent(Request $request, ?Post $post = null): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:100000'],
            'slug' => ['nullable', 'string', 'max:200', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('posts', 'slug')->ignore($post?->id)],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'locale' => ['required', Rule::in(['en', 'de'])],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'cover_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'tags' => ['sometimes', 'array', 'max:20'],
            'tags.*' => ['integer', 'distinct', 'exists:tags,id'],
            'seo_title' => ['nullable', 'string', 'max:60'],
            'seo_description' => ['nullable', 'string', 'max:160'],
            'canonical_url' => ['nullable', 'url:http,https', 'max:2048'],
        ];
        if ($post) {
            $rules['lock_version'] = ['required', 'integer', 'min:1'];
        }

        return $request->validate($rules);
    }
}
