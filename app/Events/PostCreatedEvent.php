<?php

namespace App\Events;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Post;
use App\User;

class PostCreatedEvent extends Event
{
    use SerializesModels;

    public $post = null;
    public $user = null;

    /**
     * Create a new event instance.
     *
     * @return void
     */
     public function __construct(Post $post, User $user)
     {
         $this->post = $post;
         $this->user = $user;
     }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }
}
