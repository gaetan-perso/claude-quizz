# Plan : Backoffice Admin — Quiz Multijoueur
Date : 2026-04-07
Objectif : Implémenter un backoffice admin complet pour gérer questions, catégories, utilisateurs, sessions et déclencher la génération IA.
Architecture : Filament PHP v3 monté sur le backend Laravel existant (pas de frontend séparé)
Stack : Laravel 13 / PHP 8.3+ / Filament v3 / MySQL / Redis

---

## Décisions d'architecture

- **Framework backoffice** : Filament PHP v3 — admin panel Laravel natif, compatible L13, zéro JS framework séparé
- **Guard admin** : colonne `role` enum(`player`, `admin`) sur la table `users`
- **Panel Filament** : monté sur `/admin`, guard `web`, middleware `auth` + `role:admin`
- **Génération IA** : Action Filament → dispatch `GenerateQuestionsJob` en queue
- **Stats sessions** : Widgets Filament (StatsOverview + Chart) — pas de realtime dans le backoffice
- **Réponses correctes** : jamais exposées dans les payloads API publics, visibles uniquement dans le backoffice admin

---

## Ordre d'exécution

Les tâches sont numérotées dans l'ordre strict des dépendances.
Les tâches marquées **(parallèle)** peuvent être exécutées simultanément.

---

## Tâche 1 — Migration : table `categories`

**Agent** : backend-agent

**Fichiers concernés** :
- `database/migrations/2026_04_07_000001_create_categories_table.php` (créer)

**Code complet** :
```php
<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->string('icon', 50)->nullable();
            $table->string('color', 7)->default('#6366f1'); // hex color
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
```

**Commande de vérification** :
```bash
php artisan migrate --path=database/migrations/2026_04_07_000001_create_categories_table.php
# Expected output : Migrating: 2026_04_07_000001_create_categories_table ... Done
```

**Commit** : `feat(db): create categories table`

---

## Tâche 2 — Migration : table `questions`

**Agent** : backend-agent

**Fichiers concernés** :
- `database/migrations/2026_04_07_000002_create_questions_table.php` (créer)

**Code complet** :
```php
<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('category_id')->constrained('categories')->cascadeOnDelete();
            $table->text('text');
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->enum('type', ['multiple_choice', 'open'])->default('multiple_choice');
            $table->text('explanation')->nullable();
            $table->json('tags')->nullable();
            $table->unsignedSmallInteger('estimated_time_seconds')->default(30);
            $table->boolean('is_active')->default(true);
            $table->enum('source', ['manual', 'ai_generated'])->default('manual');
            $table->timestamps();
            $table->softDeletes();

            $table->index('category_id');
            $table->index('difficulty');
            $table->index('is_active');
            $table->index(['difficulty', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
```

**Commit** : `feat(db): create questions table`

---

## Tâche 3 — Migration : table `choices`

**Agent** : backend-agent

**Fichiers concernés** :
- `database/migrations/2026_04_07_000003_create_choices_table.php` (créer)

**Code complet** :
```php
<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('choices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('question_id')->constrained('questions')->cascadeOnDelete();
            $table->string('text', 500);
            $table->boolean('is_correct')->default(false);
            $table->unsignedTinyInteger('order')->default(0);
            $table->timestamps();

            $table->index('question_id');
            $table->index(['question_id', 'is_correct']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('choices');
    }
};
```

**Commit** : `feat(db): create choices table`

---

## Tâche 4 — Migration : colonne `role` sur `users`

**Agent** : backend-agent

**Fichiers concernés** :
- `database/migrations/2026_04_07_000004_add_role_to_users_table.php` (créer)

**Code complet** :
```php
<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['player', 'admin'])->default('player')->after('email');
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
```

**Commit** : `feat(db): add role enum to users table`

---

## Tâche 5 — Enum `Difficulty` et enum `UserRole`

**Agent** : backend-agent

**Fichiers concernés** :
- `app/Enums/Difficulty.php` (créer)
- `app/Enums/UserRole.php` (créer)
- `app/Enums/QuestionType.php` (créer)
- `app/Enums/QuestionSource.php` (créer)

