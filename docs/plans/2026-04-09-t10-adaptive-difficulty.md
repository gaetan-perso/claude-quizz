# Plan : T10 — Difficulté Adaptative
Date : 2026-04-09
Objectif : Implémenter la sélection adaptative de la difficulté en mode solo (US-10)
Architecture : AdaptiveDifficultyService + QuizSession/SessionAnswer + API REST v1
Stack : Laravel 13 / PHP 8.3+ / MySQL / SQLite (tests)

---

## Contexte & décisions d'architecture

- Table `quiz_sessions` (pas `sessions` — évite le conflit avec le driver session Laravel)
- Modèle `QuizSession` (pas `Session` — évite le conflit avec la Facade Laravel)
- Auth : Laravel Sanctum (`auth:sanctum` middleware sur toutes les routes API v1)
- Tests : PHPUnit natif (Pest non installé dans ce projet)
- Fallback de difficulté : Hard → Medium → Easy (seulement le niveau calculé et en-dessous)
- Compteurs réinitialisés à 0 dès qu'un palier est franchi

---

## Tâche 1 — Installer Laravel Sanctum

**Agent** : backend-agent

**Fichiers concernés** :
- `composer.json` (modifié automatiquement par composer)
- `database/migrations/YYYY_MM_DD_create_personal_access_tokens_table.php` (créé par Sanctum)

**Commandes à exécuter dans Docker** :
```bash
docker compose exec app composer require laravel/sanctum
docker compose exec app php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
docker compose exec app php artisan migrate
```

**Commande de vérification** :
```bash
docker compose exec app php artisan route:list | grep sanctum
# Expected: pas d'erreur + sanctum routes présentes (ou vide si pas de routes sanctum publiées)
```

**Commit** : `chore(deps): install laravel/sanctum`

---

## Tâche 2 — Ajouter HasApiTokens au modèle User

**Agent** : backend-agent

**Fichiers concernés** :
- `app/Models/User.php` (modifier)

**Code complet** :
```php
<?php declare(strict_types=1);
namespace App\Models;

use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

final class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'role'              => UserRole::class,
    ];

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin();
    }
}
```

**Commande de vérification** :
```bash
docker compose exec app php artisan tinker --execute="echo (new App\Models\User)->createToken('test')->plainTextToken ? 'ok' : 'fail';"
# Expected: ok
```

**Commit** : `feat(auth): add HasApiTokens to User model`

---

## Tâche 3 — Migration quiz_sessions

**Agent** : backend-agent

**Fichiers concernés** :
- `database/migrations/2026_04_09_000001_create_quiz_sessions_table.php` (créer)

**Code complet** :
```php
<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quiz_sessions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('category_id')->constrained('categories')->cascadeOnDelete();
            $table->enum('status', ['active', 'completed', 'abandoned'])->default('active');
            $table->enum('current_difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->unsignedTinyInteger('consecutive_correct')->default(0);
            $table->unsignedTinyInteger('consecutive_wrong')->default(0);
            $table->unsignedSmallInteger('score')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_sessions');
    }
};
```

**Commande de vérification** :
```bash
docker compose exec app php artisan migrate --path=database/migrations/2026_04_09_000001_create_quiz_sessions_table.php
# Expected: Running migrations... 2026_04_09_000001_create_quiz_sessions_table ......... DONE
```

**Commit** : `feat(db): add quiz_sessions migration`

---

## Tâche 4 — Migration session_answers

**Agent** : backend-agent

**Fichiers concernés** :
- `database/migrations/2026_04_09_000002_create_session_answers_table.php` (créer)

**Code complet** :
```php
<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('session_answers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('session_id')->constrained('quiz_sessions')->cascadeOnDelete();
            $table->foreignUlid('question_id')->constrained('questions')->cascadeOnDelete();
            $table->foreignUlid('choice_id')->nullable()->constrained('choices')->nullOnDelete();
            $table->boolean('is_correct')->default(false);
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->index('session_id');
            $table->unique(['session_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_answers');
    }
};
```

**Commande de vérification** :
```bash
docker compose exec app php artisan migrate --path=database/migrations/2026_04_09_000002_create_session_answers_table.php
# Expected: Running migrations... 2026_04_09_000002_create_session_answers_table ......... DONE
```

