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
