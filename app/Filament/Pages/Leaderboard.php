<?php declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\LobbyParticipant;
use App\Models\QuizSession;
use App\Models\SessionAnswer;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use stdClass;

final class Leaderboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-trophy';
    protected static string|\UnitEnum|null $navigationGroup = 'Joueurs';
    protected static ?string $navigationLabel = 'Classement';
    protected static ?int $navigationSort = 1;
    protected string $view = 'filament.pages.leaderboard';

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $user instanceof User && $user->role === UserRole::Admin;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resetScores')
                ->label('Réinitialiser les scores')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Réinitialiser tous les scores')
                ->modalDescription('Cette opération est irréversible. Tous les scores seront remis à zéro, les réponses supprimées et les sessions réinitialisées.')
                ->modalSubmitActionLabel('Oui, réinitialiser')
                ->action(function (): void {
                    $sessionCount     = QuizSession::count();
                    $answerCount      = SessionAnswer::count();
                    $participantCount = LobbyParticipant::count();

                    DB::transaction(function () use ($answerCount, $sessionCount, $participantCount): void {
                        if ($answerCount > 0) {
                            SessionAnswer::query()->delete();
                        }

                        if ($sessionCount > 0) {
                            QuizSession::query()->update([
                                'score'               => 0,
                                'consecutive_correct' => 0,
                                'consecutive_wrong'   => 0,
                                'status'              => 'active',
                                'completed_at'        => null,
                            ]);
                        }

                        if ($participantCount > 0) {
                            LobbyParticipant::query()->update(['score' => 0]);
                        }
                    });

                    Notification::make()
                        ->title('Scores réinitialisés')
                        ->body(
                            "{$sessionCount} session(s), {$answerCount} réponse(s) et {$participantCount} participant(s) remis à zéro."
                        )
                        ->success()
                        ->send();
                }),
        ];
    }

    /**
     * @return LengthAwarePaginator<stdClass>
     */
    public function getLeaderboard(): LengthAwarePaginator
    {
        return QuizSession::query()
            ->where('quiz_sessions.status', 'completed')
            ->join('users', 'users.id', '=', 'quiz_sessions.user_id')
            ->selectRaw(
                'quiz_sessions.user_id,
                 users.name,
                 SUM(quiz_sessions.score)    AS total_score,
                 COUNT(*)                    AS sessions_count,
                 MAX(quiz_sessions.score)    AS best_score,
                 MAX(quiz_sessions.completed_at) AS last_session_at'
            )
            ->groupBy('quiz_sessions.user_id', 'users.name')
            ->orderByDesc('total_score')
            ->paginate(25);
    }
}
