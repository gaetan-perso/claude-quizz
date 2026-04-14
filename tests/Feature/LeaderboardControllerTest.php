<?php declare(strict_types=1);

use App\Enums\Difficulty;
use App\Models\Category;
use App\Models\QuizSession;
use App\Models\User;

describe('GET /api/v1/leaderboard', function () {
    it('returns top players ordered by total score', function () {
        $userA    = User::factory()->create(['name' => 'Alice']);
        $userB    = User::factory()->create(['name' => 'Bob']);
        $category = Category::factory()->create();

        QuizSession::create(['user_id' => $userA->id, 'category_id' => $category->id, 'status' => 'completed', 'score' => 30, 'current_difficulty' => Difficulty::Medium, 'consecutive_correct' => 0, 'consecutive_wrong' => 0, 'completed_at' => now()]);
        QuizSession::create(['user_id' => $userA->id, 'category_id' => $category->id, 'status' => 'completed', 'score' => 20, 'current_difficulty' => Difficulty::Medium, 'consecutive_correct' => 0, 'consecutive_wrong' => 0, 'completed_at' => now()]);
        QuizSession::create(['user_id' => $userB->id, 'category_id' => $category->id, 'status' => 'completed', 'score' => 60, 'current_difficulty' => Difficulty::Medium, 'consecutive_correct' => 0, 'consecutive_wrong' => 0, 'completed_at' => now()]);

        $response = $this->actingAs($userA, 'sanctum')->getJson('/api/v1/leaderboard');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Bob')
            ->assertJsonPath('data.0.total_score', 60)
            ->assertJsonPath('data.0.rank', 1)
            ->assertJsonPath('data.1.name', 'Alice')
            ->assertJsonPath('data.1.total_score', 50)
            ->assertJsonPath('data.1.sessions_count', 2);
    });

    it('ignores abandoned and active sessions', function () {
        $user     = User::factory()->create();
        $category = Category::factory()->create();

        QuizSession::create(['user_id' => $user->id, 'category_id' => $category->id, 'status' => 'active',    'score' => 100, 'current_difficulty' => Difficulty::Medium, 'consecutive_correct' => 0, 'consecutive_wrong' => 0]);
        QuizSession::create(['user_id' => $user->id, 'category_id' => $category->id, 'status' => 'abandoned', 'score' => 100, 'current_difficulty' => Difficulty::Medium, 'consecutive_correct' => 0, 'consecutive_wrong' => 0]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/leaderboard');

        $response->assertStatus(200)->assertJsonCount(0, 'data');
    });

    it('requires authentication', function () {
        $this->getJson('/api/v1/leaderboard')->assertStatus(401);
    });
});