**Code complet** :
```php
// app/Enums/Difficulty.php
<?php declare(strict_types=1);
namespace App\Enums;

enum Difficulty: string
{
    case Easy   = 'easy';
    case Medium = 'medium';
    case Hard   = 'hard';

    public function label(): string
    {
        return match($this) {
            self::Easy   => 'Facile',
            self::Medium => 'Moyen',
            self::Hard   => 'Difficile',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Easy   => 'success',
            self::Medium => 'warning',
            self::Hard   => 'danger',
        };
    }
}
```

```php
// app/Enums/UserRole.php
<?php declare(strict_types=1);
namespace App\Enums;

enum UserRole: string
{
    case Player = 'player';
    case Admin  = 'admin';
}
```

```php
// app/Enums/QuestionType.php
<?php declare(strict_types=1);
namespace App\Enums;

enum QuestionType: string
{
    case MultipleChoice = 'multiple_choice';
    case Open           = 'open';
}
```

```php
// app/Enums/QuestionSource.php
<?php declare(strict_types=1);
namespace App\Enums;

enum QuestionSource: string
{
    case Manual      = 'manual';
    case AiGenerated = 'ai_generated';
}
```

**Commande de vérification** :
```bash
php artisan tinker --execute="echo App\Enums\Difficulty::Hard->label();"
# Expected output : Difficile
```

**Commit** : `feat(enums): add Difficulty, UserRole, QuestionType, QuestionSource`

---

## Tâche 6 — Model `Category`

**Agent** : backend-agent

**Fichiers concernés** :
- `app/Models/Category.php` (créer)
- `database/factories/CategoryFactory.php` (créer)

**Code complet** :
```php
// app/Models/Category.php
<?php declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Category extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = ['name', 'slug', 'icon', 'color', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->where('is_active', true);
    }
}
```

```php
// database/factories/CategoryFactory.php
<?php declare(strict_types=1);
namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);
        return [
            'name'      => ucfirst($name),
            'slug'      => Str::slug($name),
            'icon'      => $this->faker->randomElement(['🎯', '🌍', '🔬', '🎨', '⚽']),
            'color'     => $this->faker->hexColor(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
```

**Commit** : `feat(model): add Category model with factory`

---

## Tâche 7 — Model `Question` + Model `Choice`

**Agent** : backend-agent

**Fichiers concernés** :
- `app/Models/Question.php` (créer)
- `app/Models/Choice.php` (créer)
- `database/factories/QuestionFactory.php` (créer)
- `database/factories/ChoiceFactory.php` (créer)

**Code complet** :
```php
// app/Models/Question.php
<?php declare(strict_types=1);
namespace App\Models;

use App\Enums\Difficulty;
use App\Enums\QuestionSource;
use App\Enums\QuestionType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Question extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'category_id', 'text', 'difficulty', 'type', 'explanation',
        'tags', 'estimated_time_seconds', 'is_active', 'source',
    ];

    protected $casts = [
        'difficulty'             => Difficulty::class,
        'type'                   => QuestionType::class,
        'source'                 => QuestionSource::class,
        'tags'                   => 'array',
        'is_active'              => 'boolean',
        'estimated_time_seconds' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function choices(): HasMany
    {
        return $this->hasMany(Choice::class)->orderBy('order');
    }

    public function correctChoice(): HasMany
    {
        return $this->hasMany(Choice::class)->where('is_correct', true);
    }

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeForDifficulty(\Illuminate\Database\Eloquent\Builder $query, Difficulty $difficulty): void
    {
        $query->where('difficulty', $difficulty->value);
    }
}
```

```php
// app/Models/Choice.php
<?php declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Choice extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = ['question_id', 'text', 'is_correct', 'order'];

    protected $casts = ['is_correct' => 'boolean', 'order' => 'integer'];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
```

```php
// database/factories/QuestionFactory.php
<?php declare(strict_types=1);
namespace Database\Factories;

use App\Enums\Difficulty;
use App\Enums\QuestionSource;
use App\Enums\QuestionType;
use App\Models\Category;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

final class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        return [
            'category_id'            => Category::factory(),
            'text'                   => $this->faker->sentence() . ' ?',
            'difficulty'             => $this->faker->randomElement(Difficulty::cases()),
            'type'                   => QuestionType::MultipleChoice,
            'explanation'            => $this->faker->paragraph(),
            'tags'                   => [$this->faker->word(), $this->faker->word()],
            'estimated_time_seconds' => $this->faker->numberBetween(15, 60),
            'is_active'              => true,
            'source'                 => QuestionSource::Manual,
        ];
    }

    public function aiGenerated(): static
    {
        return $this->state(['source' => QuestionSource::AiGenerated]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
```

