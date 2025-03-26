<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class NewReservationEvent implements ShouldBroadcast
{
    use SerializesModels;

    public $reservation;
    public $professionalId;

    public function __construct($reservation, $professionalId)
    {
        $this->reservation = $reservation;
        $this->professionalId = $professionalId;
    }

    public function broadcastOn()
    {
        // Canal privado para el profesional
        return new PrivateChannel('reservations.' . $this->professionalId);
    }

    public function broadcastAs()
    {
        return 'new.reservation';
    }
}
