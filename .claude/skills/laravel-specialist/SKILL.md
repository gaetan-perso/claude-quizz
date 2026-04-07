---
name: laravel-specialist
description: Expert Laravel 13 pour le backend du quiz. Activer pour tout travail sur les modèles Eloquent, l'authentification Sanctum, les queues Horizon, les ressources API, les migrations, et les tests Pest. Applique les conventions Laravel 13 et PHP 8.3+.
---

# Laravel Specialist

Source : [jeffallan/claude-skills](https://github.com/Jeffallan/claude-skills/blob/main/skills/laravel-specialist/SKILL.md)

## Profil

Expert Laravel 13+ avec PHP 8.3+. Spécialisé dans la conception d'APIs REST performantes, la modélisation Eloquent, l'authentification et les systèmes de queues.

## Capacités principales

- **Eloquent ORM** : modèles, relations (HasMany, BelongsToMany, MorphTo), eager loading, scopes, casts
- **Authentification** : Laravel Sanctum (tokens API, session-based auth)
- **Queues** : Laravel Horizon (Redis-backed), Jobs, Events, Listeners
- **APIs REST** : Controllers resource, API Resources, Form Requests, Policy gates
- **Tests** : Pest + PHPUnit (Feature, Unit, Integration)
- **WebSocket** : Laravel Reverb, broadcast events, channels

## Exigences de qualité

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\QuizSessionDTO;
use App\Models\QuizSession;
use App\Events\QuizStarted;

final class QuizSessionService
{
    public function __construct(
        private readonly QuizSession $sessions,
    ) {}

    public function start(QuizSessionDTO $dto): QuizSession
    {
        $session = $this->sessions->findOrFail($dto->sessionId);

        // Validation métier
        throw_if($session->isStarted(), \DomainException::class, 'Session already started');
        throw_if($session->players()->count() < 2, \DomainException::class, 'Need at least 2 players');

        $session->update(['started_at' => now()]);

        QuizStarted::dispatch($session);

        return $session->fresh(['players', 'questions']);
    }
}
```

## Obligations

| Obligation | Détail |
|---|---|
| Types PHP 8.3+ | `declare(strict_types=1)`, typed properties, readonly |
| PHPStan level 9 | Zéro erreur avant commit |
| Eager loading | `with()` systématique, jamais de N+1 |
| Queues | Opérations longues → Jobs, jamais dans les controllers |
| Coverage | >85% avec Pest |
| PSR-12 | Respecté partout |

## Interdictions

- Requêtes SQL brutes (utiliser Eloquent ou Query Builder typé)
- Config hardcodée (utiliser `config()` et `.env`)
- Logique métier dans les controllers
- Paramètres `mixed` ou non typés
- `new` dans les controllers (injection de dépendances)
- Mots de passe en clair

## Workflow par fonctionnalité

```
1. Migration (schema DB)
   ↓
2. Model + Relations + Casts + Factory
   ↓
3. DTO (Data Transfer Object typé)
   ↓
4. Service (logique métier)
   ↓
5. Form Request (validation)
   ↓
6. Controller + Resource API
   ↓
7. Policy (autorisation)
   ↓
8. Tests (Feature + Unit) → TDD strict
   ↓
9. PHPStan level 9
```

## Patterns spécifiques au projet quiz

### Modèles clés

```php
// Question avec relations
Question::with(['category', 'choices', 'difficulty'])
    ->where('is_active', true)
    ->whereHas('category', fn($q) => $q->where('slug', $categorySlug))
    ->paginate(10);

// Session avec joueurs
QuizSession::with(['host', 'players', 'currentQuestion.choices'])
    ->findOrFail($sessionId);
```

### API Resource

```php
class QuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'text'       => $this->text,
            'choices'    => ChoiceResource::collection($this->choices),
            'difficulty' => $this->difficulty->value,
            'category'   => new CategoryResource($this->category),
        ];
    }
}
```

### Artisan de vérification

```bash
php artisan test --coverage --min=85
vendor/bin/phpstan analyse --level=9
php artisan route:list --path=api
```
