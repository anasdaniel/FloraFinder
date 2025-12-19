<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ForumThread;
use App\Models\ForumPost;
use App\Notifications\ThreadLikedNotification;
use App\Notifications\CommentCreatedNotification;
use App\Notifications\ReplyCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_liking_a_thread_sends_notification()
    {
        Notification::fake();

        $owner = User::factory()->create();
        $liker = User::factory()->create();
        $thread = ForumThread::factory()->create([
            'user_id' => $owner->id,
            'title' => 'Test Thread',
            'category' => 'General',
            'content' => 'Test Content',
        ]);

        $this->actingAs($liker)
            ->post(route('forum.like', $thread));

        Notification::assertSentTo(
            $owner,
            ThreadLikedNotification::class,
            function ($notification) use ($thread, $liker, $owner) {
                return $notification->toArray($owner)['thread_id'] === $thread->id &&
                       $notification->toArray($owner)['user_id'] === $liker->id;
            }
        );
    }

    public function test_commenting_on_a_thread_sends_notification()
    {
        Notification::fake();

        $owner = User::factory()->create();
        $commenter = User::factory()->create();
        $thread = ForumThread::factory()->create([
            'user_id' => $owner->id,
            'title' => 'Test Thread',
            'category' => 'General',
            'content' => 'Test Content',
        ]);

        $this->actingAs($commenter)
            ->post("/forum/{$thread->id}/comments", [
                'content' => 'Nice post!',
            ]);

        Notification::assertSentTo(
            $owner,
            CommentCreatedNotification::class,
            function ($notification) use ($thread, $commenter, $owner) {
                return $notification->toArray($owner)['thread_id'] === $thread->id &&
                       $notification->toArray($owner)['user_id'] === $commenter->id;
            }
        );
    }

    public function test_replying_to_a_comment_sends_notification()
    {
        Notification::fake();

        $threadOwner = User::factory()->create();
        $commentOwner = User::factory()->create();
        $replier = User::factory()->create();

        $thread = ForumThread::factory()->create([
            'user_id' => $threadOwner->id,
            'title' => 'Test Thread',
            'category' => 'General',
            'content' => 'Test Content',
        ]);
        $comment = ForumPost::factory()->create([
            'forum_thread_id' => $thread->id,
            'user_id' => $commentOwner->id,
            'parent_post_id' => null,
            'content' => 'Test Comment',
        ]);

        $this->actingAs($replier)
            ->post(route('forum.reply', ['thread' => $thread->id, 'post' => $comment->id]), [
                'content' => 'I agree!',
            ]);

        Notification::assertSentTo(
            $commentOwner,
            ReplyCreatedNotification::class,
            function ($notification) use ($thread, $replier, $commentOwner) {
                return $notification->toArray($commentOwner)['thread_id'] === $thread->id &&
                       $notification->toArray($commentOwner)['user_id'] === $replier->id;
            }
        );
    }
}
