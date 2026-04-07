---
name: test-driven-development
description: Méthodologie TDD stricte Red-Green-Refactor. À activer pour toute écriture de code de production — backend Laravel (Pest/PHPUnit) ou mobile React Native (Jest/RNTL). Aucune exception sans approbation explicite.
---

# Test-Driven Development

Source : [obra/superpowers](https://github.com/obra/superpowers/blob/main/skills/test-driven-development/SKILL.md)

## Règle fondamentale

```
AUCUN CODE DE PRODUCTION SANS UN TEST QUI ÉCHOUE EN PREMIER.
```

## Le cycle Red-Green-Refactor

### RED — Écrire un test minimal qui échoue
- Décrit le comportement attendu, pas l'implémentation
- **Vérifier que le test échoue** pour la bonne raison
- Si le test passe immédiatement → le test est mal écrit, recommencer

### GREEN — Code minimal pour faire passer le test
- Écrire le code **le plus simple possible** pour passer
- Pas de fonctionnalités supplémentaires
- Pas d'optimisation prématurée

### REFACTOR — Nettoyer en gardant les tests verts
- Éliminer la duplication
- Améliorer la lisibilité
- **Ne jamais casser un test existant pendant le refactor**

## Application dans ce projet

### Backend Laravel — Pest

```php
// 1. RED — Test qui échoue
it('rejects answer submitted after question timer expired', function () {
    $session = QuizSession::factory()->withActiveQuestion(endedAt: now()->subSecond())->create();
    $player = Player::factory()->inSession($session)->create();

    $this->actingAs($player)
        ->postJson("/api/v1/sessions/{$session->id}/answers", [
            'question_id' => $session->currentQuestion->id,
            'choice_id' => 'choice-1',
        ])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Question timer has expired');
});

// 2. GREEN — Implémentation minimale
// 3. REFACTOR — Extraire dans QuizAnswerService si nécessaire
```

```bash
# Vérifier que le test échoue d'abord
vendor/bin/pest tests/Feature/Quiz/AnswerSubmissionTest.php --filter="rejects answer"
# Expected : FAIL — puis implémenter
```

### Mobile React Native — Jest

```typescript
// 1. RED — Test qui échoue
it('disables choice buttons after answer is submitted', async () => {
    const { getByText, getAllByRole } = render(<QuestionScreen />);

    fireEvent.press(getByText('Paris'));

    await waitFor(() => {
        getAllByRole('button').forEach(button => {
            expect(button.props.disabled).toBe(true);
        });
    });
});
```

```bash
npx jest --testPathPattern="QuestionScreen" --watch
```

## Règles strictes

### Toujours
- Voir le test échouer avant d'écrire le code
- Vérifier que l'échec est pour la bonne raison (pas une erreur de syntaxe)
- Utiliser de vrais objets (pas de mocks) sauf pour les services externes
- Un test = un seul comportement testé

### Jamais
- Écrire du code avant un test échouant
- Écrire des tests après le code (les tests passent immédiatement → inutile)
- Ignorer un test avec `->skip()` ou `it.skip()` sans justification documentée
- Mocker la base de données Laravel (utiliser une vraie DB de test)
- Écrire des assertions sur l'implémentation interne (tester le comportement, pas les détails)

## Rationalisations à rejeter

| Rationalisation | Réalité |
|---|---|
| "Je vais écrire les tests après" | Les tests qui passent immédiatement ne prouvent rien |
| "Les tests manuels suffisent" | Pas de couverture systématique, pas de régression catching |
| "Ce code est trop simple pour un test" | Les bugs se cachent souvent dans le code "simple" |
| "Je n'ai pas le temps d'écrire des tests" | Tu n'as pas le temps de debugger sans filet de sécurité |

## Exceptions acceptées

Requièrent une justification documentée dans le code :
- Code généré automatiquement (migrations auto-générées)
- Glue code trivial (re-export d'un seul module)
- Prototypage exploratoire (à supprimer avant merge)
