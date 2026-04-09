# Quiz Multijoueur

Application quiz multijoueur avec difficulté adaptative, évaluation IA des réponses ouvertes et explications pédagogiques en streaming.

## Stack

| Couche | Technologie |
|---|---|
| Backend | Laravel 13, PHP 8.3+, MySQL, Redis |
| Temps réel | Laravel Reverb (WebSocket) |
| Mobile | React Native (Expo) |
| Tests backend | PHPUnit / Pest |
| Tests mobile | Jest + React Native Testing Library |

## Démarrage rapide

```powershell
.\run.ps1 setup
```

L'application est disponible sur **http://localhost:8000**.  
Voir [docs/docker.md](docs/docker.md) pour toutes les commandes Docker.

---

## État d'avancement

### Infrastructure & modèles (T01 – T09) ✓

| Tâche | Description |
|---|---|
| T01–T02 | Modèle `User` avec rôles (`player` / `admin`) |
| T03–T04 | Modèles `Category`, `Question`, `Choice` avec relations Eloquent |
| T05–T06 | Migrations complètes (ULID, soft deletes, index) |
| T07–T08 | Enums PHP 8.1+ : `Difficulty`, `QuestionType`, `QuestionSource`, `UserRole` |
| T09 | Factories : `UserFactory`, `CategoryFactory`, `QuestionFactory`, `ChoiceFactory` |

### Difficulté adaptative (T10) ✓

| Tâche | Description |
|---|---|
| Migrations | `quiz_sessions` + `session_answers` |
| Modèles | `QuizSession`, `SessionAnswer` |
| Service | `AdaptiveDifficultyService` — algorithme de sélection adaptative |
| API REST | 3 endpoints sous `auth:sanctum` (voir ci-dessous) |
| Tests | 29 tests, 69 assertions — 100% verts |

---

## API REST — Sessions (T10)

Tous les endpoints requièrent un token Sanctum (`Authorization: Bearer <token>`).

### Créer une session

```
POST /api/v1/sessions
```

**Body :**
```json
{ "category_id": "<ulid>" }
```

**Réponse 201 :**
```json
{
  "data": {
    "id": "<ulid>",
    "user_id": "<ulid>",
    "category_id": "<ulid>",
    "status": "active",
    "current_difficulty": "medium",
    "consecutive_correct": 0,
    "consecutive_wrong": 0,
    "score": 0
  }
}
```

---

### Question suivante (difficulté adaptée)

```
GET /api/v1/sessions/{id}/next-question
```

**Réponse 200 — question disponible :**
```json
{
  "data": {
    "question": {
      "id": "<ulid>",
      "text": "Quelle est la formule de l'eau ?",
      "difficulty": "medium",
      "estimated_time_seconds": 30,
      "choices": [
        { "id": "<ulid>", "text": "H2O" },
        { "id": "<ulid>", "text": "CO2" }
      ]
    },
    "current_difficulty": "medium"
  }
}
```

**Réponse 200 — session terminée (plus de questions) :**
```json
{ "data": null, "message": "Session terminée" }
```

---

### Soumettre une réponse

```
POST /api/v1/sessions/{id}/answers
```

**Body :**
```json
{
  "question_id": "<ulid>",
  "choice_id": "<ulid>"
}
```

**Réponse 200 :**
```json
{
  "data": {
    "is_correct": true,
    "current_difficulty": "hard",
    "score": 4
  }
}
```

---

### Logique de difficulté adaptative

| Condition | Effet |
|---|---|
| Session démarrée | Difficulté initiale : `medium` |
| 3 bonnes réponses consécutives | Montée d'un palier (`easy→medium`, `medium→hard`) |
| 3 mauvaises réponses consécutives | Descente d'un palier (`hard→medium`, `medium→easy`) |
| Palier `hard` — plus de questions | Fallback automatique vers `medium`, puis `easy` |
| Palier `hard` déjà atteint | Reste à `hard` (plafond) |
| Palier `easy` déjà atteint | Reste à `easy` (plancher) |

---

## Lancer les tests

```powershell
.\run.ps1 test
# ou
docker compose exec app php artisan test
```

---

## Prochaines features

| Feature | User Story |
|---|---|
| T11 — Évaluation des réponses ouvertes (IA) | US-11 |
| T12 — Explications pédagogiques en streaming | US-12 |
| T13 — Interface mobile (React Native) | US-13 |