```php
// database/factories/ChoiceFactory.php
<?php declare(strict_types=1);
namespace Database\Factories;

use App\Models\Choice;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ChoiceFactory extends Factory
{
    protected $model = Choice::class;

    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'text'        => $this->faker->sentence(3),
            'is_correct'  => false,
            'order'       => 0,
        ];
    }

    public function correct(): static
    {
        return $this->state(['is_correct' => true]);
    }
}
```

**Commit** : `feat(model): add Question and Choice models with factories`

---

## Tâche 8 — Model `User` : ajout du rôle

**Agent** : backend-agent

**Fichiers concernés** :
- `app/Models/User.php` (modifier)

**Code complet** — ajouter dans le model User existant :
```php
// Ajouter dans $fillable :
'role',

// Ajouter dans $casts :
'role' => \App\Enums\UserRole::class,

// Ajouter la méthode :
public function isAdmin(): bool
{
    return $this->role === \App\Enums\UserRole::Admin;
}
```

**Commit** : `feat(model): add role cast and isAdmin() to User`

---

## Tâche 9 — Installation Filament v3

**Agent** : backend-agent

**Fichiers concernés** :
- `composer.json` (modifier)
- `app/Providers/Filament/AdminPanelProvider.php` (créer via artisan)

**Commandes** :
```bash
composer require filament/filament:"^3.0"
php artisan filament:install --panels
# Choisir : panel id = admin, guard = web, path = admin
```

**Configuration du panel** dans `app/Providers/Filament/AdminPanelProvider.php` :
```php
<?php declare(strict_types=1);
namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

final class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors(['primary' => Color::Indigo])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([Authenticate::class]);
    }
}
```

**Ajouter dans `app/Models/User.php`** (interface Filament) :
```php
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

// implements FilamentUser

public function canAccessPanel(Panel $panel): bool
{
    return $this->isAdmin();
}
```

**Commande de vérification** :
```bash
php artisan route:list | grep admin
# Expected output : GET|HEAD  admin/login ...
```

**Commit** : `feat(backoffice): install Filament v3 admin panel`

---

## Tâche 10 — Resource Filament : `CategoryResource`

**Agent** : backend-agent

**Fichiers concernés** :
- `app/Filament/Resources/CategoryResource.php` (créer)
- `app/Filament/Resources/CategoryResource/Pages/ListCategories.php` (créer)
- `app/Filament/Resources/CategoryResource/Pages/CreateCategory.php` (créer)
- `app/Filament/Resources/CategoryResource/Pages/EditCategory.php` (créer)

**Code complet** :
```php
<?php declare(strict_types=1);
namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

final class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Bibliothèque';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(100)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Forms\Set $set, ?string $state) =>
                    $set('slug', Str::slug($state ?? ''))
                ),
            Forms\Components\TextInput::make('slug')
                ->required()
                ->maxLength(100)
                ->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('icon')
                ->maxLength(50)
                ->placeholder('🎯'),
            Forms\Components\ColorPicker::make('color')
                ->default('#6366f1'),
            Forms\Components\Toggle::make('is_active')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('icon')->label(''),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('slug')->color('gray'),
                Tables\Columns\ColorColumn::make('color'),
                Tables\Columns\TextColumn::make('questions_count')
                    ->counts('questions')
                    ->label('Questions')
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')->label('Active'),
                Tables\Columns\TextColumn::make('created_at')->since()->sortable(),
            ])
            ->filters([
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
            'index'  => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit'   => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
```

```php
// Pages/ListCategories.php
<?php declare(strict_types=1);
namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
```

```php
// Pages/CreateCategory.php
<?php declare(strict_types=1);
namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;
}
```

```php
// Pages/EditCategory.php
<?php declare(strict_types=1);
namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
```

**Commit** : `feat(backoffice): add CategoryResource with CRUD`

---

## Tâche 11 — Resource Filament : `QuestionResource` (liste + filtres)

