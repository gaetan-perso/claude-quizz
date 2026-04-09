<?php declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\Difficulty;
use App\Enums\QuestionSource;
use App\Enums\QuestionType;
use App\Filament\Resources\QuestionResource\Pages;
use App\Models\Question;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

final class QuestionResource extends Resource
{
    protected static ?string $model = Question::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-question-mark-circle';
    protected static string|\UnitEnum|null $navigationGroup = 'Bibliothèque';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Question')->schema([
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Textarea::make('text')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
                Select::make('difficulty')
                    ->options(collect(Difficulty::cases())->mapWithKeys(
                        fn(Difficulty $d) => [$d->value => $d->label()]
                    ))
                    ->required(),
                Select::make('type')
                    ->options([
                        QuestionType::MultipleChoice->value => 'QCM (4 choix)',
                        QuestionType::Open->value           => 'Réponse ouverte',
                    ])
                    ->required()
                    ->default(QuestionType::MultipleChoice->value)
                    ->live(),
                TextInput::make('estimated_time_seconds')
                    ->numeric()
                    ->default(30)
                    ->suffix('secondes'),
                TagsInput::make('tags')->separator(','),
                Toggle::make('is_active')->default(true),
            ]),

            Section::make('Choix de réponse')
                ->visible(fn (Get $get) => $get('type') === QuestionType::MultipleChoice->value)
                ->schema([
                    Repeater::make('choices')
                        ->relationship()
                        ->schema([
                            TextInput::make('text')->required()->columnSpan(3),
                            Toggle::make('is_correct')->label('Correcte'),
                        ])
                        ->columns(4)
                        ->minItems(4)
                        ->maxItems(4)
                        ->reorderable()
                        ->reorderableWithButtons(),
                ]),

            Section::make('Pédagogie')->schema([
                Textarea::make('explanation')
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
