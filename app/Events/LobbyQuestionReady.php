<?php declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class LobbyQuestionReady implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $lobbyId,
        public readonly int    $questionIndex,
        public readonly int    $totalQuestions,
        public readonly array  $question,
        public readonly string $startedAt,
    ) {}

    public function broadcastOn(): Channel
    {
        return new PresenceChannel('lobby.' . $this->lobbyId);
    }

    public function broadcastAs(): string
    {
        return 'question.ready';
    }

    public function broadcastWith(): array
    {
        return [
            'question_index'  => $this->questionIndex,
            'total_questions' => $this->totalQuestions,
            'question'        => $this->question,
            'started_at'      => $this->startedAt,
        ];
    }
}
