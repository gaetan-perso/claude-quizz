<?php declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class LobbyPlayerLeft implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $lobbyId,
        public readonly string $userId,
        public readonly array  $participants,
    ) {}

    public function broadcastOn(): Channel
    {
        return new PresenceChannel('lobby.' . $this->lobbyId);
    }

    public function broadcastAs(): string
    {
        return 'player.left';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id'      => $this->userId,
            'participants' => $this->participants,
        ];
    }
}
