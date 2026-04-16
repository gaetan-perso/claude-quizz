<?php declare(strict_types=1);

namespace App\Services;

use App\Events\LobbyGameCompleted;
use App\Events\LobbyQuestionReady;
use App\Models\Choice;
use App\Models\Lobby;
use App\Models\LobbyParticipant;
use App\Models\Question;
use App\Models\QuizSession;

final class LobbyQuestionService
{
    /**
     * Avance à la question suivante ou termine la partie.
     * Retourne true si la partie continue, false si terminée.
     */
    public function advance(Lobby $lobby): bool
    {
        $lobby->loadMissing(['participants.user']);

        // Récupérer la liste commune depuis n'importe quelle session du lobby
        $session = QuizSession::where('lobby_id', $lobby->id)->first();
        if ($session === null || empty($session->question_ids)) {
            $this->completeGame($lobby);
            return false;
        }

        $questionIds = $session->question_ids;
        $nextIndex   = $lobby->current_question_index + 1;

        if ($nextIndex >= count($questionIds)) {
            $this->completeGame($lobby);
            return false;
        }

        $lobby->update(['current_question_index' => $nextIndex]);

        $question = Question::with(['choices', 'category'])->find($questionIds[$nextIndex]);
        if ($question === null) {
            $this->completeGame($lobby);
            return false;
        }

        LobbyQuestionReady::dispatch(
            lobbyId:        $lobby->id,
            questionIndex:  $nextIndex,
            totalQuestions: count($questionIds),
            question:       $this->formatQuestion($question),
            startedAt:      now()->toIsoString(),
        );

        return true;
    }

    /**
     * Vérifie si tous les joueurs ont répondu à la question courante.
     */
    public function allPlayersAnswered(Lobby $lobby): bool
    {
        $session = QuizSession::where('lobby_id', $lobby->id)->first();
        if ($session === null || empty($session->question_ids)) {
            return false;
        }

        $currentQuestionId = $session->question_ids[$lobby->current_question_index] ?? null;
        if ($currentQuestionId === null) {
            return false;
        }

        $participantCount = $lobby->participants()->count();
        $answeredCount    = QuizSession::where('lobby_id', $lobby->id)
            ->whereHas('answers', fn ($q) => $q->where('question_id', $currentQuestionId))
            ->count();

        return $answeredCount >= $participantCount;
    }

    public function completeGame(Lobby $lobby): void
    {
        $lobby->update(['status' => 'completed', 'completed_at' => now()]);

        QuizSession::where('lobby_id', $lobby->id)
            ->where('status', 'active')
            ->update(['status' => 'completed', 'completed_at' => now()]);

        $sessions = QuizSession::where('lobby_id', $lobby->id)->get()->keyBy('user_id');

        $leaderboard = $lobby->participants->map(fn (LobbyParticipant $p) => [
            'user_id' => $p->user_id,
            'name'    => $p->user->name,
            'score'   => $sessions->get($p->user_id)?->score ?? 0,
        ])->sortByDesc('score')->values()->toArray();

        LobbyGameCompleted::dispatch(lobbyId: $lobby->id, leaderboard: $leaderboard);
    }

    public function formatQuestion(Question $question): array
    {
        return [
            'id'                     => $question->id,
            'text'                   => $question->text,
            'estimated_time_seconds' => $question->estimated_time_seconds,
            'category'               => $question->category?->name,
            'choices'                => $question->choices->map(fn (Choice $c) => [
                'id'   => $c->id,
                'text' => $c->text,
            ])->values()->toArray(),
        ];
    }
}
