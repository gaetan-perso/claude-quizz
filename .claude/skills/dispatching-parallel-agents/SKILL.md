---
name: dispatching-parallel-agents
description: Dispatche plusieurs agents en parallèle pour investiguer ou résoudre des problèmes indépendants simultanément. À utiliser quand tu as plusieurs bugs ou tâches sans dépendances entre eux, pour réduire le temps total d'exécution.
---

# Dispatching Parallel Agents

Source : [obra/superpowers](https://github.com/obra/superpowers/blob/main/skills/dispatching-parallel-agents/SKILL.md)

## Principe

Quand tu as plusieurs problèmes indépendants, les investiguer séquentiellement gaspille du temps. Chaque investigation est indépendante et peut se faire en parallèle.

Dispatche un agent par domaine de problème. Laisse-les travailler simultanément.

## Quand utiliser ce skill

### Utiliser quand :
- Plusieurs bugs dans des sous-systèmes différents (API, WebSocket, Mobile)
- Plusieurs features indépendantes à implémenter en parallèle
- Plusieurs fichiers de test à écrire pour des modules sans lien
- Investigations dans des couches distinctes (backend + mobile)

### Ne PAS utiliser quand :
- Les problèmes sont liés (fixer l'un peut fixer l'autre)
- Debugging exploratoire (tu ne sais pas encore ce qui est cassé)
- État partagé (les agents éditeraient les mêmes fichiers)
- Tâches séquentielles avec dépendances

## Processus

### 1. Identifier les domaines indépendants

Grouper les problèmes par ce qui est cassé :
- Backend API → `backend-agent`
- WebSocket/Realtime → `realtime-agent`
- Mobile → `mobile-agent`
- Tests → `testing-agent`

Confirmer qu'ils n'ont pas de dépendances entre eux.

### 2. Créer des prompts focalisés

Chaque agent doit recevoir :
- **Un seul domaine de problème clairement défini**
- Tout le contexte nécessaire pour comprendre le problème
- Les fichiers pertinents (pas tout le projet)
- Le périmètre exact : "Ne touche que ces fichiers"
- Le livrable attendu

**Prompt minimal efficace :**
```
Contexte : [description du problème]
Fichiers concernés : [liste]
Objectif : [résultat attendu]
Contraintes : Ne modifie que [périmètre]
Rapport : Retourne DONE/NEEDS_CONTEXT/BLOCKED avec résumé des changements
```

### 3. Lancer en simultané

Dispatcher tous les agents en une seule fois.
Ne pas attendre le résultat d'un agent pour lancer le suivant (sauf dépendance).

### 4. Intégrer les résultats

Quand tous les agents ont terminé :
1. Vérifier que les fixes ne sont pas en conflit
2. Lancer la suite de tests complète
3. Merger les résultats dans la branche principale

## Exemple — Quiz : 3 bugs indépendants

```
Problèmes identifiés :
A) Le timer de question ne s'arrête pas côté mobile après QuizEnded
B) La pagination de l'API /questions retourne 500 si category inexistante
C) Le channel Presence ne déclenche pas pusher:member_removed

→ Dispatcher en parallèle :
  - mobile-agent : bug A (useQuizTimer.ts, question.tsx)
  - backend-agent : bug B (QuestionController.php, QuestionService.php)
  - realtime-agent : bug C (channels.php, QuizSessionCreated event)
```

## Vérification avant intégration

- [ ] Chaque agent a retourné `DONE` ou `DONE_WITH_CONCERNS`
- [ ] Aucun fichier n'a été modifié par deux agents différents
- [ ] Suite de tests complète verte après intégration
- [ ] Les concerns éventuels sont documentés
