<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewPrivateMessage implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $userId;
    public $message;

    public function __construct($userId, $message)
    {
        $this->userId = $userId;
        $this->message = $message;
    }

    // Al usar PrivateChannel, Laravel publicará el evento en un canal que se le antepone el prefijo (por defecto "laravel_database_")
    public function broadcastOn()
    {
        return new PrivateChannel('private-chat-' . $this->userId);
    }

    public function broadcastAs()
    {
        return 'new-private-message';
    }

    public function broadcastWith()
    {
        return [
            'user_id'   => $this->userId,
            'message'   => $this->message,
        ];
    }
}
