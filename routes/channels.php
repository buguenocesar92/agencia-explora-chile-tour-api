<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('reservations.{professionalId}', function ($user, $professionalId) {
    // Por ejemplo, permite el acceso si el ID del usuario coincide con el ID del profesional
    return (int) $user->id === (int) $professionalId;
});
