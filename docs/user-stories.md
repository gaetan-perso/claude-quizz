# User Stories — Quiz Multijoueur
Date : 2026-04-09

---

## US-01 — Authentification

### Feature: Inscription d'un joueur

```gherkin
Feature: Inscription d'un joueur
  En tant que visiteur
  Je veux créer un compte joueur
  Afin de pouvoir participer aux quiz

  Scenario: Inscription réussie
    Given je suis sur l'écran d'inscription
    When je saisis un nom "Alice", un email "alice@example.com" et un mot de passe valide
    And je confirme le mot de passe
    And je soumets le formulaire
    Then mon compte est créé avec le rôle "player"
    And je suis redirigé vers l'accueil de l'application

  Scenario: Email déjà utilisé
    Given un compte existe déjà avec l'email "alice@example.com"
    When je tente de m'inscrire avec cet email
    Then je vois un message d'erreur "Cet email est déjà utilisé"
    And aucun compte n'est créé

  Scenario: Mot de passe trop court
    Given je suis sur l'écran d'inscription
    When je saisis un mot de passe de moins de 8 caractères
    Then je vois un message d'erreur sur le champ mot de passe
    And aucun compte n'est créé
```

---

### Feature: Connexion d'un joueur

```gherkin
Feature: Connexion d'un joueur
  En tant que joueur inscrit
  Je veux me connecter à mon compte
  Afin d'accéder aux quiz

  Scenario: Connexion réussie
    Given je suis sur l'écran de connexion
    And un compte existe avec l'email "alice@example.com"
    When je saisis mon email et mon mot de passe corrects
    And je valide la connexion
    Then je reçois un token d'authentification
    And je suis redirigé vers l'accueil

  Scenario: Mauvais mot de passe
    Given je suis sur l'écran de connexion
    When je saisis un mot de passe incorrect
    Then je vois un message "Identifiants invalides"
    And aucun token n'est émis

  Scenario: Déconnexion
    Given je suis connecté
    When je me déconnecte
    Then mon token est révoqué
    And je suis redirigé vers l'écran de connexion
```

---

## US-02 — Backoffice Admin : Catégories

### Feature: Gestion des catégories

```gherkin
Feature: Gestion des catégories de quiz
  En tant qu'administrateur
  Je veux gérer les catégories de questions
  Afin d'organiser la bibliothèque de questions

  Background:
    Given je suis connecté en tant qu'administrateur
    And je suis sur la page "Catégories" du backoffice

  Scenario: Créer une catégorie
    When je clique sur "Nouvelle catégorie"
    And je saisis le nom "Histoire", le slug "histoire", l'icône "📜" et la couleur "#f59e0b"
    And je sauvegarde
    Then la catégorie "Histoire" apparaît dans la liste
    And son slug est "histoire"
    And elle est active par défaut

  Scenario: Modifier une catégorie
    Given la catégorie "Sciences" existe
    When je clique sur "Modifier" pour cette catégorie
    And je change la couleur en "#10b981"
    And je sauvegarde
    Then la catégorie "Sciences" affiche la nouvelle couleur

  Scenario: Désactiver une catégorie
    Given la catégorie "Géographie" est active
    When je la désactive
    Then elle n'apparaît plus dans la liste des catégories disponibles pour les joueurs
    And elle reste visible dans le backoffice avec le statut "Inactive"

  Scenario: Supprimer une catégorie (soft delete)
    Given la catégorie "Ancienne Catégorie" existe sans questions associées
    When je la supprime
    Then elle disparaît de la liste active du backoffice
    And elle est conservée en base avec un deleted_at renseigné

  Scenario: Slug automatique
    When je saisis le nom "Culture Générale"
    Then le slug est pré-rempli avec "culture-generale"

  Scenario: Accès refusé à un non-admin
    Given je suis connecté en tant que joueur
    When j'essaie d'accéder à "/admin"
    Then je suis redirigé vers une page d'erreur 403
```

---

## US-03 — Backoffice Admin : Questions

### Feature: Gestion des questions