**Commit** : `feat(db): add session_answers migration`

---

## Tâche 5 — Modèle QuizSession

**Agent** : backend-agent

**Fichiers concernés** :
- `app/Models/QuizSession.php` (créer)

**Code complet** :
```php
<?php declare(strict_types=1);
namespace App\Models;

use App\Enums\Difficulty;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class QuizSession extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'quiz_sessions';

    protected $fillable = [
        'user_id',
        'category_id',
        'status',
        'current_difficulty',
        'consecutive_correct',
        'consecutive_wrong',
        'score',
        'completed_at',
    ];

    protected $casts = [
        'current_difficulty'  => Difficulty::class,
        'consecutive_correct' => 'integer',
        'consecutive_wrong'   => 'integer',
        'score'               => 'integer',
        'completed_at'        => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SessionAnswer::class, 'session_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
```

**Commande de vérification** :
```bash
docker compose exec app php artisan tinker --execute="echo App\Models\QuizSession::class;"
# Expected: App\Models\QuizSession
```

**Commit** : `feat(model): add QuizSession model`

---

## Tâche 6 — Modèle SessionAnswer

**Agent** : backend-agent

**Fichiers concernés** :
- `app/Models/SessionAnswer.php` (créer)

**Code complet** :
```php
<?php declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SessionAnswer extends Model
{
    use HasUlids;

    protected $table = 'session_answers';

    protected $fillable = [
        'session_id',
        'question_id',
        'choice_id',
        'is_correct',
        'answered_at',
    ];

    protected $casts = [
        'is_correct'  => 'boolean',
        'answered_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(QuizSession::class, 'session_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function choice(): BelongsTo
    {
        return $this->belongsTo(Choice::class);
    }
}
```

**Commit** : `feat(model): add SessionAnswer model`

---

## Tâche 7 — Tests unitaires AdaptiveDifficultyService (TDD — red)

**Agent** : backend-agent

**Fichiers concernés** :
- `tests/Unit/AdaptiveDifficultyServiceTest.php` (créer)

**Code complet** :
```php
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
```

**Commande de vérification** :
```bash
docker compose exec app php artisan test tests/Unit/AdaptiveDifficultyServiceTest.php
# Expected: FAIL — App\Services\AdaptiveDifficultyService not found (red phase TDD)
```

**Commit** : `test(adaptive): add unit tests for AdaptiveDifficultyService (red)`

---

## Tâche 8 — Implémenter AdaptiveDifficultyService

**Agent** : backend-agent

**Fichiers concernés** :
- `app/Services/AdaptiveDifficultyService.php` (créer)

