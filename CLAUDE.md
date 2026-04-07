# Quiz Project — Orchestrateur Principal

## Architecture du projet

Application quiz multijoueur composée de :
- **Backend (Laravel)** : bibliothèque de questions par thème/difficulté, API REST
- **Realtime** : serveur WebSocket via Laravel Reverb pour le multijoueur
- **Mobile (React Native)** : client joueurs connecté à l'API REST + WebSocket

## Agents disponibles

### Agents techniques

| Agent | Fichier | Rôle |
|---|---|---|
| `backend-agent` | `.claude/agents/backend-agent.md` | Laravel, Eloquent, API REST, migrations |
| `realtime-agent` | `.claude/agents/realtime-agent.md` | Laravel Reverb, WebSocket, events temps réel |
| `mobile-agent` | `.claude/agents/mobile-agent.md` | React Native, connexion REST + WebSocket |
| `testing-agent` | `.claude/agents/testing-agent.md` | TDD, PHPUnit, Pest, Jest |
| `debug-agent` | `.claude/agents/debug-agent.md` | Debugging systématique multi-couches (model: opus) |

### Agents métiers (IA générative)

| Agent | Fichier | Rôle |
|---|---|---|
| `question-generator-agent` | `.claude/agents/question-generator-agent.md` | Génération de QCM via Claude API à partir d'un thème |
| `validation-agent` | `.claude/agents/validation-agent.md` | Évaluation sémantique des réponses ouvertes + scoring |
| `adaptive-difficulty-agent` | `.claude/agents/adaptive-difficulty-agent.md` | Sélection de questions adaptée au niveau du joueur |
| `explanation-agent` | `.claude/agents/explanation-agent.md` | Explications pédagogiques en streaming post-réponse |

## Skills chargés

### Orchestration
- `subagent-driven-development` — dispatch d'un subagent frais par tâche avec double review
- `dispatching-parallel-agents` — investigation parallèle de problèmes indépendants
- `writing-plans` — décomposition des features en tâches atomiques (2-5 min)

### Backend
- `laravel-specialist` — Laravel 10+, Eloquent, Sanctum, Horizon, Livewire
- `php-pro` — PHP 8.3+, PHPStan level 9, DTOs typés, PSR-12
- `api-design-principles` — contrats REST/GraphQL, versioning, pagination

### Mobile
- `react-native-best-practices` — perf, mémoire, FPS, bundle size

### IA générative
- `claude-api` — SDK Anthropic PHP + TypeScript, structured output, streaming SSE

### Qualité
- `test-driven-development` — Red-Green-Refactor strict
- `systematic-debugging` — investigation root cause obligatoire avant tout fix

## Workflow type pour une nouvelle feature

1. Utilise `writing-plans` pour décomposer la feature en tâches atomiques
2. Utilise `subagent-driven-development` pour dispatcher les agents
3. Lance `backend-agent` + `mobile-agent` en parallèle via `dispatching-parallel-agents`
4. Lance `testing-agent` après chaque modification backend ou mobile
5. Lance `debug-agent` si 2+ tentatives de fix échouent

## Workflow type pour une feature IA

1. `question-generator-agent` → implémenter la génération de questions
2. `backend-agent` → intégrer le service dans l'API REST
3. `validation-agent` → implémenter l'évaluation des réponses
4. `adaptive-difficulty-agent` → implémenter la sélection adaptative
5. `explanation-agent` → implémenter le streaming d'explications
6. `testing-agent` → couvrir avec des mocks du SDK Anthropic

> Règle IA : La clé `ANTHROPIC_API_KEY` ne doit jamais être exposée côté mobile.
> Tous les appels Claude passent par le backend Laravel.
> Prévoir un fallback si l'API est indisponible.

## Stack technique

- **Backend** : Laravel 13, PHP 8.3+, MySQL, Redis
- **Realtime** : Laravel Reverb (WebSocket natif)
- **Mobile** : React Native (Expo)
- **Tests backend** : Pest + PHPUnit (>85% coverage)
- **Tests mobile** : Jest + React Native Testing Library

## Règles globales

- Toujours écrire le test avant le code (TDD strict)
- Jamais de fix sans investigation root cause (systematic-debugging)
- Les subagents ne partagent pas de contexte — construire les prompts explicitement
- Valider les contrats API avant de lancer le MobileAgent
- Commits petits et fréquents sur des branches dédiées