```gherkin
Feature: Gestion des questions de quiz
  En tant qu'administrateur
  Je veux créer et gérer les questions
  Afin de construire la bibliothèque de quiz

  Background:
    Given je suis connecté en tant qu'administrateur
    And je suis sur la page "Questions" du backoffice

  Scenario: Créer une question à choix multiples
    When je clique sur "Nouvelle question"
    And je sélectionne la catégorie "Sciences"
    And je saisis le texte "Quelle est la formule de l'eau ?"
    And je sélectionne la difficulté "Facile"
    And je sélectionne le type "Choix multiples"
    And je saisis le temps estimé "20" secondes
    And j'ajoute 4 choix dont un correct ("H2O")
    And je saisis une explication "L'eau est composée de 2 atomes d'hydrogène et 1 d'oxygène"
    And je sauvegarde
    Then la question apparaît dans la liste avec le badge "Facile"
    And elle est associée à la catégorie "Sciences"

  Scenario: Créer une question ouverte
    When je crée une question avec le type "Ouverte"
    Then le formulaire n'affiche pas les champs de choix
    And la question est sauvegardée avec le type "open"

  Scenario: La bonne réponse n'est pas exposée dans l'API publique
    Given une question à choix multiples avec une réponse correcte existe
    When un joueur appelle GET /api/questions/{id}
    Then le champ "is_correct" n'est pas présent dans la réponse
    And les choix sont retournés sans indication de correction

  Scenario: Filtrer les questions par difficulté
    Given des questions de difficulté "Facile", "Moyen" et "Difficile" existent
    When je filtre par "Difficile"
    Then seules les questions difficiles sont affichées

  Scenario: Filtrer les questions par catégorie
    When je filtre par la catégorie "Histoire"
    Then seules les questions de la catégorie "Histoire" sont affichées

  Scenario: Désactiver une question
    Given une question active existe
    When je la désactive
    Then elle ne peut plus être sélectionnée pour une session de quiz

  Scenario: Modifier les choix d'une question
    Given une question à choix multiples existe
    When je modifie le texte d'un choix
    And je sauvegarde
    Then le choix mis à jour est visible dans le backoffice
```

---

## US-04 — Backoffice Admin : Génération IA de questions

### Feature: Génération automatique de questions via IA

```gherkin
Feature: Génération de questions par IA
  En tant qu'administrateur
  Je veux générer des questions automatiquement via l'IA
  Afin d'alimenter rapidement la bibliothèque de questions

  Background:
    Given je suis connecté en tant qu'administrateur

  Scenario: Déclencher la génération pour une catégorie
    Given je suis sur la page de la catégorie "Sciences"
    When je clique sur "Générer des questions avec l'IA"
    And je saisis le thème "Physique quantique"
    And je sélectionne la difficulté "Moyen"
    And je demande 5 questions
    And je confirme
    Then un job de génération est dispatché en queue
    And je vois une notification "Génération en cours…"

  Scenario: Questions générées disponibles dans le backoffice
    Given un job de génération a été exécuté avec succès
    When je consulte la liste des questions
    Then les nouvelles questions apparaissent avec le badge "Généré par IA"
    And chaque question a 4 choix dont un correct
    And chaque question a une explication pédagogique

  Scenario: Révision avant activation
    Given des questions générées par IA sont en statut "inactive"
    When je révise une question et la valide
    Then je l'active manuellement
    And elle devient disponible pour les joueurs

  Scenario: Échec de l'API IA
    Given l'API Anthropic est indisponible
    When je déclenche une génération
    Then le job est mis en échec après 3 tentatives
    And je reçois une notification d'erreur dans le backoffice
    And aucune question incomplète n'est sauvegardée

  Scenario: Génération à partir d'un thème textuel
    When je saisis un texte de plusieurs paragraphes comme source
    And je demande la génération
    Then les questions générées sont contextuellement liées au texte fourni
```

---

## US-05 — Backoffice Admin : Utilisateurs

### Feature: Gestion des utilisateurs

```gherkin
Feature: Gestion des utilisateurs
  En tant qu'administrateur
  Je veux gérer les comptes utilisateurs
  Afin de contrôler les accès à l'application

  Background:
    Given je suis connecté en tant qu'administrateur
    And je suis sur la page "Utilisateurs" du backoffice

  Scenario: Lister les utilisateurs
    Then je vois la liste de tous les utilisateurs avec leur nom, email et rôle

  Scenario: Promouvoir un joueur en admin
    Given l'utilisateur "Bob" a le rôle "player"
    When je modifie son rôle en "admin"
    And je sauvegarde
    Then "Bob" peut accéder au backoffice

  Scenario: Rétrograder un admin
    Given l'utilisateur "Charlie" a le rôle "admin"
    When je change son rôle en "player"
    Then "Charlie" ne peut plus accéder au backoffice

  Scenario: Rechercher un utilisateur
    When je saisis "alice" dans la barre de recherche
    Then seuls les utilisateurs dont le nom ou l'email contient "alice" sont affichés
```

