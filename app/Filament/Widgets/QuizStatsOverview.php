<?php declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Models\Question;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class QuizStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Questions actives', Question::active()->count())
                ->description('Dans la bibliothèque')
                ->icon('heroicon-o-question-mark-circle')
                ->color('success'),

            Stat::make('Questions IA générées', Question::where('source', 'ai_generated')->count())
                ->description('Générées automatiquement')
                ->icon('heroicon-o-sparkles')
                ->color('primary'),

            Stat::make('Catégories', Category::active()->count())
                ->icon('heroicon-o-tag')
                ->color('warning'),

            Stat::make('Utilisateurs', User::count())
                ->description(User::where('role', 'admin')->count() . ' admins')
                ->icon('heroicon-o-users')
                ->color('gray'),
        ];
    }
}