**Agent** : backend-agent

**Fichiers concernés** :
- `app/Filament/Resources/QuestionResource.php` (créer)

**Code complet** :
```php
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
                Tables\Columns\BadgeColumn::make('difficulty')
                    ->formatStateUsing(fn (Difficulty $state) => $state->label())
                    ->colors([
                        'success' => Difficulty::Easy->value,
                        'warning' => Difficulty::Medium->value,
                        'danger'  => Difficulty::Hard->value,
                    ]),
                Tables\Columns\BadgeColumn::make('source')
                    ->colors(['primary' => QuestionSource::AiGenerated->value]),
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
```

**Commit** : `feat(backoffice): add QuestionResource with CRUD and filters`

---

## Tâche 12 — Pages pour `QuestionResource`

**Agent** : backend-agent

**Fichiers concernés** :
- `app/Filament/Resources/QuestionResource/Pages/ListQuestions.php` (créer)
- `app/Filament/Resources/QuestionResource/Pages/CreateQuestion.php` (créer)
- `app/Filament/Resources/QuestionResource/Pages/EditQuestion.php` (créer)

**Code complet** :
```php
// ListQuestions.php
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
                ->url(route('filament.admin.pages.generate-questions')),
        ];
    }
}
```

```php
// CreateQuestion.php
<?php declare(strict_types=1);
namespace App\Filament\Resources\QuestionResource\Pages;

use App\Filament\Resources\QuestionResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateQuestion extends CreateRecord
{
    protected static string $resource = QuestionResource::class;
}
```

```php
// EditQuestion.php
<?php declare(strict_types=1);
namespace App\Filament\Resources\QuestionResource\Pages;

use App\Filament\Resources\QuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditQuestion extends EditRecord
{
    protected static string $resource = QuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
```

**Commit** : `feat(backoffice): add QuestionResource pages`

---

## Tâche 13 — Page Filament : Génération IA de questions

**Agent** : backend-agent

**Fichiers concernés** :
- `app/Filament/Pages/GenerateQuestions.php` (créer)

**Code complet** :
```php
<?php declare(strict_types=1);
namespace App\Filament\Pages;

use App\Enums\Difficulty;
use App\Jobs\GenerateQuestionsJob;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

final class GenerateQuestions extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Bibliothèque';
    protected static ?string $navigationLabel = 'Générer via IA';
    protected static ?int    $navigationSort  = 3;
    protected static string  $view            = 'filament.pages.generate-questions';

    public ?string $topic       = null;
    public ?string $category_id = null;
    public ?string $difficulty  = null;
    public int     $count       = 5;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('topic')
                    ->label('Thème')
                    ->required()
                    ->placeholder('ex: Révolution française, Photosynthèse, SQL...'),
                Forms\Components\Select::make('category_id')
                    ->label('Catégorie')
                    ->options(Category::active()->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('difficulty')
                    ->label('Difficulté')
                    ->options(collect(Difficulty::cases())->mapWithKeys(
                        fn(Difficulty $d) => [$d->value => $d->label()]
                    ))
                    ->required(),
                Forms\Components\TextInput::make('count')
                    ->label('Nombre de questions')
                    ->numeric()
                    ->default(5)
                    ->minValue(1)
                    ->maxValue(20),
            ])
            ->statePath('data');
    }

    public function generate(): void
    {
        $data = $this->form->getState();

        $category = Category::findOrFail($data['category_id']);

        GenerateQuestionsJob::dispatch(
            topic:        $data['topic'],
            categorySlug: $category->slug,
            difficulty:   Difficulty::from($data['difficulty']),
            count:        (int) $data['count'],
        );

        Notification::make()
            ->title("Génération lancée : {$data['count']} questions sur « {$data['topic']} »")
            ->body('Les questions apparaîtront dans la bibliothèque dans quelques instants.')
            ->success()
            ->send();
    }
}
```

**Vue blade** `resources/views/filament/pages/generate-questions.blade.php` :
```blade
<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Générer des questions via l'IA</x-slot>
        <x-slot name="description">
            Claude génère automatiquement des QCM de qualité pédagogique à partir d'un thème.
        </x-slot>

        <form wire:submit="generate">
            {{ $this->form }}

            <div class="mt-6">
                <x-filament::button type="submit" icon="heroicon-o-sparkles">
                    Lancer la génération
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</x-filament-panels::page>
```

