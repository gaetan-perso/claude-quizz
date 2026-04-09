<?php declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\Difficulty;
use App\Enums\QuestionSource;
use App\Enums\QuestionType;
use App\Filament\Resources\QuestionResource\Pages;
use App\Models\Question;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class QuestionResource extends Resource
{
    protected static ?string $model = Question::class;
    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';
    protected static ?string $navigationGroup = 'Bibliothèque';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Question')->schema([
                Forms\Components\Select::make('category_id')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Textarea::make('text')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Select::make('difficulty')
                    ->options(collect(Difficulty::cases())->mapWithKeys(
                        fn(Difficulty $d) => [$d->value => $d->label()]
                    ))
                    ->required(),
                Forms\Components\Select::make('type')
                    ->options([
                        QuestionType::MultipleChoice->value => 'QCM (4 choix)',
                        QuestionType::Open->value           => 'Réponse ouverte',
                    ])
                    ->required()
                    ->default(QuestionType::MultipleChoice->value),
                Forms\Components\TextInput::make('estimated_time_seconds')
                    ->numeric()
                    ->default(30)
                    ->suffix('secondes'),
                Forms\Components\TagsInput::make('tags')->separator(','),
                Forms\Components\Toggle::make('is_active')->default(true),
            ]),

            Forms\Components\Section::make('Choix de réponse')
                ->visible(fn (Forms\Get $get) => $get('type') === QuestionType::MultipleChoice->value)
                ->schema([
                    Forms\Components\Repeater::make('choices')
                        ->relationship()
                        ->schema([
                            Forms\Components\TextInput::make('text')->required()->columnSpan(3),
                            Forms\Components\Toggle::make('is_correct')->label('Correcte'),
                        ])
                        ->columns(4)
                        ->minItems(4)
                        ->maxItems(4)
                        ->reorderable()
                        ->reorderableWithButtons(),
                ]),

            Forms\Components\Section::make('Pédagogie')->schema([
                Forms\Components\Textarea::make('explanation')
                    ->rows(4)
                    ->columnSpanFull()
                    ->helperText('Explication affichée après la réponse'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category.name')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('text')
                    ->limit(60)
                    ->searchable()
                    ->tooltip(fn ($record) => $record->text),
                Tables\Columns\TextColumn::make('difficulty')
                    ->badge()
                    ->formatStateUsing(fn (Difficulty $state) => $state->label())
                    ->color(fn (Difficulty $state) => $state->color()),
                Tables\Columns\TextColumn::make('source')
                    ->badge()
                    ->color(fn (QuestionSource $state) => $state === QuestionSource::AiGenerated ? 'primary' : 'gray'),
                Tables\Columns\TextColumn::make('choices_count')
                    ->counts('choices')
                    ->label('Choix'),
                Tables\Columns\ToggleColumn::make('is_active'),
                Tables\Columns\TextColumn::make('created_at')->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('difficulty')
                    ->options(collect(Difficulty::cases())->mapWithKeys(
                        fn(Difficulty $d) => [$d->value => $d->label()]
                    )),
                Tables\Filters\SelectFilter::make('source')
                    ->options([
                        QuestionSource::Manual->value      => 'Manuel',
                        QuestionSource::AiGenerated->value => 'IA générée',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListQuestions::route('/'),
            'create' => Pages\CreateQuestion::route('/create'),
            'edit'   => Pages\EditQuestion::route('/{record}/edit'),
        ];
    }
}
