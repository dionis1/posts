<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostView;
use Illuminate\Http\Request;

class RecordPostView
{
    public function record(Post $post, Request $request): void
    {
        if (! Post::published()->whereKey($post->id)->exists()) {
            return;
        }

        $date = now()->toDateString();
        $visitor = $request->user()
            ? 'user:'.$request->user()->id
            : 'guest:'.$request->ip().'|'.mb_substr((string) $request->userAgent(), 0, 512);
        $hash = hash_hmac('sha256', $date.'|'.$visitor, (string) config('app.key'));
        PostView::query()->insertOrIgnore(['post_id' => $post->id, 'date' => $date, 'visitor_hash' => $hash]);
    }
}
