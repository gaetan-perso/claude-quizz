---
name: writing-plans
description: Décompose une feature ou un epic en un plan d'implémentation détaillé avec des tâches atomiques de 2-5 minutes. À utiliser avant toute implémentation non triviale pour créer un plan que les subagents peuvent exécuter sans ambiguïté.
---

# Writing Plans

Source : [obra/superpowers](https://github.com/obra/superpowers/blob/main/skills/writing-plans/SKILL.md)

## Principe

Écrire des plans détaillés en partant du principe que l'ingénieur a zéro contexte sur la codebase et un jugement technique discutable.

Chaque tâche doit être si précise qu'un subagent ne peut pas se tromper.

## Format du Plan

### En-tête obligatoire

```markdown
# Plan : [Nom de la feature]
Date : YYYY-MM-DD
Objectif : [Description en une phrase]
Architecture : [Décisions clés prises]
Stack : Laravel 13 / PHP 8.3+ / React Native Expo / Laravel Reverb
```

### Format de chaque tâche

```markdown
## Tâche N — [Titre]

**Agent** : backend-agent | realtime-agent | mobile-agent | testing-agent

**Fichiers concernés** :
- `path/to/file.php` (créer | modifier)
- `path/to/other.ts` (créer | modifier)

**Code complet** :
[Code exact à écrire — pas de placeholder, pas de "à compléter"]

**Commande de vérification** :
```bash
vendor/bin/pest tests/Feature/MonTest.php
# Expected output : PASS (3 tests, 5 assertions)
```

**Commit** : `feat(quiz): [description courte]`
```

## Règles de décomposition

### Taille des tâches
- Chaque tâche prend 2-5 minutes à implémenter
- Maximum 1-2 fichiers créés/modifiés par tâche
- Une tâche = un seul concept (migration, model, controller, test, etc.)

### Contenu obligatoire
- **Chemins de fichiers exacts** — pas de "quelque chose dans app/"
- **Code complet** — pas de "TBD", "TODO", "add appropriate logic"
- **Commande de vérification avec output attendu**
- **Message de commit**

### Ordre des tâches
1. Migrations en premier
2. Models et relations
3. DTOs et interfaces
4. Services métier
5. Controllers et ressources API
6. Tests (en parallèle de chaque couche via TDD)
7. Events et broadcast
8. Mobile : types → hooks → composants → écrans

### Interdictions
- Zéro placeholder (`TBD`, `// TODO`, `add validation here`)
- Zéro code partiel
- Zéro tâche de plus de 5 minutes
- Ne pas regrouper migration + model + controller dans une seule tâche

## Vérification avant livraison du plan

- [ ] Chaque requirement de la spec est couvert par une tâche
- [ ] Zéro placeholder dans le code
- [ ] Les types sont cohérents entre toutes les tâches (même noms de champs)
- [ ] Les commandes de vérification sont exécutables
- [ ] L'ordre des tâches respecte les dépendances

## Où sauvegarder le plan

```
docs/plans/YYYY-MM-DD-<nom-feature>.md
```

## Choix d'exécution après le plan

Une fois le plan validé, proposer :

**Option A — Subagent-driven** : dispatcher un subagent frais par tâche avec double review
→ Recommandé pour les features complexes ou multi-agents

**Option B — Exécution inline** : exécuter tâche par tâche avec checkpoints humains
→ Recommandé pour les petites features ou corrections simples
