<?php declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\Difficulty;
use App\Jobs\GenerateQuestionsJob;
use App\Models\Category;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

final class GenerateQuestions extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';
    protected static string|\UnitEnum|null $navigationGroup = 'Bibliothèque';
    protected static ?string $navigationLabel = 'Générer via IA';
    protected static ?int $navigationSort = 3;
    protected string $view = 'filament.pages.generate-questions';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('topic')
                    ->label('Thème')
                    ->required()
                    ->placeholder('ex: Révolution française, Photosynthèse, SQL...'),
                Select::make('category_id')
                    ->label('Catégorie')
                    ->options(fn () => Category::active()->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                Select::make('difficulty')
                    ->label('Difficulté')
                    ->options(collect(Difficulty::cases())->mapWithKeys(
                        fn (Difficulty $d) => [$d->value => $d->label()]
                    ))
                    ->required(),
                TextInput::make('count')
                    ->label('Nombre de questions')
                    ->numeric()
                    ->default(5)
                    ->minValue(1)
                    ->maxValue(20),
            ]);
    }

    public function generate(): void
    {
        $data = $this->form->getState();

        $category = Category::findOrFail($data['category_id']);

        GenerateQuestionsJob::dispatch(
            topic: $data['topic'],
            categorySlug: $category->slug,
            difficulty: Difficulty::from($data['difficulty']),
            count: (int) $data['count'],
        );

        Notification::make()
            ->title("Génération lancée : {$data['count']} questions sur « {$data['topic']} »")
            ->body('Les questions apparaîtront dans la bibliothèque dans quelques instants.')
            ->success()
            ->send();
    }
}
