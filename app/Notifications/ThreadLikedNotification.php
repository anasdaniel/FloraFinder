<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ThreadLikedNotification extends Notification
{
    use Queueable;

    protected $thread;
    protected $liker;

    /**
     * Create a new notification instance.
     */
    public function __construct($thread, $liker)
    {
        $this->thread = $thread;
        $this->liker = $liker;
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
            'user_id' => $this->liker->id,
            'user_name' => $this->liker->name,
            'type' => 'like',
            'message' => "{$this->liker->name} liked your post: \"{$this->thread->title}\"",
        ];
    }
}
