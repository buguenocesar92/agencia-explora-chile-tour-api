<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class UserPrivateNotification implements ShouldBroadcast
{
    public $message;
    public $userId;

    public function __construct($message, $userId)
    {
        $this->message = $message;
        $this->userId = $userId;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user.'.$this->userId); // Ej: "user.5"
    }

    // Opcional: Nombre personalizado para el evento
    public function broadcastAs()
    {
        return 'private.notification';
    }
}
