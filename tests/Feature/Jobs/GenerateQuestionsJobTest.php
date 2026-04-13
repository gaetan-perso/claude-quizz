<?php declare(strict_types=1);

use App\Enums\Difficulty;
use App\Enums\QuestionSource;
use App\Enums\QuestionType;
use App\Jobs\GenerateQuestionsJob;
use App\Models\Category;
use App\Models\Choice;
use App\Models\Question;
use App\Contracts\QuestionGeneratorContract;
use App\Services\QuestionGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Construit un payload JSON valide simulant la réponse de Claude.
 */
function validClaudeApiResponse(int $count = 2): array
{
    $questions = [];

    for ($i = 1; $i <= $count; $i++) {
        $questions[] = [
            'text'                   => "Question numéro {$i} ?",
            'explanation'            => "Explication de la réponse {$i}.",
            'estimated_time_seconds' => 20,
            'choices'                => [
                ['text' => "Bonne réponse {$i}",     'is_correct' => true],
                ['text' => "Mauvaise réponse {$i}a",  'is_correct' => false],
                ['text' => "Mauvaise réponse {$i}b",  'is_correct' => false],
                ['text' => "Mauvaise réponse {$i}c",  'is_correct' => false],
            ],
        ];
    }

    // Simule le format de réponse de l'API Anthropic REST
    return [
        'id'      => 'msg_test_123',
        'type'    => 'message',
        'role'    => 'assistant',
        'content' => [
            [
                'type' => 'text',
                'text' => json_encode(['questions' => $questions], JSON_THROW_ON_ERROR),
            ],
        ],
        'model'        => 'claude-opus-4-6',
        'stop_reason'  => 'end_turn',
        'usage'        => ['input_tokens' => 100, 'output_tokens' => 200],
    ];
}

/**
 * Instancie le service avec la vraie HttpFactory (qui utilise Http::fake()).
 */
function makeService(): QuestionGeneratorService
{
    return new QuestionGeneratorService(
        http: app(HttpFactory::class),
    );
}

// ---------------------------------------------------------------------------
// Tests du service QuestionGeneratorService
// ---------------------------------------------------------------------------

describe('QuestionGeneratorService', function () {

    it('génère et persiste des questions et leurs choix en base', function () {
        Http::fake([
            'api.anthropic.com/*' => Http::response(validClaudeApiResponse(2), 200),
        ]);

        $category = Category::factory()->create(['name' => 'Histoire', 'slug' => 'histoire']);
        $service  = makeService();

        $created = $service->generate(
            topic:      'Histoire de France',
            category:   $category,
            difficulty: Difficulty::Medium,
            count:      2,
        );

        expect($created)->toBe(2);

        $questions = Question::where('category_id', $category->id)->get();
        expect($questions)->toHaveCount(2);

        $firstQuestion = $questions->first();
        expect($firstQuestion)
            ->not->toBeNull()
            ->and($firstQuestion->text)->toBe('Question numéro 1 ?')
            ->and($firstQuestion->difficulty)->toBe(Difficulty::Medium)
            ->and($firstQuestion->type)->toBe(QuestionType::MultipleChoice)
            ->and($firstQuestion->source)->toBe(QuestionSource::AiGenerated)
            ->and($firstQuestion->explanation)->toBe('Explication de la réponse 1.')
            ->and($firstQuestion->estimated_time_seconds)->toBe(20)
            ->and($firstQuestion->is_active)->toBeTrue();

        $choices = Choice::where('question_id', $firstQuestion->id)->get();
        expect($choices)->toHaveCount(4);

        $correctChoices = $choices->where('is_correct', true);
        expect($correctChoices)->toHaveCount(1);
        expect($correctChoices->first()->text)->toBe('Bonne réponse 1');
    });

    it('envoie la requête avec les bons headers Anthropic', function () {
        Http::fake([
            'api.anthropic.com/*' => Http::response(validClaudeApiResponse(1), 200),
        ]);

        $category = Category::factory()->create();
        $service  = makeService();

        $service->generate(
            topic:      'Physique',
            category:   $category,
            difficulty: Difficulty::Easy,
            count:      1,
        );

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'api.anthropic.com/v1/messages')
                && $request->hasHeader('anthropic-version')
                && $request->hasHeader('x-api-key');
        });
    });

    it('retourne 0 et log une erreur si l\'API Anthropic retourne une erreur HTTP', function () {
        Http::fake([
            'api.anthropic.com/*' => Http::response(['error' => 'Overloaded'], 529),
        ]);

        Log::shouldReceive('error')
            ->once()
            ->withArgs(fn (string $msg) => str_contains($msg, 'indisponible'));

        $category = Category::factory()->create();
        $service  = makeService();

        $created = $service->generate(
            topic:      'Science',
            category:   $category,
            difficulty: Difficulty::Easy,
            count:      3,
        );

        expect($created)->toBe(0);
        expect(Question::count())->toBe(0);
    });

    it('retourne 0 et log une erreur si Claude retourne du JSON invalide', function () {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => 'Ce n\'est pas du JSON valide {{{']],
            ], 200),
        ]);

        Log::shouldReceive('error')
            ->once()
            ->withArgs(fn (string $msg) => str_contains($msg, 'JSON invalide'));

        $category = Category::factory()->create();
        $service  = makeService();

        $created = $service->generate(
            topic:      'Mathématiques',
            category:   $category,
            difficulty: Difficulty::Hard,
            count:      1,
        );

        expect($created)->toBe(0);
        expect(Question::count())->toBe(0);
    });

    it('nettoie les blocs markdown dans la réponse de Claude', function () {
        $questionsJson = json_encode(['questions' => [
            [
                'text'                   => 'Question avec markdown ?',
                'explanation'            => 'Explication.',
                'estimated_time_seconds' => 25,
                'choices'                => [
                    ['text' => 'Bonne réponse',  'is_correct' => true],
                    ['text' => 'Mauvaise 1',      'is_correct' => false],
                    ['text' => 'Mauvaise 2',      'is_correct' => false],
                    ['text' => 'Mauvaise 3',      'is_correct' => false],
                ],
            ],
        ]], JSON_THROW_ON_ERROR);

        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => "```json\n{$questionsJson}\n```"]],
            ], 200),
        ]);

        $category = Category::factory()->create();
        $service  = makeService();

        $created = $service->generate(
            topic:      'Géographie',
            category:   $category,
            difficulty: Difficulty::Easy,
            count:      1,
        );

        expect($created)->toBe(1);
        expect(Question::count())->toBe(1);
    });

    it('ignore les questions avec un format invalide et persiste les valides', function () {
        $mixedJson = json_encode([
            'questions' => [
                // Question valide
                [
                    'text'                   => 'Question valide ?',
                    'explanation'            => 'Explication.',
                    'estimated_time_seconds' => 30,
                    'choices'                => [
                        ['text' => 'Bonne réponse', 'is_correct' => true],
                        ['text' => 'Mauvaise 1',     'is_correct' => false],
                        ['text' => 'Mauvaise 2',     'is_correct' => false],
                        ['text' => 'Mauvaise 3',     'is_correct' => false],
                    ],
                ],
                // Question invalide (2 bonnes réponses)
                [
                    'text'    => 'Question invalide ?',
                    'choices' => [
                        ['text' => 'Choix A', 'is_correct' => true],
                        ['text' => 'Choix B', 'is_correct' => true],
                        ['text' => 'Choix C', 'is_correct' => false],
                        ['text' => 'Choix D', 'is_correct' => false],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => $mixedJson]],
            ], 200),
        ]);

        Log::shouldReceive('warning')->once();

        $category = Category::factory()->create();
        $service  = makeService();

        $created = $service->generate(
            topic:      'Test',
            category:   $category,
            difficulty: Difficulty::Easy,
            count:      2,
        );

        // Seule la question valide est persistée
        expect($created)->toBe(1);
        expect(Question::count())->toBe(1);
        expect(Question::first()->text)->toBe('Question valide ?');
    });

    it('retourne 0 si le réseau est inaccessible', function () {
        Http::fake([
            'api.anthropic.com/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
            },
        ]);

        Log::shouldReceive('error')
            ->once()
            ->withArgs(fn (string $msg) => str_contains($msg, 'indisponible'));

        $category = Category::factory()->create();
        $service  = makeService();

        $created = $service->generate(
            topic:      'Biologie',
            category:   $category,
            difficulty: Difficulty::Medium,
            count:      2,
        );

        expect($created)->toBe(0);
    });

});

