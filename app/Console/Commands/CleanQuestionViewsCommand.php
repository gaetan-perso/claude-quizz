<?php declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\QuestionView;
use Illuminate\Console\Command;

final class CleanQuestionViewsCommand extends Command
{
    protected $signature = 'question-views:clean
                            {--days=30 : Nombre de jours au-delà desquels les vues sont supprimées}';

    protected $description = 'Supprime les entrées question_views plus vieilles que N jours (défaut : 30)';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        if ($days <= 0) {
            $this->error('Le paramètre --days doit être un entier positif.');
            return self::FAILURE;
        }

        $cutoff  = now()->subDays($days);
        $deleted = QuestionView::query()
            ->where('seen_at', '<', $cutoff)
            ->delete();

        $this->info("question-views:clean — {$deleted} entrée(s) supprimée(s) (antérieures à {$cutoff->toDateString()}).");

        return self::SUCCESS;
    }
}
