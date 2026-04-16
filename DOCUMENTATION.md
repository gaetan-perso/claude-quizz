# Documentation — Quiz Claude IA

> Application quiz multijoueur avec difficulté adaptative et génération de questions par IA.

---

## Table des matières

1. [Vue d'ensemble fonctionnelle](#1-vue-densemble-fonctionnelle)
2. [Architecture technique](#2-architecture-technique)
3. [Backend Laravel](#3-backend-laravel)
   - [Modèles & base de données](#31-modèles--base-de-données)
   - [API REST](#32-api-rest)
   - [Services métier](#33-services-métier)
   - [Authentification](#34-authentification)
4. [Temps réel (WebSocket)](#4-temps-réel-websocket)
5. [Application mobile (React Native)](#5-application-mobile-react-native)
   - [Navigation](#51-navigation)
   - [Gestion d'état](#52-gestion-détat)
   - [Écrans](#53-écrans)
6. [Mode solo — Difficulté adaptative](#6-mode-solo--difficulté-adaptative)
7. [Mode multijoueur](#7-mode-multijoueur)
8. [Panel d'administration](#8-panel-dadministration)
9. [Tests](#9-tests)
10. [Configuration & déploiement](#10-configuration--déploiement)

---

## 1. Vue d'ensemble fonctionnelle

### Ce que fait l'application

Quiz Claude IA est une application mobile de quiz permettant de jouer seul ou en groupe en temps réel.

| Fonctionnalité | Description |
|---|---|
| **Quiz solo adaptatif** | Le niveau de difficulté s'ajuste automatiquement selon les performances du joueur |
| **Multijoueur temps réel** | Jusqu'à 10 joueurs dans un même lobby, questions synchronisées via WebSocket |
| **Génération de questions IA** | L'administrateur peut générer des QCM automatiquement via Claude API (Anthropic) |
| **Classement global** | Leaderboard des 50 meilleurs joueurs |
| **Panel admin** | Interface Filament pour gérer catégories, questions et utilisateurs |

### Rôles utilisateurs

| Rôle | Accès |
|---|---|
| `player` | Application mobile (quiz solo, multijoueur, classement) |
| `admin` | Panel Filament `/admin` (gestion contenu + génération IA) |

### Flux utilisateur principal

```
Inscription / Connexion
        ↓
    Menu principal
    ┌─────────────────────────────┐
    │  Solo │ Multijoueur │ Classement │
    └─────────────────────────────┘
         │              │
    Choix catégorie   Créer / Rejoindre lobby
         │              │
     Quiz adaptatif   Salle d'attente
         │              │
      Résultats       Quiz synchronisé
                        │
                   Classement final
```

---

## 2. Architecture technique

### Stack

| Couche | Technologie | Version |
|---|---|---|
| Backend | Laravel | 13 |
| Langage backend | PHP | 8.3+ |
| Base de données | SQLite (dev) / MySQL (prod) | — |
| Cache & Queues | Base de données (dev) / Redis (prod) | — |
| WebSocket | Laravel Reverb | ^1.10 |
| Authentification | Laravel Sanctum | ^4.3 |
| Admin | Filament | ^4.0 |
| Mobile | React Native (Expo) | 54 / RN 0.81.5 |
| Navigation mobile | Expo Router | 6.0 |
| État mobile | Zustand | 5.0 |
| HTTP mobile | Axios | — |
| WebSocket mobile | Laravel Echo + Pusher.js | — |
| Tests backend | Pest + PHPUnit | 4.5 / 12.5 |

### Diagramme d'architecture

```
┌─────────────────────────────────────────────────────────┐
│                    CLIENT MOBILE (Expo)                  │
│  ┌──────────┐  ┌──────────┐  ┌──────────────────────┐  │
│  │   Axios  │  │  Echo    │  │    Zustand Store     │  │
│  │  (REST)  │  │ (WS)     │  │    (auth, state)     │  │
│  └────┬─────┘  └────┬─────┘  └──────────────────────┘  │
└───────┼─────────────┼───────────────────────────────────┘
        │ HTTP        │ WebSocket
        │             │
┌───────┼─────────────┼───────────────────────────────────┐
│       │  BACKEND LARAVEL 13                              │
│  ┌────▼─────┐  ┌────▼──────┐  ┌────────────────────┐   │
│  │ API REST │  │  Reverb   │  │  Filament Admin    │   │
│  │  /api/v1 │  │  :8080    │  │  /admin            │   │
│  └────┬─────┘  └───────────┘  └────────────────────┘   │
│       │                                                  │
│  ┌────▼──────────────────────────────────────────────┐  │
│  │  Controllers → Services → Models → Database       │  │
│  │  Auth │ Session │ Lobby │ Category │ Leaderboard  │  │
│  └───────────────────────────────────────────────────┘  │
│                                                          │
│  ┌─────────────────┐    ┌─────────────────────────────┐ │
│  │  Queues (jobs)  │    │   Claude API (Anthropic)    │ │
│  │  - GenerateQs   │    │   QuestionGeneratorService  │ │
│  └─────────────────┘    └─────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
        │
┌───────▼──────────┐
│  Base de données │
│  SQLite / MySQL  │
└──────────────────┘
```

---

## 3. Backend Laravel

### 3.1 Modèles & base de données

#### User

| Champ | Type | Description |
|---|---|---|
| `id` | UUID | Identifiant unique |
| `name` | string | Nom d'affichage |
| `email` | string unique | Email de connexion |
| `password` | hashed | Mot de passe hashé |
| `role` | enum(admin, player) | Rôle utilisateur |

#### Category

| Champ | Type | Description |
|---|---|---|
| `id` | ULID | Identifiant |
| `name` | string(100) | Nom affiché |
| `slug` | string(100) unique | Identifiant URL |
| `icon` | string(50) nullable | Emoji ou nom d'icône |
| `color` | string(7) | Couleur hex (défaut: `#6366f1`) |
| `is_active` | boolean | Visible dans l'app |

#### Question

| Champ | Type | Description |
|---|---|---|
| `id` | ULID | Identifiant |
| `category_id` | ULID FK | Catégorie parente |
| `text` | text | Énoncé de la question |
| `difficulty` | enum(easy, medium, hard) | Niveau |
| `type` | enum(multiple_choice, open) | Type de question |
| `source` | enum(manual, ai_generated) | Origine |
| `explanation` | text nullable | Explication pédagogique |
| `tags` | json | Tags pour filtrage |
| `estimated_time_seconds` | smallint | Temps suggéré (défaut: 30s) |
| `is_active` | boolean | Utilisable dans les quiz |

#### Choice (réponses d'une question QCM)

| Champ | Type | Description |
|---|---|---|
| `id` | ULID | Identifiant |
| `question_id` | ULID FK | Question parente |
| `text` | string | Texte de l'option |
| `is_correct` | boolean | Bonne réponse |
| `order` | int | Ordre d'affichage |

#### QuizSession

| Champ | Type | Description |
|---|---|---|
| `id` | ULID | Identifiant |
| `user_id` | UUID FK | Joueur |
| `category_id` | ULID FK | Catégorie principale |
| `lobby_id` | ULID FK nullable | Lobby si multijoueur |
| `status` | enum(active, completed, abandoned) | État |
| `current_difficulty` | enum(easy, medium, hard) | Difficulté actuelle (solo) |
| `consecutive_correct` | tinyint | Streak de bonnes réponses |
| `consecutive_wrong` | tinyint | Streak de mauvaises réponses |
| `score` | smallint | Score total |
| `max_questions` | smallint | Nombre de questions à jouer |
| `question_ids` | json nullable | IDs pré-sélectionnés (multijoueur) |
| `completed_at` | timestamp nullable | Date de fin |

#### SessionAnswer

| Champ | Type | Description |
|---|---|---|
| `id` | ULID | Identifiant |
| `session_id` | ULID FK | Session parente |
| `question_id` | ULID FK | Question répondue |
| `choice_id` | ULID FK | Choix sélectionné |
| `is_correct` | boolean | Résultat |
| `answered_at` | timestamp | Horodatage |

#### Lobby

| Champ | Type | Description |
|---|---|---|
| `id` | ULID | Identifiant |
| `host_user_id` | UUID FK | Hôte de la partie |
| `category_id` | ULID FK | Catégorie |
| `status` | enum(waiting, in_progress, completed) | État |
| `code` | string(6) unique | Code de rejoint |
| `max_players` | tinyint | Max joueurs (défaut: 10) |
| `max_questions` | smallint | Nombre de questions |
| `current_question_index` | int nullable | Question en cours |
| `started_at` | timestamp nullable | Démarrage |
| `completed_at` | timestamp nullable | Fin |

#### LobbyParticipant

| Champ | Type | Description |
|---|---|---|
| `id` | ULID | Identifiant |
| `lobby_id` | ULID FK | Lobby |
| `user_id` | UUID FK | Joueur |
| `score` | int | Score courant |
| `is_ready` | boolean | Prêt à jouer |
| `joined_at` | timestamp | Arrivée |
| `left_at` | timestamp nullable | Départ |

---

### 3.2 API REST

Préfixe : `/api/v1` — Format : JSON — Auth : `Authorization: Bearer {token}`

#### Authentification (public)

| Méthode | Endpoint | Description | Corps |
|---|---|---|---|
| `POST` | `/auth/register` | Inscription | `name, email, password, password_confirmation` |
| `POST` | `/auth/login` | Connexion | `email, password` |

**Réponse login/register** :
```json
{
  "data": {
    "user": { "id", "name", "email", "role" },
    "token": "1|abc123..."
  }
}
```

#### Authentification (protégé)

| Méthode | Endpoint | Description |
|---|---|---|
| `POST` | `/auth/logout` | Révocation du token |
| `GET` | `/auth/me` | Profil utilisateur courant |

#### Catégories

| Méthode | Endpoint | Description |
|---|---|---|
| `GET` | `/categories` | Liste des catégories actives avec compteur de questions |

#### Sessions solo

| Méthode | Endpoint | Description |
|---|---|---|
| `POST` | `/sessions` | Créer une session | 
| `GET` | `/sessions` | Lister les sessions de l'utilisateur (paginé) |
| `GET` | `/sessions/{id}` | Détail d'une session |
| `GET` | `/sessions/{id}/next-question` | Obtenir la prochaine question (adaptative) |
| `POST` | `/sessions/{id}/answers` | Soumettre une réponse |
| `POST` | `/sessions/{id}/complete` | Terminer la session |
| `POST` | `/sessions/{id}/abandon` | Abandonner la session |

**Créer une session** :
```json
// Corps
{ "category_id": "01J...", "max_questions": 10 }

// Réponse 201
{
  "data": {
    "id": "01J...",
    "status": "active",
    "current_difficulty": "medium",
    "score": 0,
    "max_questions": 10
  }
}
```

**Soumettre une réponse** :
```json
// Corps
{ "question_id": "01J...", "choice_id": "01J..." }

// Réponse 200
{
  "data": {
    "is_correct": true,
    "score": 10,
    "explanation": "Parce que...",
    "session_completed": false
  }
}
```

#### Lobbies multijoueur

| Méthode | Endpoint | Description |
|---|---|---|
| `POST` | `/lobbies` | Créer un lobby |
| `GET` | `/lobbies/{id}` | Détail du lobby + classement |
| `POST` | `/lobbies/join` | Rejoindre via code |
| `POST` | `/lobbies/{id}/leave` | Quitter (salle d'attente) |
| `POST` | `/lobbies/{id}/start` | Démarrer (hôte uniquement) |
| `POST` | `/lobbies/{id}/advance` | Passer à la question suivante (hôte) |
| `POST` | `/lobbies/{id}/complete` | Terminer la partie (hôte) |

**Créer un lobby** :
```json
// Corps
{ "category_id": "01J...", "max_players": 8, "max_questions": 10 }

// Réponse 201
{
  "data": {
    "id": "01J...",
    "code": "ABC123",
    "status": "waiting",
    "participants": [ ... ]
  }
}
```

#### Classement

| Méthode | Endpoint | Description |
|---|---|---|
| `GET` | `/leaderboard` | Top 50 joueurs par score total |

---

### 3.3 Services métier

#### `AdaptiveDifficultyService`

Ajuste la difficulté d'une session solo selon les performances :

```
Seuil : 3 réponses consécutives identiques

Progression : Easy → Medium → Hard
Régression  : Hard → Medium → Easy

Fallback si aucune question disponible au niveau demandé :
  Hard  → essaie Medium → essaie Easy
  Medium → essaie Easy → essaie Hard
  Easy  → essaie Medium → essaie Hard
```

#### `LobbyQuestionService`

Gère la progression de la partie multijoueur :
- Génère la liste partagée de `question_ids` au démarrage
- Crée les `QuizSession` pour chaque participant
- Détermine si tous les joueurs ont répondu → avance automatiquement
- Broadcast les events Reverb

#### `QuestionGeneratorService`

Génère des questions QCM via l'API Claude (Anthropic) :
- Reçoit un thème, une difficulté, un nombre de questions
- Appelle Claude avec un prompt structuré
- Parse la réponse JSON structurée
- Persiste les questions en base avec `source = ai_generated`

---

### 3.4 Authentification

- **Sanctum tokens** (texte brut, un token par session de login)
- Token stocké dans le SecureStore d'Expo côté mobile
- Injecté automatiquement dans les headers Axios : `Authorization: Bearer {token}`
- L'authentification WebSocket utilise le même token via l'endpoint `/api/broadcasting/auth`

---

## 4. Temps réel (WebSocket)

### Infrastructure

- **Serveur** : Laravel Reverb sur `0.0.0.0:8080`
- **Canal** : `lobby.{lobbyId}` (Presence Channel — authentifié)
- **Client** : Laravel Echo + Pusher.js (mode Reverb)

### Événements broadcastés (backend → mobile)

#### `player.joined`
Émis quand un joueur rejoint la salle d'attente.
```json
{
  "user_id": "...",
  "user_name": "Alice",
  "participants": [ { "id", "name", "is_host", "is_ready" } ]
}
```

#### `player.left`
Émis quand un joueur quitte la salle d'attente.
```json
{
  "user_id": "...",
  "participants": [ ... ]
}
```

#### `lobby.started`
Émis quand l'hôte démarre la partie.
```json
{
  "session_map": {
    "userId1": "sessionId1",
    "userId2": "sessionId2"
  }
}
```
→ Le mobile navigue vers l'écran de quiz avec son `sessionId`.

#### `question.ready`
Émis à chaque nouvelle question (synchronisé pour tous les joueurs).
```json
{
  "question_index": 0,
  "total_questions": 10,
  "started_at": "2026-04-16T10:30:00Z",
  "question": {
    "id": "...",
    "text": "Quelle est la capitale de la France ?",
    "choices": [ { "id", "text" } ],
    "estimated_time_seconds": 30
  }
}
```
→ Le champ `started_at` synchronise le compte à rebours sur tous les appareils.

#### `game.completed`
Émis à la fin de la partie.
```json
{
  "leaderboard": [
    { "rank": 1, "user_id": "...", "name": "Alice", "score": 80 },
    { "rank": 2, "user_id": "...", "name": "Bob", "score": 60 }
  ]
}
```

### Stratégie de fallback

Le mobile ne se repose pas uniquement sur les WebSockets. En cas de perte de connexion :

| Étape | Mécanisme |
|---|---|
| Primaire | Event Reverb reçu en temps réel |
| Fallback 1 | Polling `GET /next-question` toutes les 1,5s après réponse |
| Fallback 2 (timeout) | L'hôte appelle `POST /advance`, les autres pollingent |

---

## 5. Application mobile (React Native)

### 5.1 Navigation

Expo Router (file-based routing) :

```
app/
├── _layout.tsx              ← Stack racine + garde auth
├── (auth)/
│   ├── _layout.tsx
│   ├── login.tsx            ← Formulaire connexion
│   └── register.tsx         ← Formulaire inscription
└── (app)/
    ├── _layout.tsx          ← Header commun
    ├── index.tsx            ← Menu principal
    ├── leaderboard.tsx      ← Classement global
    ├── solo/
    │   ├── categories.tsx   ← Sélection catégorie
    │   ├── quiz.tsx         ← Quiz solo adaptatif
    │   └── results.tsx      ← Résultats solo
    └── multi/
        ├── lobby.tsx        ← Créer / rejoindre
        ├── waiting.tsx      ← Salle d'attente
        ├── quiz.tsx         ← Quiz multijoueur (WS)
        └── results.tsx      ← Classement final
```

**Garde d'authentification** (`_layout.tsx`) :
- Si non connecté et hors groupe `(auth)` → redirect vers `/login`
- Si connecté et dans groupe `(auth)` → redirect vers `/(app)`

### 5.2 Gestion d'état

#### `authStore` (Zustand)

```typescript
{
  user: User | null        // Utilisateur connecté
  token: string | null     // Token Sanctum
  isLoading: boolean       // Chargement initial

  login(email, password)   // POST /auth/login + SecureStore
  register(name, email, password)
  logout()                 // POST /auth/logout + clear SecureStore
  loadFromStorage()        // Appelé au démarrage de l'app
}
```

Token et user persistés dans **Expo SecureStore** (chiffré côté OS).

### 5.3 Écrans

#### `(auth)/login.tsx`
- Formulaire email/mot de passe
- Appel `authStore.login()`
- Gestion des erreurs (affichage inline)

#### `(app)/index.tsx` — Menu principal
- Bouton **Quiz Solo** → `/(app)/solo/categories`
- Bouton **Multijoueur** → `/(app)/multi/lobby`
- Bouton **Classement** → `/(app)/leaderboard`

#### `(app)/solo/categories.tsx`
- `GET /categories` → liste des catégories actives
- Sélection → `POST /sessions` → navigue vers quiz

#### `(app)/solo/quiz.tsx` — Quiz solo adaptatif
```
État: question, choices, selectedChoice, result, score, questionNumber

Flux:
  1. GET /sessions/{id}/next-question
  2. Affichage question + 4 choix mélangés
  3. Sélection → POST /sessions/{id}/answers
  4. Affichage feedback (correct/incorrect + explication)
  5. Question suivante ou navigation vers résultats
```

#### `(app)/multi/lobby.tsx`
- **Créer** : `POST /lobbies` → affichage du code généré → navigue vers waiting
- **Rejoindre** : saisie du code → `POST /lobbies/join` → navigue vers waiting

#### `(app)/multi/waiting.tsx` — Salle d'attente
- Affiche le code du lobby (grand format)
- Liste des participants en temps réel (WebSocket + polling 3s fallback)
- Écoute `player.joined` et `player.left`
- **Hôte** : bouton "Démarrer" → `POST /lobbies/{id}/start`
- Écoute `lobby.started` → récupère son `sessionId` depuis `session_map` → navigue vers quiz

#### `(app)/multi/quiz.tsx` — Quiz multijoueur
```
Refs utilisés pour éviter les race conditions:
  - navigatedRef    → empêche double navigation
  - scoreRef        → score stable dans les closures
  - hasAnsweredRef  → état de réponse
  - mountedRef      → cleanup à l'unmount
  - advancingRef    → empêche double appel /advance

Flux:
  1. Connexion canal Reverb lobby.{lobbyId}
  2. Écoute question.ready → affiche question, lance timer
  3. Timer synchronisé avec started_at (temps réel)
  4. Sélection → POST /sessions/{sessionId}/answers
  5. Attente question suivante (WS ou polling 1.5s)
  6. Écoute game.completed → navigue vers résultats

Timer visuel:
  Vert (>50%) → Amber (20-50%) → Rouge (<20%)
```

#### `(app)/multi/results.tsx`
- Affichage du classement final (leaderboard)
- Mise en évidence de la position du joueur courant

---

## 6. Mode solo — Difficulté adaptative

### Algorithme

```
Difficulté initiale : medium

À chaque réponse correcte :
  consecutive_correct++
  consecutive_wrong = 0
  Si consecutive_correct >= 3 :
    Passer au niveau supérieur (easy → medium → hard)
    Reset consecutive_correct = 0

À chaque mauvaise réponse :
  consecutive_wrong++
  consecutive_correct = 0
  Si consecutive_wrong >= 3 :
    Passer au niveau inférieur (hard → medium → easy)
    Reset consecutive_wrong = 0
```

### Sélection de question

- Filtre les questions par `category_id`, `difficulty`, `is_active = true`
- Exclut les questions déjà répondues dans la session
- Si aucune question disponible au niveau cible → fallback au niveau adjacent
- Si toujours aucune → recherche toutes difficultés

---

## 7. Mode multijoueur

### Cycle de vie d'une partie

```
[Hôte] POST /lobbies
         ↓ code: "ABC123", status: waiting
         
[Joueurs] POST /lobbies/join { code: "ABC123" }
         ↓ Broadcast player.joined → salle d'attente mise à jour
         
[Hôte] POST /lobbies/{id}/start
         ↓ Backend génère question_ids partagés
         ↓ Crée QuizSession pour chaque participant
         ↓ Broadcast lobby.started { session_map }
         ↓ Broadcast question.ready { question[0], started_at }
         
[Tour N]
  ┌─ Tous reçoivent question.ready (ou pollingent)
  │  Timer countdown synchronisé depuis started_at
  │
  ├─ Chaque joueur POST /answers { question_id, choice_id }
  │
  └─ Quand tous répondus → Backend broadcast question.ready { question[N+1] }
         OU
     Timeout → Hôte POST /advance → même résultat
         
[Dernière question]
  Backend broadcast game.completed { leaderboard }
  Tous naviguent vers écran résultats
```

### Calcul du score

- Bonne réponse : +10 points (base)
- Mauvaise réponse : +0 points
- Le score de chaque joueur est calculé dans sa `QuizSession`
- Le classement final tri par `score DESC`

---

## 8. Panel d'administration

Accessible à `/admin` (rôle `admin` requis).

| Section | Fonctionnalités |
|---|---|
| **Catégories** | CRUD catégories, activation/désactivation |
| **Questions** | CRUD questions et choix, filtres par catégorie/difficulté |
| **Utilisateurs** | Gestion des rôles, liste des joueurs |
| **Génération IA** | Interface pour générer des questions par thème via Claude API |

### Génération de questions IA

L'administrateur saisit :
- Thème / sujet
- Niveau de difficulté
- Nombre de questions à générer
- Catégorie cible

Un job asynchrone (`GenerateQuestionsJob`) appelle `QuestionGeneratorService` qui :
1. Construit un prompt pour Claude avec le format QCM attendu
2. Parse la réponse structurée (JSON)
3. Insère les questions et leurs choix en base avec `source = ai_generated`

> **Règle de sécurité** : La clé `ANTHROPIC_API_KEY` n'est jamais exposée côté mobile. Tous les appels Claude transitent par le backend Laravel.

---

## 9. Tests

### Backend (Pest + PHPUnit)

```
tests/
├── Feature/
│   ├── AuthControllerTest.php         ← register, login, logout, me
│   ├── CategoryControllerTest.php     ← GET /categories
│   ├── SessionLifecycleTest.php       ← cycle complet solo
│   ├── SessionControllerTest.php      ← next-question, submit answer
│   ├── LobbyControllerTest.php        ← create, join, start, advance
│   └── LeaderboardControllerTest.php  ← top 50
├── Unit/
│   └── AdaptiveDifficultyServiceTest.php ← algorithme adaptatif
└── Backoffice/
    ├── CategoryResourceTest.php
    ├── QuestionResourceTest.php
    └── GenerateQuestionsJobTest.php
```

**Lancer les tests** :
```bash
composer test
# ou
php artisan config:clear && ./vendor/bin/pest
```

### Couverture cible

- Objectif : **>85%** de couverture sur le backend
- Chaque controller a ses tests feature
- Les services critiques (AdaptiveDifficulty) ont des tests unitaires dédiés

### Exemple de test Pest

```php
describe('POST /api/v1/auth/register', function () {
    it('registers a new user and returns a token', function () {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.user.email', 'alice@example.com')
            ->assertJsonStructure([
                'data' => ['user' => ['id', 'name', 'email', 'role'], 'token']
            ]);
    });
});
```

---

## 10. Configuration & déploiement

### Variables d'environnement — Backend (`.env`)

```env
# Application
APP_NAME=Laravel
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Base de données
DB_CONNECTION=sqlite            # sqlite (dev) ou mysql (prod)

# Broadcast (WebSocket)
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=quiz-app
REVERB_APP_KEY=quiz-app-key
REVERB_APP_SECRET=quiz-app-secret
REVERB_HOST=0.0.0.0
REVERB_PORT=8080
REVERB_SCHEME=http

# Queue (jobs asynchrones)
QUEUE_CONNECTION=database       # database (dev) ou redis (prod)

# Cache
CACHE_STORE=database            # database (dev) ou redis (prod)

# IA — Jamais exposé côté mobile
ANTHROPIC_API_KEY=sk-ant-...
```

### Variables d'environnement — Mobile (`.env`)

```env
EXPO_PUBLIC_API_HOST=192.168.1.X    # IP locale du serveur backend
EXPO_PUBLIC_API_PORT=8000
EXPO_PUBLIC_WS_PORT=8080
```

### Commandes de démarrage

**Backend** :
```bash
# Installation
composer install
php artisan key:generate
php artisan migrate --seed

# Développement (tous les processus en parallèle)
composer run dev
# Lance : php artisan serve | php artisan queue:listen | php artisan reverb:start

# Tests
composer test
```

**Mobile** :
```bash
cd mobile
npm install
npm start           # Expo dev server
npm run android     # Android
npm run ios         # iOS
npm test            # Jest
```

### Checklist déploiement production

- [ ] Passer `APP_ENV=production` et `APP_DEBUG=false`
- [ ] Configurer MySQL en remplacement de SQLite
- [ ] Passer les queues sur Redis (`QUEUE_CONNECTION=redis`)
- [ ] Passer le cache sur Redis (`CACHE_STORE=redis`)
- [ ] Configurer un reverse proxy (Nginx) devant Reverb
- [ ] Activer TLS (`REVERB_SCHEME=https`, `wss://`)
- [ ] Mettre à jour `EXPO_PUBLIC_API_HOST` avec le domaine de production
- [ ] Sécuriser `ANTHROPIC_API_KEY` via les secrets du serveur (pas dans le repo)

---

*Documentation générée le 2026-04-16 — Version du projet : commit `d9374f0`*