**Commit** : `feat(backoffice): add AI question generation page`

---

## Tâche 14 — Resource Filament : `UserResource`

**Agent** : backend-agent

**Fichiers concernés** :
- `app/Filament/Resources/UserResource.php` (créer)
- `app/Filament/Resources/UserResource/Pages/ListUsers.php` (créer)
- `app/Filament/Resources/UserResource/Pages/EditUser.php` (créer)

**Code complet** :
```php
<?php declare(strict_types=1);
namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon  = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?int    $navigationSort  = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->maxLength(255),
            Forms\Components\TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
            Forms\Components\Select::make('role')
                ->options([
                    UserRole::Player->value => 'Joueur',
                    UserRole::Admin->value  => 'Administrateur',
                ])
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\BadgeColumn::make('role')
                    ->colors(['warning' => UserRole::Admin->value]),
                Tables\Columns\TextColumn::make('created_at')->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        UserRole::Player->value => 'Joueur',
                        UserRole::Admin->value  => 'Administrateur',
                    ]),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'edit'  => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
```

```php
// Pages/ListUsers.php
<?php declare(strict_types=1);
namespace App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\ListRecords;
final class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;
}
```

```php
// Pages/EditUser.php
<?php declare(strict_types=1);
namespace App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\EditRecord;
final class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;
}
```

**Commit** : `feat(backoffice): add UserResource (role management)`

---

## Tâche 15 — Widgets Dashboard : statistiques

**Agent** : backend-agent

**Fichiers concernés** :
- `app/Filament/Widgets/QuizStatsOverview.php` (créer)

**Code complet** :
```php
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
```

**Commit** : `feat(backoffice): add QuizStatsOverview dashboard widget`

---

## Tâche 16 — Seeder admin + seeder catégories de base

**Agent** : backend-agent

**Fichiers concernés** :
- `database/seeders/AdminUserSeeder.php` (créer)
- `database/seeders/CategorySeeder.php` (créer)
- `database/seeders/DatabaseSeeder.php` (modifier)

**Code complet** :
```php
// AdminUserSeeder.php
<?php declare(strict_types=1);
namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@quiz.local'],
            [
                'name'     => 'Admin Quiz',
                'password' => Hash::make('password'),
                'role'     => UserRole::Admin,
            ]
        );
    }
}
```

```php
// CategorySeeder.php
<?php declare(strict_types=1);
namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Histoire',      'icon' => '🏛️',  'color' => '#8b5cf6'],
            ['name' => 'Géographie',    'icon' => '🌍',  'color' => '#3b82f6'],
            ['name' => 'Sciences',      'icon' => '🔬',  'color' => '#10b981'],
            ['name' => 'Informatique',  'icon' => '💻',  'color' => '#6366f1'],
            ['name' => 'Littérature',   'icon' => '📚',  'color' => '#f59e0b'],
            ['name' => 'Sport',         'icon' => '⚽',  'color' => '#ef4444'],
            ['name' => 'Cinéma',        'icon' => '🎬',  'color' => '#ec4899'],
            ['name' => 'Musique',       'icon' => '🎵',  'color' => '#14b8a6'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['slug' => Str::slug($category['name'])],
                array_merge($category, ['is_active' => true])
            );
        }
    }
}
```

```php
// DatabaseSeeder.php — ajouter dans run() :
$this->call([
    AdminUserSeeder::class,
    CategorySeeder::class,
]);
```

**Commande de vérification** :
```bash
php artisan db:seed --class=AdminUserSeeder
php artisan db:seed --class=CategorySeeder
# Expected output : Database seeding completed successfully.
```

**Commit** : `feat(db): add admin and category seeders`

---

## Tâche 17 — Tests Feature : CategoryResource

**Agent** : testing-agent

**Fichiers concernés** :
- `tests/Feature/Backoffice/CategoryResourceTest.php` (créer)