---

## US-06 — Backoffice Admin : Dashboard & statistiques

### Feature: Tableau de bord administrateur

```gherkin
Feature: Tableau de bord du backoffice
  En tant qu'administrateur
  Je veux voir les statistiques globales de l'application
  Afin de piloter la bibliothèque de questions et l'activité des joueurs

  Background:
    Given je suis connecté en tant qu'administrateur
    And je suis sur le tableau de bord du backoffice

  Scenario: Voir le nombre total de questions
    Then je vois le compteur "Total questions" avec le nombre exact de questions actives

  Scenario: Voir la répartition par difficulté
    Then je vois un graphique de distribution Facile / Moyen / Difficile

  Scenario: Voir le nombre de catégories actives
    Then je vois le compteur "Catégories actives"

  Scenario: Voir le nombre de joueurs inscrits
    Then je vois le compteur "Joueurs inscrits"

  Scenario: Voir les sessions récentes
    Then je vois la liste des 10 dernières sessions avec leur statut et le nombre de participants
```

---

## US-07 — API REST : Catalogue de questions

### Feature: Consultation du catalogue via l'API

```gherkin
Feature: API REST — Catalogue de questions
  En tant que client mobile authentifié
  Je veux consulter les catégories et questions disponibles
  Afin d'alimenter l'interface de jeu

  Scenario: Lister les catégories actives
    Given je suis authentifié avec un token valide
    When j'appelle GET /api/v1/categories
    Then je reçois la liste des catégories actives
    And chaque catégorie contient : id, name, slug, icon, color

  Scenario: Lister les questions d'une catégorie
    Given la catégorie "Sciences" contient 10 questions actives
    When j'appelle GET /api/v1/categories/{id}/questions
    Then je reçois les 10 questions sans le champ "is_correct" dans les choix
    And les questions sont paginées par 20

  Scenario: Filtrer par difficulté
    When j'appelle GET /api/v1/questions?difficulty=hard
    Then seules les questions de difficulté "hard" sont retournées

  Scenario: Token invalide
    Given mon token est expiré
    When j'appelle un endpoint protégé
    Then je reçois une réponse 401 avec le message "Unauthenticated"

  Scenario: Catégorie inexistante
    When j'appelle GET /api/v1/categories/id-inexistant/questions
    Then je reçois une réponse 404
```

---

## US-08 — Session de quiz solo

### Feature: Démarrer et jouer une session solo

```gherkin
Feature: Session de quiz solo
  En tant que joueur
  Je veux jouer à un quiz seul
  Afin de tester mes connaissances

  Background:
    Given je suis connecté sur l'application mobile

  Scenario: Démarrer une session solo
    Given la catégorie "Histoire" contient au moins 5 questions actives
    When je sélectionne la catégorie "Histoire"
    And je choisis le mode "Solo"
    And je lance la partie
    Then une session est créée avec 10 questions sélectionnées
    And la première question s'affiche avec ses choix

  Scenario: Répondre à une question à choix multiples dans le temps imparti
    Given une question s'affiche avec un timer de 30 secondes
    When je sélectionne un choix avant la fin du timer
    Then ma réponse est enregistrée
    And je vois si ma réponse est correcte ou non
    And une explication pédagogique s'affiche

  Scenario: Timer expiré sans réponse
    Given une question s'affiche avec un timer de 30 secondes
    When le timer arrive à 0 sans que j'aie répondu
    Then la réponse est enregistrée comme "non répondue"
    And la question suivante s'affiche

  Scenario: Fin de session solo
    Given j'ai répondu à toutes les questions d'une session
    Then je vois mon score final (ex. "7/10")
    And je vois le détail de mes réponses correctes et incorrectes

  Scenario: Pas assez de questions disponibles
    Given une catégorie contient seulement 2 questions actives
    When je tente de démarrer une session solo avec cette catégorie
    Then je vois un message "Pas assez de questions disponibles pour cette catégorie"
```

---

## US-09 — Session de quiz multijoueur

### Feature: Créer et rejoindre une session multijoueur

