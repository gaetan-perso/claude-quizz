<?php declare(strict_types=1);

use App\Models\Lobby;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// Force Sanctum token auth pour les clients mobiles (Bearer token)
Broadcast::routes(['middleware' => ['auth:sanctum']]);

Broadcast::channel('lobby.{lobbyId}', function (User $user, string $lobbyId): array|false {
    $lobby = Lobby::with('participants')->find($lobbyId);

    if ($lobby === null) {
        return false;
    }

    $isParticipant = $lobby->participants()->where('user_id', $user->id)->exists();

    if (! $isParticipant) {
        return false;
    }

    return [
        'id'   => $user->id,
        'name' => $user->name,
    ];
});
