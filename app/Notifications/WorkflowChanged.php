<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkflowChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $postId,
        public readonly string $title,
        public readonly string $event,
        public readonly ?string $feedback = null,
    ) {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Publishing update: :title', ['title' => $this->title]))
            ->line(__($this->eventLabel()))
            ->line($this->title)
            ->when($this->feedback, fn (MailMessage $message) => $message->line($this->feedback))
            ->action(__('Open article'), url('/posts/'.$this->postId.'/edit'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'post_id' => $this->postId,
            'title' => $this->title,
            'event' => $this->event,
            'message' => __($this->eventLabel()),
            'feedback' => $this->feedback,
            'url' => url('/posts/'.$this->postId.'/edit'),
        ];
    }

    private function eventLabel(): string
    {
        return match ($this->event) {
            'post.submitted' => 'An article is ready for review.',
            'post.approved' => 'Your article has been approved.',
            'post.rejected' => 'Your article needs changes.',
            'post.scheduled' => 'Your article has been scheduled.',
            'post.published' => 'Your article has been published.',
            'post.archived' => 'Your article has been archived.',
            default => 'The article workflow has changed.',
        };
    }
}
