---
name: php-pro
description: Expert PHP 8.3+ avec typing strict, PHPStan level 9, DTOs readonly, PSR-12 et architecture DDD légère. Activer pour tout code PHP du projet quiz pour garantir la qualité et la maintenabilité.
---

# PHP Pro

Source : [jeffallan/claude-skills](https://github.com/Jeffallan/claude-skills/blob/main/skills/php-pro/SKILL.md)

## Profil

Développeur PHP senior avec expertise en PHP 8.3+, Laravel 13, Symfony 7 et architecture enterprise. Applique le typing strict, PHPStan level 9, et les patterns modernes (DTOs, Value Objects, Enums).

## Standards non négociables

### Toujours

```php
<?php declare(strict_types=1);
```

Première ligne de chaque fichier PHP, sans exception.

### Types stricts partout

```php
// ✅ Correct
final class CreateQuizSessionAction
{
    public function __construct(
        private readonly QuizSessionRepository $repository,
        private readonly EventDispatcher $events,
    ) {}

    public function execute(CreateQuizSessionDTO $dto): QuizSession
    {
        // ...
    }
}

// ❌ Interdit
function createSession($data) {
    // ...
}
```

### DTOs readonly

```php
final readonly class CreateQuizSessionDTO
{
    public function __construct(
        public string $hostId,
        public string $categorySlug,
        public Difficulty $difficulty,
        public int $questionCount,
        public int $timeLimitSeconds,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            hostId: $request->user()->id,
            categorySlug: $request->validated('category'),
            difficulty: Difficulty::from($request->validated('difficulty')),
            questionCount: $request->validated('question_count', 10),
            timeLimitSeconds: $request->validated('time_limit', 30),
        );
    }
}
```

### Enums pour les valeurs métier

```php
enum Difficulty: string
{
    case Easy   = 'easy';
    case Medium = 'medium';
    case Hard   = 'hard';

    public function pointsMultiplier(): float
    {
        return match($this) {
            self::Easy   => 1.0,
            self::Medium => 1.5,
            self::Hard   => 2.0,
        };
    }
}
```

## Checklist avant livraison

```bash
# Obligatoire — zéro erreur acceptée
vendor/bin/phpstan analyse --level=9

# Tests
vendor/bin/pest --coverage --min=85

# Style (si configuré)
vendor/bin/pint --test
```

## Architecture attendue

```
app/
├── Actions/          # Use cases (une action = une opération métier)
├── DTOs/             # Readonly data transfer objects
├── Enums/            # Valeurs métier typées (Difficulty, SessionStatus)
├── Exceptions/       # Exceptions domaine (QuizAlreadyStartedException)
├── Http/
│   ├── Controllers/  # Thin controllers — délèguent aux Actions
│   ├── Requests/     # Validation + cast vers DTOs
│   └── Resources/    # Sérialisation API
├── Models/           # Eloquent — relations, casts, scopes
├── Policies/         # Autorisation
└── Services/         # Services techniques (pas métier)
```

## Interdictions absolues

| Interdit | Alternative |
|---|---|
| `mixed` | Types précis ou union types |
| `array` non typé | `array<string, Question>` ou DTO |
| SQL brut | Eloquent / Query Builder |
| Password en clair | `Hash::make()` |
| Config hardcodée | `config('quiz.time_limit')` |
| Business logic dans controller | Action / Service |
| `catch (\Exception $e) {}` vide | Logger au minimum |

## Exemple d'intégration PHPStan

```neon
# phpstan.neon
parameters:
    level: 9
    paths:
        - app
    excludePaths:
        - app/Http/Middleware/TrustProxies.php
    checkMissingIterableValueType: false
```
