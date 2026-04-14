<?php declare(strict_types=1);
namespace Database\Factories;

use App\Models\Lobby;
use App\Models\LobbyParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

final class LobbyParticipantFactory extends Factory
{
    protected $model = LobbyParticipant::class;

    public function definition(): array
    {
        return [
            'lobby_id'  => Lobby::factory(),
            'user_id'   => User::factory(),
            'score'     => 0,
            'is_ready'  => false,
            'joined_at' => now(),
            'left_at'   => null,
        ];
    }
}
