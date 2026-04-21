<?php declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\LobbyParticipant;
use App\Models\QuizSession;
use App\Models\SessionAnswer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class ResetScoresCommand extends Command
{
    protected $signature = 'scores:reset
                            {--force : Bypass la confirmation interactive (utile en CI)}';

    protected $description = 'Remet à zéro tous les scores des joueurs (quiz_sessions, session_answers, lobby_participants)';

    public function handle(): int
    {
        $this->newLine();
        $this->line('<fg=yellow>Opération : remise à zéro des scores</>');
        $this->newLine();

        $sessionCount     = QuizSession::count();
        $answerCount      = SessionAnswer::count();
        $participantCount = LobbyParticipant::count();

        $this->table(
            ['Table', 'Enregistrements concernés'],
            [
                ['quiz_sessions (score, statut, progression)', $sessionCount],
                ['session_answers (historique des réponses)', $answerCount],
                ['lobby_participants (score multijoueur)', $participantCount],
            ],
        );

        $this->newLine();

        if (! $this->option('force')) {
            $confirmed = $this->confirm(
                'Cette opération est irréversible. Confirmes-tu la remise à zéro ?',
                false,
            );

            if (! $confirmed) {
                $this->line('<fg=yellow>Opération annulée.</>');
                return self::FAILURE;
            }
        }

        $this->newLine();
        $this->line('Réinitialisation en cours...');

        DB::transaction(function () use ($sessionCount, $answerCount, $participantCount): void {
            // 1. Supprimer toutes les réponses (historique complet des sessions)
            if ($answerCount > 0) {
                SessionAnswer::query()->delete();
                $this->line("  <fg=green>✓</> {$answerCount} réponse(s) supprimée(s) (session_answers)");
            }

            // 2. Réinitialiser les sessions : score, progression, statut → active
            if ($sessionCount > 0) {
                QuizSession::query()->update([
                    'score'               => 0,
                    'consecutive_correct' => 0,
                    'consecutive_wrong'   => 0,
                    'status'              => 'active',
                    'completed_at'        => null,
                ]);
                $this->line("  <fg=green>✓</> {$sessionCount} session(s) réinitialisée(s) (quiz_sessions)");
            }

            // 3. Remettre à zéro les scores des participants en lobby
            if ($participantCount > 0) {
                LobbyParticipant::query()->update(['score' => 0]);
                $this->line("  <fg=green>✓</> {$participantCount} participant(s) réinitialisé(s) (lobby_participants)");
            }
        });

        $this->newLine();
        $this->info('Remise à zéro terminée.');
        $this->table(
            ['Table', 'Enregistrements traités'],
            [
                ['quiz_sessions', $sessionCount],
                ['session_answers', $answerCount],
                ['lobby_participants', $participantCount],
            ],
        );

        return self::SUCCESS;
    }
}
