---
name: subagent-driven-development
description: Exécute un plan d'implémentation en dispatchant un subagent frais par tâche, avec une double review (conformité spec puis qualité code) après chaque tâche. À utiliser quand tu as un plan écrit et que tu dois l'exécuter de façon fiable et contrôlée.
---

# Subagent-Driven Development

Source : [obra/superpowers](https://github.com/obra/superpowers/blob/main/skills/subagent-driven-development/SKILL.md)

## Principe

Un subagent frais par tâche + double review (spec puis qualité) = haute qualité, itération rapide.

Tu délègues chaque tâche à un agent spécialisé avec un contexte précisément construit. Les subagents ne doivent **jamais** hériter du contexte ou de l'historique de ta session — tu construis exactement ce dont ils ont besoin. Cela préserve aussi ton propre contexte pour la coordination.

## Processus

### 1. Extraire toutes les tâches en avance

Avant de dispatcher quoi que ce soit, extrais la liste complète des tâches du plan.
Identifie les dépendances entre tâches.

### 2. Dispatcher l'Implementer Subagent

Pour chaque tâche, envoyer au subagent :
- Le texte complet de la tâche
- Les fichiers de contexte pertinents (pas tout le projet)
- Les décisions d'architecture déjà prises
- Les contraintes connues

**Ne jamais** envoyer l'historique de la conversation parent.

### 3. Gérer les questions avant implémentation

Si le subagent retourne `NEEDS_CONTEXT`, répondre à ses questions AVANT de le laisser implémenter.

### 4. Review en deux étapes

#### Étape A — Conformité Spec
Vérifier que le code match les exigences :
- Chaque requirement de la spec est couvert
- Comportement attendu correct
- Edge cases traités

#### Étape B — Qualité Code
Vérifier la qualité de l'implémentation :
- Conventions du projet respectées
- Pas de code mort
- Patterns cohérents avec le reste de la codebase

### 5. Boucle de fix

Si des problèmes sont trouvés :
1. Renvoyer au subagent implementer avec les problèmes précis
2. Re-review après chaque fix (les deux étapes)
3. Ne jamais marquer DONE tant que les deux reviews ne passent pas

### 6. Review finale

Après toutes les tâches, review globale pour vérifier la cohérence inter-tâches.

## Statuts des Implementer Subagents

| Statut | Action à prendre |
|---|---|
| `DONE` | Passer à la review spec |
| `DONE_WITH_CONCERNS` | Passer à la review mais noter les concerns |
| `NEEDS_CONTEXT` | Fournir le contexte demandé, re-dispatcher |
| `BLOCKED` | Évaluer le blocage — fournir du contexte, re-dispatcher avec un modèle plus puissant, découper plus petit, ou escalader |

## Sélection du modèle par complexité

| Complexité | Modèle |
|---|---|
| Mécanique (1-2 fichiers, spec complète) | haiku |
| Intégration (multiple fichiers, patterns connus) | sonnet |
| Architecture, design, décisions complexes | opus |

## Contraintes absolues

- Ne jamais skipper les reviews
- Ne jamais travailler sur la branche main sans consentement explicite
- Ne jamais passer à la tâche suivante avec des problèmes non résolus
- La review de conformité spec DOIT précéder la review qualité
- Les deux reviewers qui trouvent des problèmes → fix + re-review des deux
