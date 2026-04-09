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
