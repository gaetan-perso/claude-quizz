#!/bin/bash
set -e

REPO="gaetan-perso/claude-quizz"

declare -A TITLES=(
  [37]="mobile: [A1] Installer et configurer Laravel Reverb"
  [38]="mobile: [A2] Créer le canal Presence du lobby"
  [39]="mobile: [A3] Event LobbyPlayerJoined"
  [40]="mobile: [A4] Event LobbyPlayerLeft"
  [41]="mobile: [A5] Event LobbyStarted"
  [42]="mobile: [A6] Event LobbyGameCompleted"
  [43]="mobile: [A7] Dispatcher les events depuis LobbyController"
  [44]="mobile: [A8] Endpoint POST /lobbies/{lobby}/complete"
  [45]="mobile: [B1] Créer le projet Expo"
  [46]="mobile: [B2] Installer les dépendances mobile"
  [47]="mobile: [B3] Configuration API Axios"
  [48]="mobile: [B4] Store Zustand : authentification"
  [49]="mobile: [B5] Hook Laravel Echo (WebSocket Reverb)"
  [50]="mobile: [B6] Structure de navigation Expo Router"
  [51]="mobile: [C1] Thème et composants UI communs"
  [52]="mobile: [C2] Écran Login"
  [53]="mobile: [C3] Écran Register"
  [54]="mobile: [D1] Écran Home"
  [55]="mobile: [D2] Écran Sélection de catégorie (Solo)"
  [56]="mobile: [D3] Écran Quiz Solo"
  [57]="mobile: [D4] Écran Résultats Solo"
  [58]="mobile: [E1] Écran Lobby (créer / rejoindre)"
  [59]="mobile: [E2] Salle d'attente avec WebSocket"
  [60]="mobile: [E3] Écran Quiz Multijoueur"
  [61]="mobile: [E4] Écran Résultats Multijoueur"
  [62]="mobile: [F1] Écran Leaderboard global"
  [63]="mobile: [G1] Tests broadcast events Laravel"
  [64]="mobile: [G2] Tests mobile : Auth Store"
)

for num in "${!TITLES[@]}"; do
  title="${TITLES[$num]}"
  result=$(gh issue edit "$num" --repo "$REPO" --title "$title" 2>&1)
  status=$?
  echo "Issue #$num: status=$status output=$result"
done