// ---------------------------------------------------------------------------
// Tests du job GenerateQuestionsJob
// ---------------------------------------------------------------------------

describe('GenerateQuestionsJob', function () {

    it('appelle le service avec les bons paramètres pour une catégorie existante', function () {
        $category = Category::factory()->create(['slug' => 'sciences']);

        $mockService = Mockery::mock(QuestionGeneratorContract::class);
        $mockService
            ->shouldReceive('generate')
            ->once()
            ->withArgs(function (string $topic, Category $cat, Difficulty $diff, int $count) {
                return $topic === 'Physique quantique'
                    && $cat->slug === 'sciences'
                    && $diff === Difficulty::Hard
                    && $count === 3;
            })
            ->andReturn(3);

        $job = new GenerateQuestionsJob(
            topic:        'Physique quantique',
            categorySlug: 'sciences',
            difficulty:   Difficulty::Hard,
            count:        3,
        );

        $job->handle($mockService);
    });

    it('log le succès avec le nombre de questions créées', function () {
        $category = Category::factory()->create(['slug' => 'geographie']);

        $mockService = Mockery::mock(QuestionGeneratorContract::class);
        $mockService
            ->shouldReceive('generate')
            ->once()
            ->andReturn(4);

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function (string $message, array $context) {
                return str_contains($message, 'succès') && $context['created'] === 4;
            });

        $job = new GenerateQuestionsJob(
            topic:        'Capitales du monde',
            categorySlug: 'geographie',
            difficulty:   Difficulty::Easy,
            count:        4,
        );

        $job->handle($mockService);
    });

    it('n\'appelle pas le service si la catégorie n\'existe pas', function () {
        $mockService = Mockery::mock(QuestionGeneratorContract::class);
        $mockService->shouldNotReceive('generate');

        $job = new GenerateQuestionsJob(
            topic:        'Test',
            categorySlug: 'categorie-inexistante',
            difficulty:   Difficulty::Easy,
            count:        5,
        );

        // $this->fail() dans un job appelé hors queue ne throw pas — il marque
        // le job comme échoué via l'event dispatcher. On vérifie juste que
        // generate() n'est jamais appelé.
        $job->handle($mockService);
    });

    it('relance l\'exception pour le retry si le service lève une erreur', function () {
        $category = Category::factory()->create(['slug' => 'histoire']);

        $mockService = Mockery::mock(QuestionGeneratorContract::class);
        $mockService
            ->shouldReceive('generate')
            ->once()
            ->andThrow(new \RuntimeException('Erreur réseau'));

        Log::shouldReceive('error')->once();

        $job = new GenerateQuestionsJob(
            topic:        'Révolution française',
            categorySlug: 'histoire',
            difficulty:   Difficulty::Medium,
            count:        5,
        );

        expect(fn () => $job->handle($mockService))
            ->toThrow(\RuntimeException::class, 'Erreur réseau');
    });

});
