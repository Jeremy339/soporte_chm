<?php

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('ticket.{ticketId}', function (User $user, int $ticketId) {
    // 1. Busca el ticket
    $ticket = Ticket::find($ticketId);

    // 2. Si no existe, niega el acceso
    if (!$ticket) {
        return false;
    }

    // 3. Autoriza SOLO si el usuario es el creador O el técnico asignado
    return $user->id === $ticket->user_id || $user->id === $ticket->tecnico_id;
});

//// Canal privado para el dashboard de técnicos
Broadcast::channel('technician-dashboard', function (User $user) {
    return $user->hasRole(['tecnico', 'admin']);
});
