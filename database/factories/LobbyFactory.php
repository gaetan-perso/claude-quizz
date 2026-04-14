<?php declare(strict_types=1);
namespace Database\Factories;

use App\Enums\LobbyStatus;
use App\Models\Category;
use App\Models\Lobby;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class LobbyFactory extends Factory
{
    protected $model = Lobby::class;

    public function definition(): array
    {
        return [
            'host_user_id' => User::factory(),
            'category_id'  => Category::factory(),
            'status'       => LobbyStatus::Waiting,
            'code'         => strtoupper(Str::random(6)),
            'max_players'  => 4,
            'started_at'   => null,
            'completed_at' => null,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(['status' => LobbyStatus::InProgress, 'started_at' => now()]);
    }
}
