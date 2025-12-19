<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReplyCreatedNotification extends Notification
{
    use Queueable;

    protected $thread;
    protected $reply;
    protected $replier;

    /**
     * Create a new notification instance.
     */
    public function __construct($thread, $reply, $replier)
    {
        $this->thread = $thread;
        $this->reply = $reply;
        $this->replier = $replier;
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
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
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
            'reply_id' => $this->reply->id,
            'user_id' => $this->replier->id,
            'user_name' => $this->replier->name,
            'type' => 'reply',
            'message' => "{$this->replier->name} replied to your comment on: \"{$this->thread->title}\"",
        ];
    }
}