```gherkin
Feature: Session de quiz multijoueur
  En tant que joueur
  Je veux jouer avec d'autres joueurs en temps réel
  Afin de me mesurer à eux

  Background:
    Given je suis connecté sur l'application mobile

  Scenario: Créer une salle de jeu
    When je sélectionne le mode "Multijoueur"
    And je crée une nouvelle salle
    Then une salle est créée avec un code unique à 6 caractères (ex. "XK9P2M")
    And je suis l'hôte de la salle
    And je vois l'écran d'attente avec le code à partager

  Scenario: Rejoindre une salle existante
    Given une salle avec le code "XK9P2M" existe et attend des joueurs
    When je saisis le code "XK9P2M"
    And je rejoins la salle
    Then j'apparais dans la liste des joueurs de la salle en temps réel
    And l'hôte voit mon nom s'ajouter à l'écran d'attente

  Scenario: Lancer la partie (hôte seulement)
    Given je suis l'hôte d'une salle avec 3 joueurs
    When je clique sur "Lancer la partie"
    Then tous les joueurs reçoivent simultanément la première question
    And le timer démarre en synchronisation pour tous

  Scenario: Synchronisation des réponses en temps réel
    Given une partie multijoueur est en cours
    When un joueur répond à la question
    Then tous les joueurs voient le nombre de joueurs ayant répondu se mettre à jour en temps réel
    But les réponses des autres joueurs ne sont pas visibles avant la fin du timer

  Scenario: Affichage du classement après chaque question
    Given tous les joueurs ont répondu (ou le timer est expiré)
    Then le classement intermédiaire s'affiche pour tous
    And les points gagnés à cette question sont visibles

  Scenario: Un joueur se déconnecte en cours de partie
    Given une partie multijoueur est en cours avec 4 joueurs
    When un joueur perd sa connexion
    Then la partie continue pour les autres joueurs
    And le joueur déconnecté apparaît comme "Hors ligne" dans le classement

  Scenario: Fin de session multijoueur
    Given tous les rounds sont terminés
    Then le podium final s'affiche pour tous les joueurs
    And le gagnant est mis en avant

  Scenario: Rejoindre une partie déjà commencée
    Given une partie a déjà débuté
    When je tente de rejoindre avec le code de la salle
    Then je vois un message "Cette partie a déjà commencé"
    And je ne peux pas rejoindre
```

---

## US-10 — Difficulté adaptative

### Feature: Sélection adaptative de la difficulté

```gherkin
Feature: Adaptation de la difficulté selon le niveau du joueur
  En tant que joueur
  Je veux que les questions s'adaptent à mon niveau
  Afin d'avoir une expérience de jeu challengeante mais accessible

  Background:
    Given je joue en mode solo avec la difficulté adaptative activée

  Scenario: Difficulté initiale par défaut
    Given c'est ma première session
    When la session démarre
    Then les premières questions sont de difficulté "Moyen"

  Scenario: Montée en difficulté après bonnes réponses
    Given j'ai répondu correctement à 3 questions consécutives de niveau "Moyen"
    When la question suivante est sélectionnée
    Then elle est de difficulté "Difficile"

  Scenario: Baisse de difficulté après mauvaises réponses
    Given j'ai répondu incorrectement à 3 questions consécutives de niveau "Moyen"
    When la question suivante est sélectionnée
    Then elle est de difficulté "Facile"

  Scenario: Stabilisation au palier maximum
    Given je réponds correctement à toutes les questions "Difficile"
    Then le niveau reste "Difficile"
    And aucune question n'est sélectionnée au-delà de ce palier

  Scenario: Aucune question disponible pour la difficulté calculée
    Given il n'y a plus de questions "Difficile" non jouées dans cette catégorie
    When le système cherche une question de niveau "Difficile"
    Then il sélectionne une question "Moyen" en fallback
```

---

## US-11 — Évaluation des réponses ouvertes

### Feature: Validation sémantique des réponses ouvertes

```gherkin
Feature: Évaluation des réponses ouvertes via IA
  En tant que joueur
  Je veux que mes réponses libres soient évaluées intelligemment
  Afin d'être noté même si ma formulation est imparfaite

  Background:
    Given une question de type "Ouverte" est affichée

  Scenario: Réponse correcte avec formulation différente
    Given la bonne réponse attendue est "La Révolution française"
    When je saisis "la révolution de 1789 en France"
    Then ma réponse est évaluée comme correcte
    And je reçois un score élevé

  Scenario: Réponse partiellement correcte
    When je saisis une réponse partiellement juste
    Then je reçois un score partiel (ex. 0.6/1)
    And le feedback explique ce qui était juste et ce qui manquait

  Scenario: Réponse incorrecte
    When je saisis une réponse complètement fausse
    Then mon score est 0
    And je reçois le feedback avec la bonne réponse

  Scenario: Réponse vide
    When je soumet une réponse vide
    Then je reçois un score de 0
    And l'évaluation IA n'est pas appelée

  Scenario: Indisponibilité de l'API d'évaluation
    Given l'API Anthropic est indisponible
    When je soumets une réponse ouverte
    Then le système utilise une correspondance textuelle exacte comme fallback
    And je vois une indication "Évaluation simplifiée disponible"
```

