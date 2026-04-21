<?php declare(strict_types=1);
namespace App\Services;

use App\Enums\Difficulty;
use App\Models\Question;
use App\Models\QuestionView;
use App\Models\QuizSession;
use Illuminate\Support\Facades\DB;

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
     * Priorise les questions jamais vues par le joueur, puis celles vues le moins souvent.
     * Si la difficulté cible n'a plus de questions disponibles, repasse aux niveaux inférieurs.
     */
    /** Nb de jours pendant lesquels une question est en "cooldown" après avoir été vue. */
    private const int COOLDOWN_DAYS = 3;

    public function selectNextQuestion(QuizSession $session): ?Question
    {
        $answeredIds = $session->answers()->pluck('question_id')->all();
        $userId      = $session->user_id;

        // Utilise category_ids si disponible, sinon repli sur category_id (rétrocompatibilité)
        $categoryIds = $session->category_ids ?? ($session->category_id !== null ? [$session->category_id] : []);

        foreach ($this->fallbackOrder($session->current_difficulty) as $difficulty) {
            // Sous-requête : nombre de vues par question pour cet utilisateur
            $viewCountSub = DB::table('question_views')
                ->selectRaw('question_id, COUNT(*) as views_count, MAX(seen_at) as last_seen_at')
                ->where('user_id', $userId)
                ->groupBy('question_id');

            $baseQuery = Question::query()
                ->active()
                ->whereIn('category_id', $categoryIds)
                ->forDifficulty($difficulty)
                ->whereNotIn('id', $answeredIds)
                ->leftJoinSub($viewCountSub, 'qv', 'questions.id', '=', 'qv.question_id')
                ->select('questions.*')
                ->selectRaw('COALESCE(qv.views_count, 0) as views_count')
                ->selectRaw('qv.last_seen_at');

            // Tentative 1 : questions hors cooldown (jamais vues ou vues il y a plus de N jours)
            $question = (clone $baseQuery)
                ->where(fn ($q) => $q
                    ->whereNull('qv.last_seen_at')
                    ->orWhere('qv.last_seen_at', '<', now()->subDays(self::COOLDOWN_DAYS))
                )
                ->orderBy('views_count', 'asc')
                ->inRandomOrder()
                ->first();

            // Tentative 2 : fallback si toutes les questions sont en cooldown
            $question ??= (clone $baseQuery)
                ->orderBy('qv.last_seen_at', 'asc') // la moins récemment vue en premier
                ->first();

            if ($question !== null) {
                QuestionView::create([
                    'user_id'     => $userId,
                    'question_id' => $question->id,
                    'seen_at'     => now(),
                ]);

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
            Difficulty::Medium => [Difficulty::Medium, Difficulty::Easy, Difficulty::Hard],
            Difficulty::Easy   => [Difficulty::Easy, Difficulty::Medium, Difficulty::Hard],
        };
    }
}
