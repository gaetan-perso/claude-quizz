---
name: claude-api
description: Intégrer le SDK Anthropic dans le projet quiz. Activer pour tout appel à Claude API — génération de questions, évaluation de réponses, adaptation de difficulté, explications pédagogiques. Couvre PHP (Laravel backend) et TypeScript (React Native mobile).
---

# Claude API — SDK Anthropic

Source : [anthropics/skills/claude-api](https://github.com/anthropics/skills/tree/main/skills/claude-api)

## Modèle par défaut

```
claude-opus-4-6
```

Utiliser Opus 4.6 sauf spécification contraire. Activer `thinking: {type: "adaptive"}` pour les tâches complexes (génération de questions, évaluation).

## Surfaces disponibles

| Besoin | Surface |
|---|---|
| Appel unique (génération, évaluation) | Claude API — Messages |
| Workflow multi-étapes | Claude API + Tool Use |
| Agent open-ended avec outils | Claude API — Agentic Loop |

## PHP — Laravel Backend

### Installation

```bash
composer require anthropic-php/client
```

### Appel de base

```php
<?php declare(strict_types=1);

use Anthropic\Anthropic;

$client = Anthropic::client(config('services.anthropic.key'));

$response = $client->messages()->create([
    'model'      => 'claude-opus-4-6',
    'max_tokens' => 2048,
    'messages'   => [
        ['role' => 'user', 'content' => $prompt],
    ],
]);

$text = $response->content[0]->text;
```

### Structured Output (JSON) — Laravel

```php
<?php declare(strict_types=1);

use Anthropic\Anthropic;

final class QuestionGeneratorService
{
    private readonly \Anthropic\Client $client;

    public function __construct()
    {
        $this->client = Anthropic::client(config('services.anthropic.key'));
    }

    public function generate(string $topic, Difficulty $difficulty): QuizQuestionDTO
    {
        $response = $this->client->messages()->create([
            'model'      => 'claude-opus-4-6',
            'max_tokens' => 1024,
            'system'     => $this->buildSystemPrompt(),
            'messages'   => [
                [
                    'role'    => 'user',
                    'content' => "Generate a {$difficulty->value} quiz question about: {$topic}",
                ],
            ],
        ]);

        $json = json_decode($response->content[0]->text, true, flags: JSON_THROW_ON_ERROR);

        return QuizQuestionDTO::fromArray($json);
    }

    private function buildSystemPrompt(): string
    {
        return <<<'PROMPT'
        You are a quiz question generator. Always respond with valid JSON only.
        No markdown, no explanation, just the JSON object.
        PROMPT;
    }
}
```

### Streaming — pour les réponses longues

```php
$stream = $client->messages()->createStreamed([
    'model'      => 'claude-opus-4-6',
    'max_tokens' => 4096,
    'messages'   => [['role' => 'user', 'content' => $prompt]],
]);

foreach ($stream as $event) {
    if ($event->type === 'content_block_delta') {
        echo $event->delta->text;
        ob_flush();
        flush();
    }
}
```

### Configuration Laravel

```php
// config/services.php
'anthropic' => [
    'key' => env('ANTHROPIC_API_KEY'),
],
```

```env
ANTHROPIC_API_KEY=sk-ant-xxxxx
```

### Gestion d'erreurs PHP

```php
use Anthropic\Exceptions\ErrorException;
use Anthropic\Exceptions\UnserializableResponse;

try {
    $response = $this->client->messages()->create([...]);
} catch (ErrorException $e) {
    // Erreur API (rate limit, auth, etc.)
    Log::error('Anthropic API error', [
        'message' => $e->getMessage(),
        'type'    => $e->getErrorType(),
    ]);
    throw new QuizAiException('AI service unavailable', previous: $e);
} catch (\JsonException $e) {
    // Réponse JSON invalide
    throw new QuizAiException('Invalid AI response format', previous: $e);
}
```

## TypeScript — React Native

### Installation

```bash
npx expo install @anthropic-ai/sdk
```

### Appel via API backend (recommandé en mobile)

```typescript
// Ne jamais exposer la clé API côté mobile
// Toujours passer par le backend Laravel

const response = await api.post<GeneratedExplanation>(
    `/api/v1/questions/${questionId}/explanation`,
    { player_answer: selectedChoiceId }
);
```

### Si appel direct depuis un serveur Node (BFF pattern)

```typescript
import Anthropic from '@anthropic-ai/sdk';

const client = new Anthropic({ apiKey: process.env.ANTHROPIC_API_KEY });

const response = await client.messages.create({
    model: 'claude-opus-4-6',
    max_tokens: 1024,
    messages: [{ role: 'user', content: prompt }],
});

const text = response.content[0].type === 'text' ? response.content[0].text : '';
```

### Types TypeScript

```typescript
import type Anthropic from '@anthropic-ai/sdk';

type Message     = Anthropic.MessageParam;
type Tool        = Anthropic.Tool;
type ToolUse     = Anthropic.ToolUseBlock;
type ToolResult  = Anthropic.ToolResultBlockParam;
type AIResponse  = Anthropic.Message;
```

## Règles de sécurité

- **Jamais** exposer `ANTHROPIC_API_KEY` côté mobile (React Native)
- Tous les appels Claude passent par le backend Laravel
- Rate limiting sur les endpoints AI : 10 req/min par joueur
- Timeout : 30s max par requête
- Fallback si l'API est indisponible (questions pré-générées)