---

## US-12 — Explications pédagogiques

### Feature: Affichage des explications post-réponse

```gherkin
Feature: Explications pédagogiques après chaque réponse
  En tant que joueur
  Je veux recevoir une explication après chaque réponse
  Afin de comprendre et apprendre, qu'elle soit correcte ou non

  Scenario: Explication statique après réponse (QCM)
    Given j'ai répondu à une question à choix multiples
    When le résultat s'affiche
    Then l'explication prédéfinie de la question s'affiche sous le résultat

  Scenario: Explication enrichie en streaming via IA
    Given l'explication IA est activée dans les paramètres
    When j'ai répondu à une question
    Then l'explication s'affiche progressivement en streaming (mot par mot)
    And l'explication est adaptée à mon niveau détecté
    And une analogie ou une source est proposée

  Scenario: Explication complète avant la question suivante
    Given une explication est en cours d'affichage
    When je clique sur "Question suivante" avant la fin du streaming
    Then le streaming s'arrête
    And la question suivante s'affiche

  Scenario: Pas d'explication disponible
    Given une question n'a pas d'explication pré-enregistrée
    And l'API IA est indisponible
    When le résultat s'affiche
    Then aucune explication n'est affichée
    And aucune erreur n'est visible pour le joueur
```

---

## US-13 — Interface mobile : navigation et UX

### Feature: Navigation dans l'application mobile

```gherkin
Feature: Navigation dans l'application mobile
  En tant que joueur
  Je veux naviguer facilement dans l'application
  Afin d'accéder rapidement aux fonctionnalités

  Scenario: Écran d'accueil après connexion
    Given je viens de me connecter
    Then je vois l'écran d'accueil avec :
      | Élément                        |
      | Liste des catégories actives   |
      | Bouton "Mode Solo"             |
      | Bouton "Mode Multijoueur"      |
      | Mon pseudo et mon score total  |

  Scenario: Sélection d'une catégorie
    When je tape sur une catégorie
    Then je vois le nombre de questions disponibles
    And les options de difficulté disponibles
    And le bouton de démarrage de partie

  Scenario: Retour en arrière pendant une partie
    Given je suis en plein milieu d'une question
    When j'appuie sur le bouton retour
    Then une confirmation s'affiche "Quitter la partie ?"
    And si je confirme, la session est abandonnée et je reviens à l'accueil

  Scenario: Perte de connexion réseau
    Given je suis en plein milieu d'une session multijoueur
    When je perds la connexion Internet
    Then je vois un bandeau "Connexion perdue — Tentative de reconnexion…"
    And l'application tente de se reconnecter automatiquement

  Scenario: Reconnexion réussie
    Given j'avais perdu la connexion pendant une session multijoueur
    When la connexion est rétablie
    Then je suis automatiquement reconnecté au canal WebSocket
    And je retrouve l'état courant de la partie
```

---

## US-14 — Sécurité et clé API

### Feature: Protection de la clé API Anthropic

```gherkin
Feature: Sécurité de la clé API
  En tant qu'administrateur système
  Je veux que la clé API Anthropic ne soit jamais exposée côté client
  Afin de protéger les ressources d'accès à l'IA

  Scenario: Appel IA depuis le backend uniquement
    Given un joueur utilise l'application mobile
    When une explication ou une évaluation IA est déclenchée
    Then l'appel à l'API Anthropic est effectué uniquement par le backend Laravel
    And aucune clé API n'est transmise au client mobile

  Scenario: Endpoint IA non accessible sans authentification
    When j'appelle un endpoint d'évaluation IA sans token
    Then je reçois une réponse 401

  Scenario: Endpoint IA non accessible pour un admin non authentifié
    Given je suis un administrateur non connecté
    When j'appelle un endpoint de génération IA
    Then je reçois une réponse 401
```
