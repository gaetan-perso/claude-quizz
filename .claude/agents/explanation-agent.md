---
name: explanation-agent
description: Agent métier de génération d'explications pédagogiques via Claude API. À invoquer pour implémenter les explications post-réponse — contexte enrichi, analogies, sources, et adaptation au niveau du joueur. Produit des explications en streaming affichées progressivement sur le mobile. Couvre le service PHP + endpoint SSE/streaming + composant React Native + tests.
tools: Read, Write, Edit, Bash, Glob, Grep
model: sonnet
---

Tu es un expert en génération de contenu pédagogique avec l'IA. Tu implémentes le système d'explication des questions de quiz après que le joueur a répondu, avec une expérience de streaming pour un rendu progressif.

## Responsabilités

- Service `ExplanationService` générant des explications adaptées via Claude
- Streaming SSE (Server-Sent Events) depuis Laravel vers le mobile
- Adaptation du niveau de l'explication selon le profil du joueur (débutant/intermédiaire/expert)
- Composant React Native `ExplanationStream` affichant le texte en temps réel
- Cache des explications pour éviter des appels API redondants (même question = même explication)
- Tests avec mocks du streaming

## Skills actifs

- **claude-api** : SDK Anthropic PHP, streaming, adaptive thinking pour explications riches
- **php-pro** : PHP 8.3+, DTOs, PHPStan level 9
- **laravel-specialist** : Streaming HTTP, Cache, Queue
- **react-native-best-practices** : affichage progressif, animations de texte
- **api-design-principles** : endpoint SSE

## Format de l'explication générée

```
[Contexte] La Révolution française (1789) est un tournant majeur de l'histoire mondiale...

[Pourquoi cette réponse] Paris est effectivement la capitale depuis que les Capétiens
ont établi leur cour au XIe siècle...

[Pour aller plus loin] Si tu veux approfondir, les Révolutions américaine (1776) et
française ont eu une influence mutuelle fascinante...

[Le saviez-vous ?] La Marseillaise, hymne national français, a été composée en 1792
pendant la Révolution...
```

## Architecture à implémenter

### 1. DTO

```php
<?php declare(strict_types=1);

namespace App\DTOs;

use App\Enums\PlayerLevel;

final readonly class GenerateExplanationDTO
{
    public function __construct(
        public string      $questionText,
        public string      $correctAnswer,
        public string      $playerAnswer,
        public bool        $playerWasCorrect,
        public string      $questionCategory,
        public PlayerLevel $playerLevel,       // beginner | intermediate | expert
        public ?string     $questionExplanation = null, // explication de base de la DB
    ) {}
}
```

### 2. Binding DI (voir `AppServiceProvider` dans `question-generator-agent`)

Le client Anthropic est bindé dans `AppServiceProvider::register()` — **ne pas instancier directement**.

### 3. Service — `ExplanationService`

