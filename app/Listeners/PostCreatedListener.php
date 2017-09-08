<?php

namespace App\Listeners;

use App\Events\PostCreatedEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Mail;

class PostCreatedListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  PostCreatedEvent  $event
     * @return void
     */
    public function handle(PostCreatedEvent $event)
    {
        $user = $event->user;
        Mail::send('emails.postcreated', ['user' => $event->user, 'post' => $event->post], function ($m) use ($user) {
            $m->to($user->email, $user->name)->subject('New Post!');
        });
    }
}