**Code complet** :
```php
<?php declare(strict_types=1);

use App\Filament\Resources\CategoryResource;
use App\Models\Category;
use App\Models\User;
use App\Enums\UserRole;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($this->admin);
});

it('lists categories in the backoffice', function () {
    Category::factory()->count(3)->create();

    livewire(CategoryResource\Pages\ListCategories::class)
        ->assertCanSeeTableRecords(Category::all());
});

it('admin can create a category', function () {
    livewire(CategoryResource\Pages\CreateCategory::class)
        ->fillForm([
            'name'      => 'Astronomie',
            'slug'      => 'astronomie',
            'color'     => '#000000',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Category::where('slug', 'astronomie')->exists())->toBeTrue();
});

it('blocks non-admin from accessing backoffice', function () {
    $player = User::factory()->create(['role' => UserRole::Player]);
    $this->actingAs($player);

    $this->get('/admin')->assertForbidden();
});
```

**Commande de vérification** :
```bash
vendor/bin/pest tests/Feature/Backoffice/CategoryResourceTest.php
# Expected output : PASS (3 tests, 5 assertions)
```

**Commit** : `test(backoffice): add CategoryResource feature tests`

---

## Tâche 18 — Tests Feature : QuestionResource + génération IA

**Agent** : testing-agent

**Fichiers concernés** :
- `tests/Feature/Backoffice/QuestionResourceTest.php` (créer)

**Code complet** :
```php
<?php declare(strict_types=1);

use App\Enums\Difficulty;
use App\Enums\UserRole;
use App\Filament\Pages\GenerateQuestions;
use App\Filament\Resources\QuestionResource;
use App\Jobs\GenerateQuestionsJob;
use App\Models\Category;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->admin    = User::factory()->create(['role' => UserRole::Admin]);
    $this->category = Category::factory()->create();
    $this->actingAs($this->admin);
});

it('lists questions with filters', function () {
    Question::factory()->count(5)->create(['category_id' => $this->category->id]);

    livewire(QuestionResource\Pages\ListQuestions::class)
        ->assertCanSeeTableRecords(Question::all());
});

it('admin can create a question manually', function () {
    livewire(QuestionResource\Pages\CreateQuestion::class)
        ->fillForm([
            'category_id'            => $this->category->id,
            'text'                   => 'Quelle est la capitale de la France ?',
            'difficulty'             => Difficulty::Easy->value,
            'type'                   => 'multiple_choice',
            'estimated_time_seconds' => 20,
            'is_active'              => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Question::where('text', 'Quelle est la capitale de la France ?')->exists())->toBeTrue();
});

it('dispatches GenerateQuestionsJob when AI generation is submitted', function () {
    Queue::fake();

    livewire(GenerateQuestions::class)
        ->fillForm([
            'topic'       => 'Histoire de France',
            'category_id' => $this->category->id,
            'difficulty'  => Difficulty::Medium->value,
            'count'       => 5,
        ])
        ->call('generate');

    Queue::assertPushed(GenerateQuestionsJob::class, function ($job) {
        return $job->topic === 'Histoire de France' && $job->count === 5;
    });
});
```

**Commande de vérification** :
```bash
vendor/bin/pest tests/Feature/Backoffice/QuestionResourceTest.php
# Expected output : PASS (3 tests, 6 assertions)
```

**Commit** : `test(backoffice): add QuestionResource and AI generation tests`

---

## Checklist de livraison

- [ ] Tâches 1-4 : toutes les migrations passent (`php artisan migrate`)
- [ ] Tâche 5 : tous les enums resolvent sans erreur PHPStan
- [ ] Tâches 6-8 : `php artisan tinker` → factories fonctionnelles
- [ ] Tâche 9 : `/admin/login` accessible
- [ ] Tâches 10-15 : toutes les pages Filament s'affichent sans erreur
- [ ] Tâche 16 : seeders exécutables, login `admin@quiz.local` / `password` fonctionne
- [ ] Tâches 17-18 : `vendor/bin/pest tests/Feature/Backoffice/ --coverage --min=85`
- [ ] PHPStan level 9 : `vendor/bin/phpstan analyse app/Filament app/Models app/Enums --level=9`

---

## Option d'exécution

**Option A — Subagent-driven** (recommandé) :
Dispatcher `backend-agent` pour les tâches 1-16 en séquence, puis `testing-agent` pour les tâches 17-18.

**Option B — Exécution inline** :
Exécuter tâche par tâche avec checkpoint après chaque commit.
