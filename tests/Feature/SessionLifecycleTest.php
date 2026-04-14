<?php declare(strict_types=1);

use App\Enums\Difficulty;
use App\Models\Category;
use App\Models\QuizSession;
use App\Models\User;

function makeActiveSession(User $user): QuizSession
{
    $category = Category::factory()->create();

    return QuizSession::create([
        'user_id'             => $user->id,
        'category_id'         => $category->id,
        'status'              => 'active',
        'current_difficulty'  => Difficulty::Medium,
        'consecutive_correct' => 0,
        'consecutive_wrong'   => 0,
        'score'               => 5,
    ]);
}

describe('POST /api/v1/sessions/{id}/complete', function () {
    it('marks session as completed', function () {
        $user    = User::factory()->create();
        $session = makeActiveSession($user);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/sessions/{$session->id}/complete");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('quiz_sessions', ['id' => $session->id, 'status' => 'completed']);
        $this->assertNotNull(QuizSession::find($session->id)->completed_at);
    });

    it('returns 422 if session is not active', function () {
        $user    = User::factory()->create();
        $session = makeActiveSession($user);
        $session->update(['status' => 'completed', 'completed_at' => now()]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/sessions/{$session->id}/complete")
            ->assertStatus(422);
    });

    it('returns 403 for other user', function () {
        $owner   = User::factory()->create();
        $other   = User::factory()->create();
        $session = makeActiveSession($owner);

        $this->actingAs($other, 'sanctum')
            ->postJson("/api/v1/sessions/{$session->id}/complete")
            ->assertStatus(403);
    });
});

describe('POST /api/v1/sessions/{id}/abandon', function () {
    it('marks session as abandoned', function () {
        $user    = User::factory()->create();
        $session = makeActiveSession($user);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/sessions/{$session->id}/abandon")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'abandoned');

        $this->assertDatabaseHas('quiz_sessions', ['id' => $session->id, 'status' => 'abandoned']);
    });

    it('returns 422 if already abandoned', function () {
        $user    = User::factory()->create();
        $session = makeActiveSession($user);
        $session->update(['status' => 'abandoned']);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/sessions/{$session->id}/abandon")
            ->assertStatus(422);
    });
});

describe('GET /api/v1/sessions', function () {
    it('returns paginated list of user sessions', function () {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        makeActiveSession($user);
        makeActiveSession($user);
        makeActiveSession($other);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/sessions');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['id', 'status', 'score', 'category']], 'meta']);
    });

    it('requires authentication', function () {
        $this->getJson('/api/v1/sessions')->assertStatus(401);
    });
});

describe('GET /api/v1/sessions/{id}', function () {
    it('returns session details', function () {
        $user    = User::factory()->create();
        $session = makeActiveSession($user);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/sessions/{$session->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $session->id)
            ->assertJsonStructure(['data' => ['id', 'status', 'score', 'category']]);
    });

    it('returns 403 for other user', function () {
        $owner   = User::factory()->create();
        $other   = User::factory()->create();
        $session = makeActiveSession($owner);

        $this->actingAs($other, 'sanctum')
            ->getJson("/api/v1/sessions/{$session->id}")
            ->assertStatus(403);
    });
});
