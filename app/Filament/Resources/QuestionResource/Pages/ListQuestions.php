<?php declare(strict_types=1);

namespace App\Filament\Resources\QuestionResource\Pages;

use App\Filament\Resources\QuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListQuestions extends ListRecords
{
    protected static string $resource = QuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('generate_ai')
                ->label('Générer via IA')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->url(fn () => route('filament.admin.pages.generate-questions')),
        ];
    }
}
