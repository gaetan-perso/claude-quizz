#!/usr/bin/env bash
# Script de création des issues GitHub pour le projet claude-quizz
# Repo : gaetan-perso/claude-quizz
# Project : PVT_kwHOAG1LKc4BUJ8p

set -euo pipefail

REPO="gaetan-perso/claude-quizz"
PROJECT_ID="PVT_kwHOAG1LKc4BUJ8p"
STATUS_FIELD_ID="PVTSSF_lAHOAG1LKc4BUJ8pzhBUBco"
TODO_OPTION_ID="f75ad846"

RESULTS_FILE="/tmp/issues_created.txt"
> "$RESULTS_FILE"

# Fonction : ajouter une issue au board et la mettre en Todo
add_to_board() {
  local node_id="$1"
  local issue_num="$2"

  # Ajouter au projet
  ITEM_ID=$(gh api graphql -f query='
mutation($projectId: ID!, $contentId: ID!) {
  addProjectV2ItemById(input: {projectId: $projectId, contentId: $contentId}) {
    item { id }
  }
}' -f projectId="'"$PROJECT_ID"'" -f contentId="'"$node_id"'" --jq '.data.addProjectV2ItemById.item.id')

  # Mettre en Todo
  gh api graphql -f query='
mutation($projectId: ID!, $itemId: ID!, $fieldId: ID!, $optionId: String!) {
  updateProjectV2ItemFieldValue(input: {
    projectId: $projectId, itemId: $itemId,
    fieldId: $fieldId,
    value: { singleSelectOptionId: $optionId }
  }) { projectV2Item { id } }
}' -f projectId="$PROJECT_ID" -f itemId="$ITEM_ID" -f fieldId="$STATUS_FIELD_ID" -f optionId="$TODO_OPTION_ID" > /dev/null

  echo "  -> Board: OK (item $ITEM_ID)"
}

# Fonction : créer une issue et l'ajouter au board
create_issue() {
  local title="$1"
  local body="$2"
  local label="$3"

  echo "Creating: $title"

  ISSUE_URL=$(gh issue create \
    --repo "$REPO" \
    --title "$title" \
    --body "$body" \
    --label "$label")

  ISSUE_NUM=$(echo "$ISSUE_URL" | grep -oE '[0-9]+$')
  NODE_ID=$(gh api "repos/$REPO/issues/$ISSUE_NUM" --jq '.node_id')

  echo "  -> Issue #$ISSUE_NUM (node: $NODE_ID)"
  add_to_board "$NODE_ID" "$ISSUE_NUM"
  echo "#$ISSUE_NUM: $title" >> "$RESULTS_FILE"
}

# Créer les labels
echo "=== Création des labels ==="
gh label create "backend" --color "0075ca" --repo "$REPO" 2>/dev/null && echo "Label backend créé" || echo "Label backend existe déjà"
gh label create "mobile" --color "e4e669" --repo "$REPO" 2>/dev/null && echo "Label mobile créé" || echo "Label mobile existe déjà"
gh label create "testing" --color "d93f0b" --repo "$REPO" 2>/dev/null && echo "Label testing créé" || echo "Label testing existe déjà"

echo ""
echo "=== Bloc A — Backend Reverb ==="

create_issue "[A1] Installer et configurer Laravel Reverb" \
'Installer le package Laravel Reverb et configurer les variables d'\''environnement pour le WebSocket multijoueur.

- `composer require laravel/reverb && php artisan reverb:install`
- Configurer `.env` : BROADCAST_CONNECTION=reverb, REVERB_APP_ID, KEY, SECRET, HOST=0.0.0.0, PORT=8080
- Vérifier `config/reverb.php` → allowed_origins: ['"'"'*'"'"']
- Vérification : `php artisan reverb:start --debug` → serveur démarre sur 0.0.0.0:8080' \
"backend"

create_issue "[A2] Créer le canal Presence du lobby" \
'Définir le canal presence WebSocket qui authentifie les participants d'\''un lobby.

- Modifier `routes/channels.php`
- Canal `lobby.{lobbyId}` : vérifier que l'\''user est participant du lobby
- Retourner `['\''id'\'' => $user->id, '\''name'\'' => $user->name]` si autorisé
- Vérification : `php artisan route:list | grep broadcasting`' \
"backend"

create_issue "[A3] Event LobbyPlayerJoined" \
'Créer l'\''event broadcast diffusé quand un joueur rejoint un lobby.

- Créer `app/Events/LobbyPlayerJoined.php`
- Implements ShouldBroadcast, canal PresenceChannel('\''lobby.{lobbyId}'\'')
- broadcastAs() → '\''player.joined'\''
- Payload : user_id, user_name, participants (liste complète)' \
"backend"

create_issue "[A4] Event LobbyPlayerLeft" \
'Créer l'\''event broadcast diffusé quand un joueur quitte un lobby.

- Créer `app/Events/LobbyPlayerLeft.php`
- Implements ShouldBroadcast, canal PresenceChannel('\''lobby.{lobbyId}'\'')
- broadcastAs() → '\''player.left'\''
- Payload : user_id, participants (liste mise à jour)' \
"backend"

create_issue "[A5] Event LobbyStarted" \
'Créer l'\''event broadcast diffusé quand l'\''hôte démarre la partie.

- Créer `app/Events/LobbyStarted.php`
- Implements ShouldBroadcast, canal PresenceChannel('\''lobby.{lobbyId}'\'')
- broadcastAs() → '\''lobby.started'\''
- Payload : session_map (dict user_id => session_id)' \
"backend"

create_issue "[A6] Event LobbyGameCompleted" \
'Créer l'\''event broadcast diffusé quand la partie multijoueur se termine.

- Créer `app/Events/LobbyGameCompleted.php`
- Implements ShouldBroadcast, canal PresenceChannel('\''lobby.{lobbyId}'\'')
- broadcastAs() → '\''game.completed'\''
- Payload : leaderboard (tableau trié par score desc)' \
"backend"

create_issue "[A7] Dispatcher les events depuis LobbyController" \
'Modifier LobbyController pour dispatcher les events broadcast au bon moment.

- Modifier `app/Http/Controllers/Api/V1/LobbyController.php`
- join() → dispatch LobbyPlayerJoined après création participant
- leave() → dispatch LobbyPlayerLeft après suppression participant
- start() → construire session_map + dispatch LobbyStarted
- Ajouter méthode privée formatParticipants()' \
"backend"

create_issue "[A8] Endpoint POST /lobbies/{lobby}/complete" \
'Ajouter l'\''endpoint de fin de partie multijoueur qui complète le lobby et diffuse le classement final.

- Modifier `app/Http/Controllers/Api/V1/LobbyController.php` : méthode complete()
- Modifier `routes/api.php` : Route::post('\''lobbies/{lobby}/complete'\'', ...)
- complete() : update lobby status='\''completed'\'', compléter toutes les sessions actives, construire leaderboard, dispatch LobbyGameCompleted' \
"backend"

echo ""
echo "=== Bloc B — Mobile Setup ==="

create_issue "[B1] Créer le projet Expo" \
'Initialiser le projet React Native Expo avec TypeScript dans le sous-dossier mobile/.

- Prérequis : Node.js LTS installé, IP LAN notée
- `npx create-expo-app@latest mobile --template blank-typescript`
- Vérification : `cd mobile && npx expo --version`' \
"mobile"

create_issue "[B2] Installer les dépendances mobile" \
'Installer toutes les dépendances nécessaires au projet mobile.

- expo-router, expo-secure-store, expo-constants, expo-status-bar
- axios, zustand, @react-native-async-storage/async-storage
- laravel-echo, pusher-js, @types/pusher-js
- Configurer package.json : "main": "expo-router/entry"
- Configurer app.json : scheme "quiz-claude"' \
"mobile"

create_issue "[B3] Configuration API Axios" \
'Créer le client HTTP Axios configuré avec l'\''IP LAN et l'\''intercepteur token Bearer.

- Créer `mobile/src/lib/constants.ts` : API_BASE_URL, REVERB_HOST, REVERB_PORT, REVERB_APP_KEY
- Créer `mobile/src/lib/api.ts` : client axios, intercepteur request (token Bearer depuis SecureStore), intercepteur response (normalisation erreurs)
- Créer `mobile/.env` : EXPO_PUBLIC_API_HOST=<IP_LAN>' \
"mobile"

create_issue "[B4] Store Zustand : authentification" \
'Créer le store Zustand qui gère l'\''état d'\''authentification (user, token, login, logout, register).

- Créer `mobile/src/store/authStore.ts`
- login(), register(), logout() : appels API + stockage SecureStore
- loadFromStorage() : restaurer l'\''état au démarrage' \
"mobile"

create_issue "[B5] Hook Laravel Echo (WebSocket Reverb)" \
'Configurer Laravel Echo pour se connecter au serveur Reverb via WebSocket.

- Créer `mobile/src/lib/echo.ts`
- getEcho() : instancier Echo avec broadcaster '\''reverb'\'', wsHost/wsPort depuis constants
- Auth headers : token Bearer pour les canaux presence
- destroyEcho() : déconnecter proprement' \
"mobile"

create_issue "[B6] Structure de navigation Expo Router" \
'Mettre en place la navigation file-based avec Expo Router et le guard d'\''authentification.

- Créer `mobile/app/_layout.tsx` : root layout + redirection auto login/app selon auth state
- Créer `mobile/app/(auth)/_layout.tsx`
- Créer `mobile/app/(app)/_layout.tsx`
- Placeholders : `(auth)/login.tsx`, `(auth)/register.tsx`, `(app)/index.tsx`' \
"mobile"

echo ""
echo "=== Bloc C — Écrans Auth ==="

create_issue "[C1] Thème et composants UI communs" \
'Créer le thème de couleurs et les composants Button et Input réutilisables.

- Créer `mobile/src/theme.ts` : colors (dark theme indigo), spacing, radius
- Créer `mobile/src/components/Button.tsx` : variantes primary/outline/ghost, état loading
- Créer `mobile/src/components/Input.tsx` : label, error, styles cohérents avec le thème' \
"mobile"

create_issue "[C2] Écran Login" \
'Implémenter l'\''écran de connexion avec email/mot de passe et gestion d'\''erreur.

- Modifier `mobile/app/(auth)/login.tsx`
- Formulaire email + password → appel authStore.login()
- Affichage erreur API, état loading, lien vers Register' \
"mobile"

create_issue "[C3] Écran Register" \
'Implémenter l'\''écran d'\''inscription avec nom/email/mot de passe et validation.

- Modifier `mobile/app/(auth)/register.tsx`
- Validation locale : nom requis, password >= 8 chars
- Appel authStore.register(), affichage erreur, lien vers Login' \
"mobile"

echo ""
echo "=== Bloc D — Écrans Solo ==="

create_issue "[D1] Écran Home" \
'Implémenter l'\''écran d'\''accueil avec les 3 entrées de menu (Solo, Multijoueur, Leaderboard).

- Modifier `mobile/app/(app)/index.tsx`
- Afficher le nom de l'\''utilisateur connecté
- Cards cliquables : Solo → /solo/categories, Multi → /multi/lobby, Leaderboard → /leaderboard
- Bouton déconnexion' \
"mobile"

create_issue "[D2] Écran Sélection de catégorie (Solo)" \
'Implémenter l'\''écran de sélection de catégorie qui crée la session et redirige vers le quiz.

- Créer `mobile/app/(app)/solo/categories.tsx`
- GET /v1/categories → afficher liste avec icon et color
- Tap sur une catégorie → POST /v1/sessions → navigate vers quiz avec sessionId' \
"mobile"

create_issue "[D3] Écran Quiz Solo" \
'Implémenter l'\''écran de quiz adaptatif avec affichage question/choix, feedback et navigation.

- Créer `mobile/app/(app)/solo/quiz.tsx`
- GET next-question → afficher question + 4 choix
- POST answers → colorer correct/incorrect + afficher explanation
- Bouton "Question suivante" → fetch next (ou complete + navigate résultats si null)
- Afficher score courant en temps réel' \
"mobile"

create_issue "[D4] Écran Résultats Solo" \
'Implémenter l'\''écran de résultats de fin de partie solo avec score final et actions.

- Créer `mobile/app/(app)/solo/results.tsx`
- Afficher medal (🥇/🥈/🥉) + score final
- Boutons : Rejouer → categories, Accueil → home, Classement → leaderboard' \
"mobile"

echo ""
echo "=== Bloc E — Écrans Multijoueur ==="

create_issue "[E1] Écran Lobby (créer / rejoindre)" \
'Implémenter l'\''écran de création et de rejoignement d'\''un lobby multijoueur.

- Créer `mobile/app/(app)/multi/lobby.tsx`
- Section "Créer" : POST /v1/lobbies (catégorie auto) → navigate waiting room (isHost=1)
- Section "Rejoindre" : Input code 6 chars → POST /v1/lobbies/join → navigate waiting room (isHost=0)' \
"mobile"

create_issue "[E2] Salle d'attente avec WebSocket" \
'Implémenter la salle d'\''attente en temps réel via le canal presence Reverb.

- Créer `mobile/app/(app)/multi/waiting.tsx`
- GET /v1/lobbies/{id} → charger état initial
- echo.join('\''lobby.{id}'\'').listen('\''.player.joined'\'') + '\''.player.left'\'' → update liste participants
- listen('\''.lobby.started'\'') → navigate vers quiz multi avec session_map[userId]
- Si hôte : bouton "Démarrer" (actif si >= 2 joueurs) → POST /v1/lobbies/{id}/start
- Afficher code d'\''invitation en grand' \
"mobile"

create_issue "[E3] Écran Quiz Multijoueur" \
'Implémenter l'\''écran de quiz en mode multijoueur (même flow que solo).

- Créer `mobile/app/(app)/multi/quiz.tsx`
- Identique au quiz solo mais avec lobbyId en paramètre
- Si hôte et session terminée : POST /v1/lobbies/{id}/complete avant navigate résultats' \
"mobile"

create_issue "[E4] Écran Résultats Multijoueur" \
'Implémenter l'\''écran de classement final de la partie multijoueur.

- Créer `mobile/app/(app)/multi/results.tsx`
- GET /v1/lobbies/{id} → afficher participants triés par score
- Médailles 🥇🥈🥉 pour le podium
- Boutons : Rejouer → lobby, Accueil → home' \
"mobile"

echo ""
echo "=== Bloc F — Leaderboard ==="

create_issue "[F1] Écran Leaderboard global" \
'Implémenter l'\''écran de classement global avec pull-to-refresh.

- Créer `mobile/app/(app)/leaderboard.tsx`
- GET /v1/leaderboard → afficher top 50
- Colonnes : rang (medal/numéro), nom, sessions, score total
- Pull-to-refresh' \
"mobile"

echo ""
echo "=== Bloc G — Tests ==="

create_issue "[G1] Tests broadcast events Laravel" \
'Écrire les tests Pest pour les events broadcast du LobbyController.

- Créer `tests/Feature/LobbyBroadcastTest.php`
- Test : LobbyPlayerJoined dispatché lors d'\''un join
- Test : LobbyPlayerLeft dispatché lors d'\''un leave
- Test : LobbyStarted dispatché avec session_map correct lors du start
- Test : LobbyGameCompleted dispatché lors du complete
- Utiliser Event::fake() et assert dispatched avec les bons paramètres' \
"testing"

create_issue "[G2] Tests mobile : Auth Store" \
'Écrire les tests Jest pour le store Zustand d'\''authentification.

- Créer `mobile/__tests__/authStore.test.ts`
- Mock expo-secure-store et axios
- Test : login() stocke token/user dans SecureStore et met à jour le state
- Test : logout() vide SecureStore et reset le state
- Test : register() crée un compte et stocke les credentials
- Test : loadFromStorage() restaure l'\''état depuis SecureStore au boot' \
"testing"

echo ""
echo "=== Résumé des issues créées ==="
cat "$RESULTS_FILE"
echo ""
echo "Total : $(wc -l < "$RESULTS_FILE") issues créées"
