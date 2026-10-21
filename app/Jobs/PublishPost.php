<?php

namespace App\Jobs;

use App\Services\PostService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PublishPost implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(public readonly int $postId, public readonly int $version)
    {
        $this->afterCommit();
    }

    public function handle(PostService $service): void
    {
        $service->publishScheduled($this->postId, $this->version);
    }
}
