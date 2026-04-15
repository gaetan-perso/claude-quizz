<?php declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class LobbyPlayerJoined implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $lobbyId,
        public readonly string $userId,
        public readonly string $userName,
        public readonly array  $participants,
    ) {}

    public function broadcastOn(): Channel
    {
        return new PresenceChannel('lobby.' . $this->lobbyId);
    }

    public function broadcastAs(): string
    {
        return 'player.joined';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id'      => $this->userId,
            'user_name'    => $this->userName,
            'participants' => $this->participants,
        ];
    }
}
