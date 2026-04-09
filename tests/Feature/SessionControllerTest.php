<?php declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Difficulty;
use App\Models\Category;
use App\Models\Choice;
use App\Models\Question;
use App\Models\QuizSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionControllerTest extends TestCase
{
    use RefreshDatabase;

    // ─── POST /api/v1/sessions ─────────────────────────────────────────────────

    public function test_create_session_requires_authentication(): void
    {
        $category = Category::factory()->create();

        $response = $this->postJson('/api/v1/sessions', ['category_id' => $category->id]);

        $response->assertStatus(401);
    }

    public function test_create_session_with_valid_category(): void
    {
        $user     = User::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/sessions', ['category_id' => $category->id]);

        $response->assertStatus(201)
            ->assertJsonPath('data.category_id', $category->id)
            ->assertJsonPath('data.current_difficulty', 'medium')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.score', 0);

        $this->assertDatabaseHas('quiz_sessions', [
            'user_id'            => $user->id,
            'category_id'        => $category->id,
            'current_difficulty' => 'medium',
            'status'             => 'active',
        ]);
    }

    public function test_create_session_fails_with_invalid_category(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/sessions', ['category_id' => 'nonexistent-ulid-xxx']);

        $response->assertStatus(422);
    }

    // ─── GET /api/v1/sessions/{id}/next-question ───────────────────────────────

    public function test_next_question_requires_authentication(): void
    {
        $session = $this->makeSession();

        $response = $this->getJson("/api/v1/sessions/{$session->id}/next-question");

        $response->assertStatus(401);
    }

    public function test_next_question_returns_medium_difficulty_initially(): void
    {
        $user     = User::factory()->create();
        $category = Category::factory()->create();
        Question::factory()->create([
            'category_id' => $category->id,
            'difficulty'  => Difficulty::Medium,
            'is_active'   => true,
        ]);

        $session = QuizSession::create([
            'user_id'             => $user->id,
            'category_id'         => $category->id,
            'status'              => 'active',
            'current_difficulty'  => Difficulty::Medium,
            'consecutive_correct' => 0,
            'consecutive_wrong'   => 0,
            'score'               => 0,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/sessions/{$session->id}/next-question");

        $response->assertStatus(200)
            ->assertJsonPath('data.question.difficulty', 'medium')
            ->assertJsonStructure(['data' => ['question' => ['id', 'text', 'difficulty', 'estimated_time_seconds', 'choices']]]);
    }

    public function test_next_question_returns_null_data_when_no_questions_left(): void
    {
        $user     = User::factory()->create();
        $category = Category::factory()->create();

        $session = QuizSession::create([
            'user_id'             => $user->id,
            'category_id'         => $category->id,
            'status'              => 'active',
            'current_difficulty'  => Difficulty::Medium,
            'consecutive_correct' => 0,
            'consecutive_wrong'   => 0,
            'score'               => 0,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/sessions/{$session->id}/next-question");

        $response->assertStatus(200)
            ->assertJsonPath('data', null);
    }

    public function test_next_question_forbidden_for_other_user(): void
    {
        $owner    = User::factory()->create();
        $other    = User::factory()->create();
        $session  = $this->makeSession(['user_id' => $owner->id]);

        $response = $this->actingAs($other, 'sanctum')
            ->getJson("/api/v1/sessions/{$session->id}/next-question");

        $response->assertStatus(403);
    }

    // ─── POST /api/v1/sessions/{id}/answers ───────────────────────────────────

    public function test_submit_answer_requires_authentication(): void
    {
        $session = $this->makeSession();

        $response = $this->postJson("/api/v1/sessions/{$session->id}/answers", [
            'question_id' => 'x',
            'choice_id'   => 'y',
        ]);

        $response->assertStatus(401);
    }

    public function test_submit_correct_answer_increments_score(): void
    {
        $user          = User::factory()->create();
        $category      = Category::factory()->create();
        $question      = Question::factory()->create(['category_id' => $category->id, 'is_active' => true]);
        $correctChoice = Choice::factory()->correct()->create(['question_id' => $question->id]);

        $session = QuizSession::create([
            'user_id'             => $user->id,
            'category_id'         => $category->id,
            'status'              => 'active',
            'current_difficulty'  => Difficulty::Medium,
            'consecutive_correct' => 0,
            'consecutive_wrong'   => 0,
            'score'               => 0,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/sessions/{$session->id}/answers", [
                'question_id' => $question->id,
                'choice_id'   => $correctChoice->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.is_correct', true)
            ->assertJsonPath('data.score', 1);

        $this->assertDatabaseHas('session_answers', [
            'session_id'  => $session->id,
            'question_id' => $question->id,
            'is_correct'  => true,
        ]);
    }

    public function test_submit_wrong_answer_does_not_increment_score(): void
    {
        $user        = User::factory()->create();
        $category    = Category::factory()->create();
        $question    = Question::factory()->create(['category_id' => $category->id, 'is_active' => true]);
        $wrongChoice = Choice::factory()->create(['question_id' => $question->id, 'is_correct' => false]);

        $session = QuizSession::create([
            'user_id'             => $user->id,
            'category_id'         => $category->id,
            'status'              => 'active',
            'current_difficulty'  => Difficulty::Medium,
            'consecutive_correct' => 0,
            'consecutive_wrong'   => 0,
            'score'               => 0,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/sessions/{$session->id}/answers", [
                'question_id' => $question->id,
                'choice_id'   => $wrongChoice->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.is_correct', false)
            ->assertJsonPath('data.score', 0);
    }

    public function test_3_correct_answers_upgrade_difficulty_to_hard(): void
    {
        $user     = User::factory()->create();
        $category = Category::factory()->create();
        $question = Question::factory()->create(['category_id' => $category->id, 'is_active' => true]);
        $correct  = Choice::factory()->correct()->create(['question_id' => $question->id]);

        $session = QuizSession::create([
            'user_id'             => $user->id,
            'category_id'         => $category->id,
            'status'              => 'active',
            'current_difficulty'  => Difficulty::Medium,
            'consecutive_correct' => 2,
            'consecutive_wrong'   => 0,
            'score'               => 0,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/sessions/{$session->id}/answers", [
                'question_id' => $question->id,
                'choice_id'   => $correct->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.current_difficulty', 'hard');

        $this->assertDatabaseHas('quiz_sessions', [
            'id'                  => $session->id,
            'current_difficulty'  => 'hard',
            'consecutive_correct' => 0,
        ]);
    }

    public function test_3_wrong_answers_downgrade_difficulty_to_easy(): void
    {
        $user     = User::factory()->create();
        $category = Category::factory()->create();
        $question = Question::factory()->create(['category_id' => $category->id, 'is_active' => true]);
        $wrong    = Choice::factory()->create(['question_id' => $question->id, 'is_correct' => false]);

        $session = QuizSession::create([
            'user_id'             => $user->id,
            'category_id'         => $category->id,
            'status'              => 'active',
            'current_difficulty'  => Difficulty::Medium,
            'consecutive_correct' => 0,
            'consecutive_wrong'   => 2,
            'score'               => 0,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/sessions/{$session->id}/answers", [
                'question_id' => $question->id,
                'choice_id'   => $wrong->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.current_difficulty', 'easy');

        $this->assertDatabaseHas('quiz_sessions', [
            'id'                 => $session->id,
            'current_difficulty' => 'easy',
            'consecutive_wrong'  => 0,
        ]);
    }

    public function test_submit_answer_forbidden_for_other_user(): void
    {
        $owner    = User::factory()->create();
        $other    = User::factory()->create();
        $session  = $this->makeSession(['user_id' => $owner->id]);
        $question = Question::factory()->create(['category_id' => $session->category_id, 'is_active' => true]);
        $choice   = Choice::factory()->create(['question_id' => $question->id]);

        $response = $this->actingAs($other, 'sanctum')
            ->postJson("/api/v1/sessions/{$session->id}/answers", [
                'question_id' => $question->id,
                'choice_id'   => $choice->id,
            ]);

        $response->assertStatus(403);
    }

    public function test_cannot_answer_same_question_twice(): void
    {
        $user     = User::factory()->create();
        $category = Category::factory()->create();
        $question = Question::factory()->create(['category_id' => $category->id, 'is_active' => true]);
        $choice   = Choice::factory()->create(['question_id' => $question->id, 'is_correct' => false]);

        $session = QuizSession::create([
            'user_id'             => $user->id,
            'category_id'         => $category->id,
            'status'              => 'active',
            'current_difficulty'  => Difficulty::Medium,
            'consecutive_correct' => 0,
            'consecutive_wrong'   => 0,
            'score'               => 0,
        ]);

        // First answer
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/sessions/{$session->id}/answers", [
                'question_id' => $question->id,
                'choice_id'   => $choice->id,
            ])->assertStatus(200);

        // Second answer to the same question
        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/sessions/{$session->id}/answers", [
                'question_id' => $question->id,
                'choice_id'   => $choice->id,
            ]);

        $response->assertStatus(422);
    }

    // ─── Helper ────────────────────────────────────────────────────────────────

    private function makeSession(array $attrs = []): QuizSession
    {
        $user     = isset($attrs['user_id']) ? User::find($attrs['user_id']) : User::factory()->create();
        $category = Category::factory()->create();

        return QuizSession::create(array_merge([
            'user_id'             => $user->id,
            'category_id'         => $category->id,
            'status'              => 'active',
            'current_difficulty'  => Difficulty::Medium,
            'consecutive_correct' => 0,
            'consecutive_wrong'   => 0,
            'score'               => 0,
        ], $attrs));
    }
}
