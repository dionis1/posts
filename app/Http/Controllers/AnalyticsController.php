<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostView;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $visible = Post::visibleTo($request->user());
        $totalPosts = (clone $visible)->count();
        $statusCounts = (clone $visible)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $dailyViews = PostView::whereIn('post_id', (clone $visible)->select('id'))
            ->where('date', '>=', now()->subDays(29)->toDateString())
            ->selectRaw('date, count(*) as total')->groupBy('date')->orderBy('date')->get();
        $posts = (clone $visible)->with('author')->withCount('views')->orderByDesc('views_count')->paginate(15);

        return view('analytics.index', compact('totalPosts', 'statusCounts', 'dailyViews', 'posts'));
    }

    public function export(Request $request)
    {
        return response()->streamDownload(function () use ($request) {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, ['ID', 'Title', 'Status', 'Author', 'Published at', 'Created at'], ',', '"', '');
            Post::visibleTo($request->user())->with('author')->orderBy('id')->lazyById(200)->each(function (Post $post) use ($stream) {
                fputcsv($stream, [
                    $post->id, $this->safeCell($post->title), $post->status->value,
                    $this->safeCell($post->author->name), $post->published_at?->toIso8601String(), $post->created_at->toIso8601String(),
                ], ',', '"', '');
            });
            fclose($stream);
        }, 'posts-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }

    private function safeCell(string $value): string
    {
        // Spreadsheet programs can execute formulas even after leading whitespace.
        return preg_match('/^[\x00-\x20]*[=+@-]/u', $value) ? "'".$value : $value;
    }
}