**Code complet** :
```php
<?php declare(strict_types=1);
namespace App\Services;

use App\Enums\Difficulty;
use App\Models\Question;
use App\Models\QuizSession;

final class AdaptiveDifficultyService
{
    private const int CONSECUTIVE_THRESHOLD = 3;

    /**
     * Calcule l'état de la session après une réponse.
     *
     * @return array{current_difficulty: Difficulty, consecutive_correct: int, consecutive_wrong: int}
     */
    public function applyAnswer(QuizSession $session, bool $isCorrect): array
    {
        if ($isCorrect) {
            $consecutiveCorrect = $session->consecutive_correct + 1;
            $consecutiveWrong   = 0;
        } else {
            $consecutiveCorrect = 0;
            $consecutiveWrong   = $session->consecutive_wrong + 1;
        }

        $difficulty = $session->current_difficulty;

        if ($consecutiveCorrect >= self::CONSECUTIVE_THRESHOLD) {
            $difficulty         = $this->upgrade($difficulty);
            $consecutiveCorrect = 0;
        } elseif ($consecutiveWrong >= self::CONSECUTIVE_THRESHOLD) {
            $difficulty       = $this->downgrade($difficulty);
            $consecutiveWrong = 0;
        }

        return [
            'current_difficulty'  => $difficulty,
            'consecutive_correct' => $consecutiveCorrect,
            'consecutive_wrong'   => $consecutiveWrong,
        ];
    }

    /**
     * Sélectionne la prochaine question non répondue dans la session.
     * Si la difficulté cible n'a plus de questions disponibles, repasse aux niveaux inférieurs.
     */
    public function selectNextQuestion(QuizSession $session): ?Question
    {
        $answeredIds = $session->answers()->pluck('question_id')->all();

        foreach ($this->fallbackOrder($session->current_difficulty) as $difficulty) {
            $question = Question::query()
                ->active()
                ->where('category_id', $session->category_id)
                ->forDifficulty($difficulty)
                ->whereNotIn('id', $answeredIds)
                ->inRandomOrder()
                ->first();

            if ($question !== null) {
                return $question;
            }
        }

        return null;
    }

    private function upgrade(Difficulty $difficulty): Difficulty
    {
        return match ($difficulty) {
            Difficulty::Easy   => Difficulty::Medium,
            Difficulty::Medium => Difficulty::Hard,
            Difficulty::Hard   => Difficulty::Hard,
        };
    }

    private function downgrade(Difficulty $difficulty): Difficulty
    {
        return match ($difficulty) {
            Difficulty::Easy   => Difficulty::Easy,
            Difficulty::Medium => Difficulty::Easy,
            Difficulty::Hard   => Difficulty::Medium,
        };
    }

    /** @return list<Difficulty> */
    private function fallbackOrder(Difficulty $difficulty): array
    {
        return match ($difficulty) {
            Difficulty::Hard   => [Difficulty::Hard, Difficulty::Medium, Difficulty::Easy],
            Difficulty::Medium => [Difficulty::Medium, Difficulty::Easy],
            Difficulty::Easy   => [Difficulty::Easy],
        };
    }
}
```

**Commande de vérification** :
```bash
docker compose exec app php artisan test tests/Unit/AdaptiveDifficultyServiceTest.php
# Expected: PASS — 13 tests, X assertions (green phase TDD)
```

**Commit** : `feat(service): implement AdaptiveDifficultyService`

---

## Tâche 9 — Tests Feature SessionController (TDD — red)

**Agent** : backend-agent

**Fichiers concernés** :
- `tests/Feature/SessionControllerTest.php` (créer)

**Code complet** :
```php
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
```

**Commande de vérification** :
```bash
docker compose exec app php artisan test tests/Feature/SessionControllerTest.php
# Expected: FAIL — routes /api/v1/sessions not found (red phase TDD)
```

**Commit** : `test(api): add feature tests for SessionController (red)`

---

## Tâche 10 — Implémenter SessionController

**Agent** : backend-agent

**Fichiers concernés** :
- `app/Http/Controllers/Api/V1/SessionController.php` (créer)

**Code complet** :
```php
<?php declare(strict_types=1);
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Choice;
use App\Models\QuizSession;
use App\Services\AdaptiveDifficultyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SessionController extends Controller
{
    public function __construct(
        private readonly AdaptiveDifficultyService $adaptiveService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'ulid', 'exists:categories,id'],
        ]);

        $session = QuizSession::create([
            'user_id'             => $request->user()->id,
            'category_id'         => $validated['category_id'],
            'status'              => 'active',
            'current_difficulty'  => 'medium',
            'consecutive_correct' => 0,
            'consecutive_wrong'   => 0,
            'score'               => 0,
        ]);

        return response()->json(['data' => $session], 201);
    }

    public function nextQuestion(Request $request, QuizSession $session): JsonResponse
    {
        abort_if($session->user_id !== $request->user()->id, 403);

        $question = $this->adaptiveService->selectNextQuestion($session);

        if ($question === null) {
            return response()->json(['data' => null, 'message' => 'Session terminée']);
        }

        return response()->json([
            'data' => [
                'question' => [
                    'id'                     => $question->id,
                    'text'                   => $question->text,
                    'difficulty'             => $question->difficulty->value,
                    'estimated_time_seconds' => $question->estimated_time_seconds,
                    'choices'                => $question->choices->map(fn (Choice $c) => [
                        'id'   => $c->id,
                        'text' => $c->text,
                    ])->values(),
                ],
                'current_difficulty' => $session->current_difficulty->value,
            ],
        ]);
    }

    public function answer(Request $request, QuizSession $session): JsonResponse
    {
        abort_if($session->user_id !== $request->user()->id, 403);
        abort_if(! $session->isActive(), 422, 'La session n\'est pas active.');

        $validated = $request->validate([
            'question_id' => ['required', 'ulid', 'exists:questions,id'],
            'choice_id'   => ['required', 'ulid', 'exists:choices,id'],
        ]);

        abort_if(
            $session->answers()->where('question_id', $validated['question_id'])->exists(),
            422,
            'Cette question a déjà été répondue.'
        );

        $choice    = Choice::findOrFail($validated['choice_id']);
        $isCorrect = $choice->is_correct;

        $session->answers()->create([
            'question_id' => $validated['question_id'],
            'choice_id'   => $validated['choice_id'],
            'is_correct'  => $isCorrect,
            'answered_at' => now(),
        ]);

        $update   = $this->adaptiveService->applyAnswer($session, $isCorrect);
        $newScore = $session->score + ($isCorrect ? 1 : 0);

        $session->update([
            'current_difficulty'  => $update['current_difficulty'],
            'consecutive_correct' => $update['consecutive_correct'],
            'consecutive_wrong'   => $update['consecutive_wrong'],
            'score'               => $newScore,
        ]);

        return response()->json([
            'data' => [
                'is_correct'         => $isCorrect,
                'current_difficulty' => $update['current_difficulty']->value,
                'score'              => $newScore,
            ],
        ]);
    }
}
```

