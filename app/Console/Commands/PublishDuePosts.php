<?php

namespace App\Console\Commands;

use App\Enums\PostStatus;
use App\Jobs\PublishPost;
use App\Models\Post;
use Illuminate\Console\Command;

class PublishDuePosts extends Command
{
    protected $signature = 'posts:publish-due';

    protected $description = 'Queue articles whose approved publishing time has arrived';

    public function handle(): int
    {
        $queued = 0;
        Post::query()->where('status', PostStatus::Scheduled)->where('scheduled_at', '<=', now())
            ->select(['id', 'lock_version'])->chunkById(100, function ($posts) use (&$queued) {
                foreach ($posts as $post) {
                    PublishPost::dispatch($post->id, $post->lock_version);
                    $queued++;
                }
            });
        $this->info("Queued {$queued} due article(s).");

        return self::SUCCESS;
    }
}
