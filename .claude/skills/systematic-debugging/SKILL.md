---
name: systematic-debugging
description: Framework de debugging en 4 phases avec investigation root cause obligatoire avant tout fix. À utiliser dès qu'un bug apparaît, surtout pour les problèmes de synchronisation WebSocket, les race conditions multijoueur, ou tout bug qui résiste à une première tentative de fix.
---

# Systematic Debugging

Source : [obra/superpowers](https://github.com/obra/superpowers/blob/main/skills/systematic-debugging/SKILL.md)

## Règle fondamentale

```
AUCUN FIX SANS INVESTIGATION ROOT CAUSE D'ABORD.
```

Les réparations basées sur les symptômes sont des échecs : elles masquent les vrais problèmes et créent souvent de nouveaux bugs.

## Phase 1 — Investigation Root Cause

### Actions obligatoires
1. **Lire l'erreur en entier** — stack trace complète, message d'erreur exact
2. **Reproduire le problème** de façon consistante
   - Si non reproductible → collecter plus de données, ne pas fixer
3. **Examiner les changements récents**
   ```bash
   git log --oneline -15
   git diff HEAD~3
   ```
4. **Collecter des preuves** :
   - `storage/logs/laravel.log`
   - Logs Reverb WebSocket
   - Console Metro / React Native
   - Network tab (Flipper / Charles Proxy)
5. **Tracer le flux de données** de la source jusqu'au symptôme

### Red flags — Retour en Phase 1 si tu entends ça
- "Essaie juste de changer X"
- "Ça devrait marcher si..."
- Proposer une solution avant d'avoir investigué

## Phase 2 — Analyse de Patterns

1. Trouver un cas similaire qui **fonctionne**
2. Comparer avec le cas **cassé** ligne par ligne
3. Identifier la différence exacte
4. Comprendre toutes les dépendances et hypothèses cachées

## Phase 3 — Hypothèse et Test

1. Formuler **UNE** hypothèse précise
   > "Le channel WebSocket n'est pas authentifié car le token Bearer n'est pas envoyé dans les headers de la requête d'auth Reverb"
2. Tester avec **UN** seul changement minimal
3. Vérifier le résultat avant de passer à la suite

## Phase 4 — Implémentation

1. **Créer un test qui reproduit le bug** (doit échouer)
2. Implémenter le fix qui cible la root cause
3. Vérifier que le test passe
4. Vérifier qu'aucune régression n'est introduite
5. Committer avec une description de la root cause

## Règle d'escalade — 3 tentatives = Stop

Si 3 tentatives de fix ou plus échouent :

**Stopper.** Interroger l'architecture elle-même :
- Le design actuel peut-il résoudre ce problème ?
- Faut-il repenser le flux de données ?
- Escalader à l'humain avec un rapport complet

## Problèmes courants dans ce projet

### Désynchronisation WebSocket
- Vérifier l'ordre des events dans les logs
- Vérifier que tous les joueurs sont abonnés avant le start
- Vérifier `ShouldBroadcastNow` vs `ShouldBroadcast` (queue vs synchrone)

### Race conditions sur les réponses
- Toujours valider le timestamp côté serveur (`answered_at` < `question_ends_at`)
- Ne jamais faire confiance au timer client

### N+1 queries
- Activer `DB::enableQueryLog()` en dev
- Vérifier tous les `with()` dans les Eloquent queries
- Utiliser Laravel Telescope ou Debugbar

### Perte de connexion mobile
- Vérifier la logique de reconnexion automatique
- Vérifier la réhydratation de l'état Zustand après reconnexion

## Format du rapport

```markdown
## Rapport de Debugging

**Symptôme** : [description exacte de ce qui est observé]
**Reproductible** : Oui/Non — [étapes de reproduction]
**Root Cause** : [explication précise]

### Preuves
- Log : `[extrait pertinent]`
- Diff : `[changement identifié]`

### Fix
- `[fichier:ligne]` — [description du changement]

### Vérification
- Test créé : [nom du test]
- Résultat : PASS ✓
- Régressions : aucune | [liste si applicable]
```
