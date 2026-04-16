<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\Difficulty;
use App\Events\LobbyPlayerJoined;
use App\Events\LobbyPlayerLeft;
use App\Events\LobbyQuestionReady;
use App\Events\LobbyStarted;
use App\Http\Controllers\Controller;
use App\Models\Lobby;
use App\Models\LobbyParticipant;
use App\Models\Question;
use App\Models\QuizSession;
use App\Services\LobbyQuestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LobbyController extends Controller
{
    public function __construct(
        private readonly LobbyQuestionService $questionService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id'    => ['nullable', 'ulid', 'exists:categories,id'],
            'category_ids'   => ['nullable', 'array', 'min:1'],
            'category_ids.*' => ['ulid', 'exists:categories,id'],
            'max_players'    => ['sometimes', 'integer', 'min:2', 'max:50'],
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

        $lobby = Lobby::create([
            'host_user_id'  => $request->user()->id,
            'category_id'   => $categoryId,
            'category_ids'  => $categoryIds,
            'status'        => 'waiting',
            'code'          => Lobby::generateCode(),
            'max_players'   => $validated['max_players'] ?? 10,
            'max_questions' => $validated['max_questions'] ?? 10,
        ]);

        $lobby->participants()->create([
            'user_id'   => $request->user()->id,
            'joined_at' => now(),
        ]);

        $lobby->load(['category', 'participants.user']);

        return response()->json(['data' => $this->format($lobby)], 201);
    }

    public function show(Lobby $lobby): JsonResponse
    {
        $lobby->load(['category', 'participants.user']);

        $data = $this->format($lobby);

        if ($lobby->started_at !== null) {
            $sessions = QuizSession::where('lobby_id', $lobby->id)
                ->get()
                ->keyBy('user_id');

            // session_map pour la navigation (lobby en cours)
            if ($lobby->status->value === 'in_progress') {
                $data['session_map'] = $sessions->map(fn (QuizSession $s) => $s->id)->toArray();
            }

            // Classement calculé depuis les sessions réelles (scores à jour)
            $data['leaderboard'] = $lobby->participants
                ->map(fn (LobbyParticipant $p) => [
                    'user_id' => $p->user_id,
                    'name'    => $p->user->name,
                    'score'   => $sessions->get($p->user_id)?->score ?? 0,
                ])
                ->sortByDesc('score')
                ->values()
                ->toArray();
        }

        return response()->json(['data' => $data]);
    }

    public function join(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $lobby = Lobby::where('code', strtoupper($validated['code']))->firstOrFail();

        abort_if(! $lobby->isWaiting(), 422, 'Le lobby n\'est plus disponible.');
        abort_if($lobby->participants()->count() >= $lobby->max_players, 422, 'Le lobby est plein.');
        abort_if(
            $lobby->participants()->where('user_id', $request->user()->id)->exists(),
            422,
            'Vous êtes déjà dans ce lobby.'
        );

        $lobby->participants()->create([
            'user_id'   => $request->user()->id,
            'joined_at' => now(),
        ]);

        $lobby->load(['category', 'participants.user']);

        LobbyPlayerJoined::dispatch(
            lobbyId:      $lobby->id,
            userId:       $request->user()->id,
            userName:     $request->user()->name,
            participants: $this->formatParticipants($lobby),
        );

        return response()->json(['data' => $this->format($lobby)]);
    }

    public function leave(Request $request, Lobby $lobby): JsonResponse
    {
        abort_if(! $lobby->isWaiting(), 422, 'Impossible de quitter un lobby en cours.');
        abort_if($lobby->host_user_id === $request->user()->id, 422, 'L\'hôte ne peut pas quitter le lobby.');

        $participant = $lobby->participants()->where('user_id', $request->user()->id)->first();
        abort_if($participant === null, 422, 'Vous n\'êtes pas dans ce lobby.');

        $participant->delete();

        $lobby->load(['participants.user']);
        LobbyPlayerLeft::dispatch(
            lobbyId:      $lobby->id,
            userId:       $request->user()->id,
            participants: $this->formatParticipants($lobby),
        );

        return response()->json(['data' => null]);
    }

    public function start(Request $request, Lobby $lobby): JsonResponse
    {
        abort_if($lobby->host_user_id !== $request->user()->id, 403);
        abort_if(! $lobby->isWaiting(), 422, 'Le lobby n\'est pas en attente.');
        // Minimum 1 joueur (l'hôte peut démarrer seul pour tester)
        abort_if($lobby->participants()->count() < 1, 422, 'Aucun joueur dans le lobby.');

        $lobby->update([
            'status'                 => 'in_progress',
            'started_at'             => now(),
            'current_question_index' => 0,
        ]);

        $categoryIds = $lobby->category_ids ?? [$lobby->category_id];
        $maxQ        = $lobby->max_questions ?? 10;

        // Générer UNE liste commune de questions pour tous les participants
        $questionIds = Question::query()
            ->active()
            ->whereIn('category_id', $categoryIds)
            ->inRandomOrder()
            ->limit($maxQ)
            ->pluck('id')
            ->toArray();

        $createdIds = [];
        foreach ($lobby->participants as $participant) {
            $session = QuizSession::create([
                'user_id'             => $participant->user_id,
                'lobby_id'            => $lobby->id,
                'category_id'         => $lobby->category_id,
                'category_ids'        => $categoryIds,
                'question_ids'        => $questionIds,
                'status'              => 'active',
                'current_difficulty'  => Difficulty::Medium->value,
                'consecutive_correct' => 0,
                'consecutive_wrong'   => 0,
                'score'               => 0,
                'max_questions'       => count($questionIds),
            ]);
            $createdIds[] = $session->id;
        }

        $lobby->load(['category', 'participants.user']);

        $sessions   = QuizSession::whereIn('id', $createdIds)->get()->keyBy('user_id');
        $sessionMap = $sessions->map(fn (QuizSession $s) => $s->id)->toArray();

        // 1. Envoyer la session_map à chaque joueur
        LobbyStarted::dispatch(
            lobbyId:    $lobby->id,
            sessionMap: $sessionMap,
        );

        // 2. Broadcaster la première question
        if (! empty($questionIds)) {
            $firstQuestion = Question::with(['choices', 'category'])->find($questionIds[0]);
            if ($firstQuestion !== null) {
                LobbyQuestionReady::dispatch(
                    lobbyId:        $lobby->id,
                    questionIndex:  0,
                    totalQuestions: count($questionIds),
                    question:       $this->questionService->formatQuestion($firstQuestion),
                    startedAt:      now()->toIsoString(),
                );
            }
        }

        return response()->json([
            'data' => array_merge($this->format($lobby), [
                'session_map' => $sessionMap,
            ]),
        ]);
    }

    public function advance(Request $request, Lobby $lobby): JsonResponse
    {
        abort_if($lobby->host_user_id !== $request->user()->id, 403);
        abort_if($lobby->status->value !== 'in_progress', 422, 'La partie n\'est pas en cours.');

        $lobby->loadMissing('participants');
        $continues = $this->questionService->advance($lobby);

        return response()->json(['data' => ['continues' => $continues]]);
    }

    public function complete(Request $request, Lobby $lobby): JsonResponse
    {
        abort_if($lobby->host_user_id !== $request->user()->id, 403, 'Seul l\'hôte peut terminer la partie.');
        abort_if($lobby->status->value !== 'in_progress', 422, 'Le lobby n\'est pas en cours.');

        $lobby->loadMissing('participants.user');
        $this->questionService->completeGame($lobby);

        return response()->json(['data' => ['status' => 'completed']]);
    }

    private function format(Lobby $lobby): array
    {
        return [
            'id'            => $lobby->id,
            'code'          => $lobby->code,
            'status'        => $lobby->status->value,
            'max_players'   => $lobby->max_players,
            'max_questions' => $lobby->max_questions,
            'host_user_id'  => $lobby->host_user_id,
            'category'      => [
                'id'   => $lobby->category->id,
                'name' => $lobby->category->name,
            ],
            'category_ids'  => $lobby->category_ids ?? [$lobby->category_id],
            'participants'  => $lobby->participants->map(fn (LobbyParticipant $p) => [
                'user_id'  => $p->user_id,
                'name'     => $p->user->name,
                'score'    => $p->score,
                'is_ready' => $p->is_ready,
            ])->values(),
            'started_at'    => $lobby->started_at,
        ];
    }

    private function formatParticipants(Lobby $lobby): array
    {
        return $lobby->participants->map(fn (LobbyParticipant $p) => [
            'user_id' => $p->user_id,
            'name'    => $p->user->name,
            'score'   => $p->score,
        ])->values()->toArray();
    }
}
