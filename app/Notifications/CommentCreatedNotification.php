<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommentCreatedNotification extends Notification
{
    use Queueable;

    protected $thread;
    protected $comment;
    protected $commenter;

    /**
     * Create a new notification instance.
     */
    public function __construct($thread, $comment, $commenter)
    {
        $this->thread = $thread;
        $this->comment = $comment;
        $this->commenter = $commenter;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'thread_id' => $this->thread->id,
            'thread_title' => $this->thread->title,
            'comment_id' => $this->comment->id,
            'user_id' => $this->commenter->id,
            'user_name' => $this->commenter->name,
            'type' => 'comment',
            'message' => "{$this->commenter->name} commented on your post: \"{$this->thread->title}\"",
        ];
    }
}
