<?php declare(strict_types=1);
namespace App\Http\Controllers\Api\V1;

use App\Enums\Difficulty;
use App\Events\LobbyGameCompleted;
use App\Events\LobbyPlayerJoined;
use App\Events\LobbyPlayerLeft;
use App\Events\LobbyStarted;
use App\Http\Controllers\Controller;
use App\Models\Lobby;
use App\Models\LobbyParticipant;
use App\Models\QuizSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LobbyController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id'    => ['nullable', 'ulid', 'exists:categories,id'],
            'category_ids'   => ['nullable', 'array', 'min:1'],
            'category_ids.*' => ['ulid', 'exists:categories,id'],
            'max_players'    => ['sometimes', 'integer', 'min:2', 'max:8'],
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
            'max_players'   => $validated['max_players'] ?? 4,
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

        return response()->json(['data' => $this->format($lobby)]);
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

        $lobby->update(['status' => 'in_progress', 'started_at' => now()]);

        $categoryIds = $lobby->category_ids ?? [$lobby->category_id];

        $createdIds = [];
        foreach ($lobby->participants as $participant) {
            $session = QuizSession::create([
                'user_id'             => $participant->user_id,
                'category_id'         => $lobby->category_id,
                'category_ids'        => $categoryIds,
                'status'              => 'active',
                'current_difficulty'  => Difficulty::Medium->value,
                'consecutive_correct' => 0,
                'consecutive_wrong'   => 0,
                'score'               => 0,
                'max_questions'       => $lobby->max_questions ?? 10,
            ]);
            $createdIds[] = $session->id;
        }

        $lobby->load(['category', 'participants.user']);

        $sessions   = QuizSession::whereIn('id', $createdIds)->get()->keyBy('user_id');
        $sessionMap = $sessions->map(fn (QuizSession $s) => $s->id)->toArray();

        LobbyStarted::dispatch(
            lobbyId:    $lobby->id,
            sessionMap: $sessionMap,
        );

        return response()->json(['data' => $this->format($lobby)]);
    }

    public function complete(Request $request, Lobby $lobby): JsonResponse
    {
        abort_if($lobby->host_user_id !== $request->user()->id, 403, 'Seul l\'hôte peut terminer la partie.');
        abort_if($lobby->status->value !== 'in_progress', 422, 'Le lobby n\'est pas en cours.');

        $lobby->update(['status' => 'completed', 'completed_at' => now()]);

        // Compléter toutes les sessions actives des participants
        QuizSession::whereIn('user_id', $lobby->participants->pluck('user_id'))
            ->where('status', 'active')
            ->update(['status' => 'completed', 'completed_at' => now()]);

        // Construire le classement final en filtrant sur les sessions créées après le démarrage du lobby
        $leaderboard = $lobby->participants->load('user')
            ->map(fn (LobbyParticipant $p) => [
                'user_id' => $p->user_id,
                'name'    => $p->user->name,
                'score'   => QuizSession::where('user_id', $p->user_id)
                    ->where('created_at', '>=', $lobby->started_at)
                    ->orderByDesc('created_at')
                    ->value('score') ?? 0,
            ])
            ->sortByDesc('score')
            ->values()
            ->toArray();

        LobbyGameCompleted::dispatch(
            lobbyId:     $lobby->id,
            leaderboard: $leaderboard,
        );

        return response()->json(['data' => ['leaderboard' => $leaderboard]]);
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