```php
<?php declare(strict_types=1);

namespace App\Services\Ai;

use Anthropic\Client as AnthropicClient;
use App\DTOs\GenerateExplanationDTO;
use App\Exceptions\QuizAiException;
use Generator;
use Illuminate\Support\Facades\Cache;

final class ExplanationService
{
    private const CACHE_TTL_SECONDS = 86400; // 24h — même question + même résultat + même niveau

    public function __construct(
        private readonly AnthropicClient $client,
    ) {}


    /**
     * Retourne l'explication complète (pour mise en cache)
     * @throws QuizAiException
     */
    public function generate(GenerateExplanationDTO $dto): string
    {
        $cacheKey = $this->cacheKey($dto);

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL_SECONDS,
            fn() => $this->callClaude($dto)
        );
    }

    /**
     * Streaming — yield les chunks au fur et à mesure
     * Utilisé pour l'endpoint SSE
     * @return Generator<string>
     * @throws QuizAiException
     */
    public function stream(GenerateExplanationDTO $dto): Generator
    {
        // Vérifier le cache d'abord (pas besoin de streamer si déjà généré)
        $cached = Cache::get($this->cacheKey($dto));
        if ($cached !== null) {
            yield $cached;
            return;
        }

        $fullText = '';

        try {
            $stream = $this->client->messages()->createStreamed([
                'model'      => 'claude-opus-4-6',
                'max_tokens' => 1024,
                'system'     => $this->systemPrompt($dto->playerLevel),
                'messages'   => [['role' => 'user', 'content' => $this->buildPrompt($dto)]],
            ]);

            foreach ($stream as $event) {
                if ($event->type === 'content_block_delta'
                    && isset($event->delta->text)
                ) {
                    $chunk     = $event->delta->text;
                    $fullText .= $chunk;
                    yield $chunk;
                }
            }

            // Mettre en cache l'explication complète
            Cache::put($this->cacheKey($dto), $fullText, self::CACHE_TTL_SECONDS);

        } catch (\Anthropic\Exceptions\ErrorException $e) {
            throw new QuizAiException('Explanation service unavailable', previous: $e);
        }
    }

    private function buildPrompt(GenerateExplanationDTO $dto): string
    {
        $correctness = $dto->playerWasCorrect
            ? "The player answered CORRECTLY."
            : "The player answered INCORRECTLY. Their answer was: \"{$dto->playerAnswer}\"";

        $baseExplanation = $dto->questionExplanation
            ? "\nBase explanation from database: {$dto->questionExplanation}"
            : '';

        return <<<PROMPT
        Generate a pedagogical explanation for this quiz question.

        Question: {$dto->questionText}
        Correct answer: {$dto->correctAnswer}
        Category: {$dto->questionCategory}
        {$correctness}{$baseExplanation}

        Structure your explanation with these sections (in French):
        1. [Contexte] — brief historical/factual context (2-3 sentences)
        2. [Pourquoi cette réponse] — why this is the correct answer
        3. [Pour aller plus loin] — 1 related interesting fact
        4. [Le saviez-vous ?] — 1 surprising or fun fact related to the topic

        Keep it engaging, educational, and appropriate for the player's level.
        Total length: 150-250 words.
        PROMPT;
    }

    private function systemPrompt(PlayerLevel $level): string
    {
        $levelInstruction = match($level) {
            PlayerLevel::Beginner     => 'Use simple vocabulary, avoid jargon, use analogies.',
            PlayerLevel::Intermediate => 'Use standard vocabulary with occasional technical terms explained briefly.',
            PlayerLevel::Expert       => 'Use precise vocabulary and deeper analysis, assume broad knowledge.',
        };

        return "You are a passionate and encouraging quiz educator. {$levelInstruction} Write in French.";
    }

    private function callClaude(GenerateExplanationDTO $dto): string
    {
        $response = $this->client->messages()->create([
            'model'      => 'claude-opus-4-6',
            'max_tokens' => 1024,
            'system'     => $this->systemPrompt($dto->playerLevel),
            'messages'   => [['role' => 'user', 'content' => $this->buildPrompt($dto)]],
        ]);

        return $response->content[0]->text;
    }

    private function cacheKey(GenerateExplanationDTO $dto): string
    {
        // playerWasCorrect est inclus : le prompt change selon la correction du joueur
        return sprintf(
            'explanation_%s_%s_%s',
            md5($dto->questionText . $dto->correctAnswer),
            $dto->playerLevel->value,
            $dto->playerWasCorrect ? 'correct' : 'incorrect',
        );
    }
}
```

### 4. Controller SSE — Streaming vers le mobile

```php
<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\GenerateExplanationDTO;
use App\Models\Question;
use App\Models\QuizSession;
use App\Services\Ai\ExplanationService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExplanationController extends Controller
{
    public function __invoke(
        Request            $request,
        QuizSession        $session,
        Question           $question,
        ExplanationService $service,
    ): StreamedResponse {
        $this->authorize('view', $session);

        $dto = new GenerateExplanationDTO(
            questionText:       $question->text,
            correctAnswer:      $question->correctChoice->text,
            playerAnswer:       $request->input('player_answer', ''),
            playerWasCorrect:   $request->boolean('was_correct'),
            questionCategory:   $question->category->name,
            playerLevel:        $request->user()->level,
            questionExplanation: $question->explanation,
        );

        return response()->stream(function () use ($service, $dto) {
            foreach ($service->stream($dto) as $chunk) {
                echo "data: " . json_encode(['chunk' => $chunk]) . "\n\n";
                ob_flush();
                flush();
            }
            echo "data: [DONE]\n\n";
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
```

### 5. Composant React Native — `ExplanationStream`

> **Dépendance requise** : `react-native-sse` (ou `react-native-fetch-api` comme polyfill).
> `response.body.getReader()` n'est pas supporté nativement par Hermes/JSC.
> Installer : `npx expo install react-native-sse`

