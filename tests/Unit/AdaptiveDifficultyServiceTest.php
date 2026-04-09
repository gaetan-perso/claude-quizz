<?php declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\Difficulty;
use App\Models\Category;
use App\Models\Question;
use App\Models\QuizSession;
use App\Models\User;
use App\Services\AdaptiveDifficultyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdaptiveDifficultyServiceTest extends TestCase
{
    use RefreshDatabase;

    private AdaptiveDifficultyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AdaptiveDifficultyService();
    }

    private function makeSession(array $attrs = []): QuizSession
    {
        return new QuizSession(array_merge([
            'user_id'             => User::factory()->create()->id,
            'category_id'         => Category::factory()->create()->id,
            'status'              => 'active',
            'current_difficulty'  => Difficulty::Medium,
            'consecutive_correct' => 0,
            'consecutive_wrong'   => 0,
            'score'               => 0,
        ], $attrs));
    }

    // ─── applyAnswer ───────────────────────────────────────────────────────────

    public function test_correct_answer_increments_consecutive_correct(): void
    {
        $session = $this->makeSession(['consecutive_correct' => 1, 'consecutive_wrong' => 0]);

        $result = $this->service->applyAnswer($session, true);

        $this->assertSame(2, $result['consecutive_correct']);
        $this->assertSame(0, $result['consecutive_wrong']);
        $this->assertSame(Difficulty::Medium, $result['current_difficulty']);
    }

    public function test_wrong_answer_increments_consecutive_wrong(): void
    {
        $session = $this->makeSession(['consecutive_correct' => 0, 'consecutive_wrong' => 1]);

        $result = $this->service->applyAnswer($session, false);

        $this->assertSame(0, $result['consecutive_correct']);
        $this->assertSame(2, $result['consecutive_wrong']);
        $this->assertSame(Difficulty::Medium, $result['current_difficulty']);
    }

    public function test_correct_answer_resets_consecutive_wrong(): void
    {
        $session = $this->makeSession(['consecutive_correct' => 0, 'consecutive_wrong' => 2]);

        $result = $this->service->applyAnswer($session, true);

        $this->assertSame(1, $result['consecutive_correct']);
        $this->assertSame(0, $result['consecutive_wrong']);
    }

    public function test_wrong_answer_resets_consecutive_correct(): void
    {
        $session = $this->makeSession(['consecutive_correct' => 2, 'consecutive_wrong' => 0]);

        $result = $this->service->applyAnswer($session, false);

        $this->assertSame(0, $result['consecutive_correct']);
        $this->assertSame(1, $result['consecutive_wrong']);
    }

    public function test_3_consecutive_correct_upgrades_medium_to_hard(): void
    {
        $session = $this->makeSession([
            'current_difficulty'  => Difficulty::Medium,
            'consecutive_correct' => 2,
            'consecutive_wrong'   => 0,
        ]);

        $result = $this->service->applyAnswer($session, true);

        $this->assertSame(Difficulty::Hard, $result['current_difficulty']);
        $this->assertSame(0, $result['consecutive_correct']);
        $this->assertSame(0, $result['consecutive_wrong']);
    }

    public function test_3_consecutive_correct_upgrades_easy_to_medium(): void
    {
        $session = $this->makeSession([
            'current_difficulty'  => Difficulty::Easy,
            'consecutive_correct' => 2,
            'consecutive_wrong'   => 0,
        ]);

        $result = $this->service->applyAnswer($session, true);

        $this->assertSame(Difficulty::Medium, $result['current_difficulty']);
        $this->assertSame(0, $result['consecutive_correct']);
    }

    public function test_3_consecutive_wrong_downgrades_medium_to_easy(): void
    {
        $session = $this->makeSession([
            'current_difficulty'  => Difficulty::Medium,
            'consecutive_correct' => 0,
            'consecutive_wrong'   => 2,
        ]);

        $result = $this->service->applyAnswer($session, false);

        $this->assertSame(Difficulty::Easy, $result['current_difficulty']);
        $this->assertSame(0, $result['consecutive_correct']);
        $this->assertSame(0, $result['consecutive_wrong']);
    }

    public function test_3_consecutive_wrong_downgrades_hard_to_medium(): void
    {
        $session = $this->makeSession([
            'current_difficulty'  => Difficulty::Hard,
            'consecutive_correct' => 0,
            'consecutive_wrong'   => 2,
        ]);

        $result = $this->service->applyAnswer($session, false);

        $this->assertSame(Difficulty::Medium, $result['current_difficulty']);
        $this->assertSame(0, $result['consecutive_wrong']);
    }

    public function test_difficulty_stays_at_hard_ceiling(): void
    {
        $session = $this->makeSession([
            'current_difficulty'  => Difficulty::Hard,
            'consecutive_correct' => 2,
            'consecutive_wrong'   => 0,
        ]);

        $result = $this->service->applyAnswer($session, true);

        $this->assertSame(Difficulty::Hard, $result['current_difficulty']);
    }

    public function test_difficulty_stays_at_easy_floor(): void
    {
        $session = $this->makeSession([
            'current_difficulty'  => Difficulty::Easy,
            'consecutive_correct' => 0,
            'consecutive_wrong'   => 2,
        ]);

        $result = $this->service->applyAnswer($session, false);

        $this->assertSame(Difficulty::Easy, $result['current_difficulty']);
    }

    // ─── selectNextQuestion ────────────────────────────────────────────────────

    public function test_select_next_question_returns_question_matching_current_difficulty(): void
    {
        $user     = User::factory()->create();
        $category = Category::factory()->create();
        $question = Question::factory()->create([
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

        $result = $this->service->selectNextQuestion($session);

        $this->assertNotNull($result);
        $this->assertSame($question->id, $result->id);
    }

    public function test_select_next_question_excludes_already_answered(): void
    {
        $user     = User::factory()->create();
        $category = Category::factory()->create();

        $answered = Question::factory()->create([
            'category_id' => $category->id,
            'difficulty'  => Difficulty::Medium,
            'is_active'   => true,
        ]);
        $next = Question::factory()->create([
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

        $session->answers()->create([
            'question_id' => $answered->id,
            'choice_id'   => null,
            'is_correct'  => false,
            'answered_at' => now(),
        ]);

        $result = $this->service->selectNextQuestion($session);

        $this->assertNotNull($result);
        $this->assertSame($next->id, $result->id);
    }

    public function test_select_next_question_falls_back_to_medium_when_no_hard_available(): void
    {
        $user     = User::factory()->create();
        $category = Category::factory()->create();
        $medium   = Question::factory()->create([
            'category_id' => $category->id,
            'difficulty'  => Difficulty::Medium,
            'is_active'   => true,
        ]);

        $session = QuizSession::create([
            'user_id'             => $user->id,
            'category_id'         => $category->id,
            'status'              => 'active',
            'current_difficulty'  => Difficulty::Hard,
            'consecutive_correct' => 0,
            'consecutive_wrong'   => 0,
            'score'               => 0,
        ]);

        $result = $this->service->selectNextQuestion($session);

        $this->assertNotNull($result);
        $this->assertSame($medium->id, $result->id);
        $this->assertSame(Difficulty::Medium, $result->difficulty);
    }

    public function test_select_next_question_returns_null_when_no_questions_available(): void
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

        $result = $this->service->selectNextQuestion($session);

        $this->assertNull($result);
    }

    public function test_inactive_questions_are_excluded(): void
    {
        $user     = User::factory()->create();
        $category = Category::factory()->create();

        Question::factory()->create([
            'category_id' => $category->id,
            'difficulty'  => Difficulty::Medium,
            'is_active'   => false,
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

        $result = $this->service->selectNextQuestion($session);

        $this->assertNull($result);
    }
}
