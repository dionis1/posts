<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublicationSearchRequest;
use App\Models\Post;
use App\Services\RecordPostView;
use Illuminate\Http\Request;

class PublicationController extends Controller
{
    public function index(PublicationSearchRequest $request)
    {
        $posts = Post::published()->with(['author', 'category'])->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->toString()))
            ->when($request->filled('category'), fn ($q) => $q->whereHas('category', fn ($c) => $c->where('slug', $request->input('category'))))
            ->when($request->filled('tag'), fn ($q) => $q->whereHas('tags', fn ($t) => $t->where('slug', $request->input('tag'))))
            ->orderByDesc('published_at')->orderByDesc('id')->paginate(12)->withQueryString();

        return view('public.index', compact('posts'));
    }

    public function show(Request $request, string $slug, RecordPostView $views)
    {
        $post = Post::published()->where('slug', $slug)->with(['author', 'category', 'tags', 'coverMedia'])->firstOrFail();
        $views->record($post, $request);
        $comments = $post->comments()->where('status', 'approved')->with('user')->oldest()->get();

        return view('public.show', compact('post', 'comments'));
    }

    public function sitemap()
    {
        $posts = Post::published()->select(['slug', 'updated_at'])->orderBy('id')->get();

        return response()->view('public.sitemap', compact('posts'))->header('Content-Type', 'application/xml');
    }
}
