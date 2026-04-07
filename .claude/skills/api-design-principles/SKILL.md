---
name: api-design-principles
description: Principes de conception REST pour l'API du quiz. Activer pour concevoir ou modifier des endpoints API — structure des ressources, pagination, gestion d'erreurs, versioning, contrats entre backend et mobile.
---

# API Design Principles

Source : [wshobson/agents](https://skills.sh/wshobson/agents/api-design-principles)

## Design REST orienté ressources

### Structure des URLs

```
GET    /api/v1/questions                    → liste paginée
GET    /api/v1/questions/{id}              → détail
POST   /api/v1/questions                    → créer
PUT    /api/v1/questions/{id}              → remplacer
PATCH  /api/v1/questions/{id}              → modifier partiellement
DELETE /api/v1/questions/{id}              → supprimer

GET    /api/v1/sessions                     → mes sessions
POST   /api/v1/sessions                     → créer une session
POST   /api/v1/sessions/{id}/join           → rejoindre
POST   /api/v1/sessions/{id}/start          → démarrer
POST   /api/v1/sessions/{id}/answers        → soumettre une réponse
GET    /api/v1/sessions/{id}/leaderboard    → classement
```

### Sémantique HTTP

| Méthode | Idempotent | Corps | Usage |
|---|---|---|---|
| GET | Oui | Non | Lecture |
| POST | Non | Oui | Création, actions |
| PUT | Oui | Oui | Remplacement complet |
| PATCH | Oui | Oui | Modification partielle |
| DELETE | Oui | Non | Suppression |

## Structure des réponses

### Collection (liste paginée)

```json
{
  "data": [
    {
      "id": "uuid",
      "text": "Quelle est la capitale de la France ?",
      "choices": [
        { "id": "uuid", "text": "Paris" },
        { "id": "uuid", "text": "Lyon" }
      ],
      "difficulty": "easy",
      "category": { "id": "uuid", "slug": "geography", "name": "Géographie" }
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 150,
    "last_page": 15
  },
  "links": {
    "self": "/api/v1/questions?page=1",
    "next": "/api/v1/questions?page=2",
    "prev": null
  }
}
```

### Ressource unique

```json
{
  "data": {
    "id": "uuid",
    "text": "...",
    "choices": []
  }
}
```

### Erreurs

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "category": ["The selected category is invalid."],
    "difficulty": ["The difficulty field is required."]
  }
}
```

## Codes HTTP sémantiques

| Code | Usage dans le quiz |
|---|---|
| 200 | GET, PUT, PATCH réussis |
| 201 | Session créée, joueur rejoint |
| 204 | DELETE réussi (pas de corps) |
| 400 | Requête malformée |
| 401 | Non authentifié |
| 403 | Non autorisé (ex: n'est pas le host) |
| 404 | Ressource inexistante |
| 409 | Conflit (session déjà démarrée, déjà répondu) |
| 422 | Validation échouée |
| 429 | Rate limit dépassé |

## Filtrage et pagination

```
GET /api/v1/questions?category=geography&difficulty=easy&per_page=20&page=2
GET /api/v1/questions?search=capitale&sort=created_at&order=desc
```

Convention de filtres :
- `?category=slug` pour filtrer par catégorie
- `?difficulty=easy|medium|hard`
- `?per_page=10` (max: 50)
- `?sort=field&order=asc|desc`

## Versioning

- Version dans l'URL : `/api/v1/`, `/api/v2/`
- Pas de breaking changes sur une version active
- Déprécation annoncée dans les headers : `Deprecation: true`

## Pièges courants — à éviter

| Piège | Solution |
|---|---|
| Exposer les IDs auto-incrément | Utiliser des UUIDs |
| Retourner des données imbriquées profondes | Séparer en ressources distinctes |
| Exposer la structure DB dans les noms de champs | Noms métier dans l'API |
| Pas de pagination | Toujours paginer les listes |
| Erreurs génériques (500 partout) | Codes précis + messages clairs |
| Exposer les réponses correctes avant le résultat | Ne jamais inclure `correct_choice_id` dans la question broadcastée |

## Contrat de l'event WebSocket

Les events Reverb doivent avoir des payloads aussi stricts que les API REST :

```typescript
// Types TypeScript générés depuis le contrat
interface QuestionBroadcastedEvent {
  question_id: string;
  text: string;
  choices: Array<{ id: string; text: string }>;  // Jamais correct_choice_id ici
  duration_ms: number;
  index: number;
  total: number;
}
```
