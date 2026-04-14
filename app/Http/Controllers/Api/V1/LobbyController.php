<?php declare(strict_types=1);
namespace App\Http\Controllers\Api\V1;

use App\Enums\Difficulty;
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
            'category_id' => ['required', 'ulid', 'exists:categories,id'],
            'max_players' => ['sometimes', 'integer', 'min:2', 'max:8'],
        ]);

        $lobby = Lobby::create([
            'host_user_id' => $request->user()->id,
            'category_id'  => $validated['category_id'],
            'status'       => 'waiting',
            'code'         => Lobby::generateCode(),
            'max_players'  => $validated['max_players'] ?? 4,
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

        return response()->json(['data' => $this->format($lobby)]);
    }

    public function leave(Request $request, Lobby $lobby): JsonResponse
    {
        abort_if(! $lobby->isWaiting(), 422, 'Impossible de quitter un lobby en cours.');
        abort_if($lobby->host_user_id === $request->user()->id, 422, 'L\'hôte ne peut pas quitter le lobby.');

        $participant = $lobby->participants()->where('user_id', $request->user()->id)->first();
        abort_if($participant === null, 422, 'Vous n\'êtes pas dans ce lobby.');

        $participant->delete();

        return response()->json(['data' => null]);
    }

    public function start(Request $request, Lobby $lobby): JsonResponse
    {
        abort_if($lobby->host_user_id !== $request->user()->id, 403);
        abort_if(! $lobby->isWaiting(), 422, 'Le lobby n\'est pas en attente.');
        abort_if($lobby->participants()->count() < 2, 422, 'Il faut au moins 2 joueurs pour démarrer.');

        $lobby->update(['status' => 'in_progress', 'started_at' => now()]);

        foreach ($lobby->participants as $participant) {
            QuizSession::create([
                'user_id'             => $participant->user_id,
                'category_id'         => $lobby->category_id,
                'status'              => 'active',
                'current_difficulty'  => Difficulty::Medium->value,
                'consecutive_correct' => 0,
                'consecutive_wrong'   => 0,
                'score'               => 0,
            ]);
        }

        $lobby->load(['category', 'participants.user']);

        return response()->json(['data' => $this->format($lobby)]);
    }

    private function format(Lobby $lobby): array
    {
        return [
            'id'           => $lobby->id,
            'code'         => $lobby->code,
            'status'       => $lobby->status->value,
            'max_players'  => $lobby->max_players,
            'host_user_id' => $lobby->host_user_id,
            'category'     => [
                'id'   => $lobby->category->id,
                'name' => $lobby->category->name,
            ],
            'participants' => $lobby->participants->map(fn (LobbyParticipant $p) => [
                'user_id'  => $p->user_id,
                'name'     => $p->user->name,
                'score'    => $p->score,
                'is_ready' => $p->is_ready,
            ])->values(),
            'started_at'   => $lobby->started_at,
        ];
    }
}