**Commit** : `feat(controller): add SessionController with adaptive difficulty`

---

## Tâche 11 — Routes API v1 + enregistrement bootstrap

**Agent** : backend-agent

**Fichiers concernés** :
- `routes/api.php` (créer)
- `bootstrap/app.php` (modifier)

**Code complet — routes/api.php** :
```php
<?php declare(strict_types=1);

use App\Http\Controllers\Api\V1\SessionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {
    Route::post('sessions', [SessionController::class, 'store']);
    Route::get('sessions/{session}/next-question', [SessionController::class, 'nextQuestion']);
    Route::post('sessions/{session}/answers', [SessionController::class, 'answer']);
});
```

**Code complet — bootstrap/app.php** :
```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
```

**Commande de vérification** :
```bash
docker compose exec app php artisan route:list --path=api
# Expected:
#   POST   api/v1/sessions
#   GET    api/v1/sessions/{session}/next-question
#   POST   api/v1/sessions/{session}/answers
```

**Commit** : `feat(routes): register API v1 session routes`

---

## Tâche 12 — Vérification finale (green phase)

**Agent** : backend-agent

**Commande de vérification** :
```bash
docker compose exec app php artisan test tests/Unit/AdaptiveDifficultyServiceTest.php tests/Feature/SessionControllerTest.php
# Expected: PASS — tous les tests verts
```

**Commit** : `test(t10): all adaptive difficulty tests green ✓`

---

## Récapitulatif des fichiers créés/modifiés

| Fichier | Action |
|---|---|
| `app/Models/User.php` | modifier — ajouter `HasApiTokens` |
| `app/Models/QuizSession.php` | créer |
| `app/Models/SessionAnswer.php` | créer |
| `app/Services/AdaptiveDifficultyService.php` | créer |
| `app/Http/Controllers/Api/V1/SessionController.php` | créer |
| `routes/api.php` | créer |
| `bootstrap/app.php` | modifier — ajouter `api:` |
| `database/migrations/2026_04_09_000001_create_quiz_sessions_table.php` | créer |
| `database/migrations/2026_04_09_000002_create_session_answers_table.php` | créer |
| `tests/Unit/AdaptiveDifficultyServiceTest.php` | créer |
| `tests/Feature/SessionControllerTest.php` | créer |

---

## Choix d'exécution

**Option A — Subagent-driven** : dispatcher `adaptive-difficulty-agent` + `backend-agent` en parallèle, avec `testing-agent` après chaque couche
→ Recommandé si on veut une exécution robuste avec double review

**Option B — Exécution inline** : exécuter tâche par tâche avec checkpoints
→ Recommandé ici — feature mono-couche backend, plan complet, pas de coordination multi-agent nécessaire