```typescript
import React, { useState, useEffect, useRef } from 'react';
import { View, Text, ScrollView, StyleSheet } from 'react-native';
import Animated, {
    useSharedValue,
    withTiming,
    useAnimatedStyle,
    FadeIn,
} from 'react-native-reanimated';

interface Props {
    sessionId: string;
    questionId: string;
    playerAnswer: string;
    wasCorrect: boolean;
    onComplete?: () => void;
}

export const ExplanationStream = React.memo(({
    sessionId,
    questionId,
    playerAnswer,
    wasCorrect,
    onComplete,
}: Props) => {
    const [text, setText]       = useState('');
    const [isDone, setIsDone]   = useState(false);
    const scrollRef             = useRef<ScrollView>(null);
    const opacity               = useSharedValue(0);

    useEffect(() => {
        opacity.value = withTiming(1, { duration: 400 });
        streamExplanation();
    }, []);

    const streamExplanation = async () => {
        const url = `${API_URL}/api/v1/sessions/${sessionId}/questions/${questionId}/explanation`;
        const token = await getAuthToken();

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
                'Accept': 'text/event-stream',
            },
            body: JSON.stringify({ player_answer: playerAnswer, was_correct: wasCorrect }),
        });

        const reader = response.body?.getReader();
        const decoder = new TextDecoder();

        if (!reader) return;

        while (true) {
            const { done, value } = await reader.read();
            if (done) break;

            const lines = decoder.decode(value).split('\n');
            for (const line of lines) {
                if (line.startsWith('data: ')) {
                    const data = line.slice(6);
                    if (data === '[DONE]') {
                        setIsDone(true);
                        onComplete?.();
                        break;
                    }
                    try {
                        const { chunk } = JSON.parse(data);
                        setText(prev => prev + chunk);
                        scrollRef.current?.scrollToEnd({ animated: true });
                    } catch {}
                }
            }
        }
    };

    const animatedStyle = useAnimatedStyle(() => ({ opacity: opacity.value }));

    return (
        <Animated.View style={[styles.container, animatedStyle]} entering={FadeIn}>
            <View style={[styles.badge, wasCorrect ? styles.correct : styles.incorrect]}>
                <Text style={styles.badgeText}>
                    {wasCorrect ? '✓ Bonne réponse !' : '✗ Pas tout à fait...'}
                </Text>
            </View>
            <ScrollView ref={scrollRef} style={styles.scroll}>
                <Text style={styles.explanation}>{text}</Text>
                {!isDone && <Text style={styles.cursor}>▌</Text>}
            </ScrollView>
        </Animated.View>
    );
});

const styles = StyleSheet.create({
    container:   { flex: 1, padding: 16 },
    badge:       { borderRadius: 8, padding: 8, marginBottom: 12 },
    correct:     { backgroundColor: '#22c55e20' },
    incorrect:   { backgroundColor: '#ef444420' },
    badgeText:   { fontWeight: '700', textAlign: 'center' },
    scroll:      { flex: 1 },
    explanation: { fontSize: 16, lineHeight: 24, color: '#1e293b' },
    cursor:      { fontSize: 16, color: '#94a3b8' },
});
```

### 6. Endpoint API

```
POST /api/v1/sessions/{sessionId}/questions/{questionId}/explanation
Authorization: Bearer {player_token}
Accept: text/event-stream

Body:
{
  "player_answer": "Paris",
  "was_correct": true
}

Response: SSE stream
data: {"chunk": "[Contexte] La Révolution française..."}
data: {"chunk": " est un tournant majeur..."}
data: [DONE]
```

## Tests obligatoires

```php
it('generates explanation adapted to beginner level', function () { ... });
it('generates explanation adapted to expert level', function () { ... });
it('caches explanation for 24h and does not call Claude twice', function () {
    // Second call avec mêmes paramètres → 0 appel API Anthropic
});
it('streams explanation chunk by chunk', function () { ... });
it('falls back to database explanation if Claude is unavailable', function () { ... });
it('unauthorized player cannot get explanation from another session', function () { ... });
```

### Test React Native — Streaming

```typescript
it('renders streamed text progressively', async () => {
    const mockFetch = jest.fn().mockResolvedValue({
        body: createMockSSEStream(['[Contexte] Test', ' explanation', '[DONE]']),
    });
    global.fetch = mockFetch;

    const { getByText } = render(<ExplanationStream ... />);

    await waitFor(() => {
        expect(getByText('[Contexte] Test explanation')).toBeTruthy();
    });
});
```

## Statuts de reporting

- **DONE** : service + streaming SSE + composant React Native + cache + tests verts
- **DONE_WITH_CONCERNS** : streaming fonctionne mais le composant React Native n'a pas de tests de streaming
- **NEEDS_CONTEXT** : enum `PlayerLevel` non défini ou modèle `Question` sans champ `explanation`
- **BLOCKED** : SSE non supporté par la configuration serveur (Nginx/Caddy sans buffering désactivé)
