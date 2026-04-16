<?php declare(strict_types=1);
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
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

    public function index(Request $request): JsonResponse
    {
        $sessions = $request->user()
            ->quizSessions()
            ->with('category')
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => $sessions->map(fn (QuizSession $s) => [
                'id'                 => $s->id,
                'status'             => $s->status,
                'score'              => $s->score,
                'max_questions'      => $s->max_questions,
                'current_difficulty' => $s->current_difficulty->value,
                'completed_at'       => $s->completed_at,
                'created_at'         => $s->created_at,
                'category_ids'       => $s->category_ids ?? ($s->category_id !== null ? [$s->category_id] : []),
                'category'           => $s->category !== null ? [
                    'id'   => $s->category->id,
                    'name' => $s->category->name,
                ] : null,
            ])->values(),
            'meta' => [
                'current_page' => $sessions->currentPage(),
                'last_page'    => $sessions->lastPage(),
                'total'        => $sessions->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id'    => ['nullable', 'ulid', 'exists:categories,id'],
            'category_ids'   => ['nullable', 'array', 'min:1'],
            'category_ids.*' => ['ulid', 'exists:categories,id'],
            'max_questions'  => ['sometimes', 'integer', 'min:1', 'max:40'],
        ]);

        // Résolution des catégories : category_ids prime sur category_id
        if (! empty($validated['category_ids'])) {
            $categoryIds = array_values(array_unique($validated['category_ids']));
            $categoryId  = $categoryIds[0];
        } elseif (! empty($validated['category_id'])) {
            $categoryId  = $validated['category_id'];
            $categoryIds = [$categoryId];
        } else {
            return response()->json([
                'message' => 'Au moins une catégorie est requise (category_id ou category_ids).',
                'errors'  => [
                    'category_id'  => ['Le champ category_id est requis si category_ids est absent.'],
                    'category_ids' => ['Le champ category_ids est requis si category_id est absent.'],
                ],
            ], 422);
        }

        $session = QuizSession::create([
            'user_id'             => $request->user()->id,
            'category_id'         => $categoryId,
            'category_ids'        => $categoryIds,
            'status'              => 'active',
            'current_difficulty'  => 'medium',
            'consecutive_correct' => 0,
            'consecutive_wrong'   => 0,
            'score'               => 0,
            'max_questions'       => $validated['max_questions'] ?? 20,
        ]);

        return response()->json(['data' => $session], 201);
    }

    public function show(Request $request, QuizSession $session): JsonResponse
    {
        abort_if($session->user_id !== $request->user()->id, 403);
        $session->load('category');

        return response()->json([
            'data' => [
                'id'                 => $session->id,
                'status'             => $session->status,
                'score'              => $session->score,
                'max_questions'      => $session->max_questions,
                'answered_count'     => $session->answers()->count(),
                'current_difficulty' => $session->current_difficulty->value,
                'completed_at'       => $session->completed_at,
                'created_at'         => $session->created_at,
                'category_ids'       => $session->category_ids ?? ($session->category_id !== null ? [$session->category_id] : []),
                'category'           => $session->category !== null ? [
                    'id'   => $session->category->id,
                    'name' => $session->category->name,
                ] : null,
            ],
        ]);
    }

    public function complete(Request $request, QuizSession $session): JsonResponse
    {
        abort_if($session->user_id !== $request->user()->id, 403);
        abort_if(! $session->isActive(), 422, 'La session n\'est pas active.');

        $session->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        return response()->json(['data' => ['status' => 'completed', 'score' => $session->score]]);
    }

    public function abandon(Request $request, QuizSession $session): JsonResponse
    {
        abort_if($session->user_id !== $request->user()->id, 403);
        abort_if(! $session->isActive(), 422, 'La session n\'est pas active.');

        $session->update(['status' => 'abandoned']);

        return response()->json(['data' => ['status' => 'abandoned', 'score' => $session->score]]);
    }

    public function nextQuestion(Request $request, QuizSession $session): JsonResponse
    {
        abort_if($session->user_id !== $request->user()->id, 403);

        // Vérifier si le nombre max de questions est atteint
        $answeredCount = $session->answers()->count();
        if ($answeredCount >= $session->max_questions) {
            return response()->json(['data' => null, 'message' => 'Nombre maximum de questions atteint']);
        }

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

        $choice        = Choice::with('question.choices')->findOrFail($validated['choice_id']);
        $isCorrect     = $choice->is_correct;
        $correctChoice = $choice->question->choices->firstWhere('is_correct', true);

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
                'correct_choice_id'  => $correctChoice?->id,
                'explanation'        => $choice->question->explanation,
            ],
        ]);
    }
}
