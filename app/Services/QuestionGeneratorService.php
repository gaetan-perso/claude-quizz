<?php declare(strict_types=1);

namespace App\Services;

use App\Enums\Difficulty;
use App\Enums\QuestionSource;
use App\Enums\QuestionType;
use App\Models\Category;
use App\Models\Choice;
use App\Models\Question;
use App\Contracts\QuestionGeneratorContract;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class QuestionGeneratorService implements QuestionGeneratorContract
{
    private const ANTHROPIC_API_URL = 'https://api.anthropic.com/v1/messages';
    private const ANTHROPIC_VERSION = '2023-06-01';
    private const MODEL              = 'claude-opus-4-6';

    public function __construct(
        private readonly HttpFactory $http,
    ) {}

    /**
     * Génère et persiste des questions QCM via l'API Claude.
     * Retourne le nombre de questions effectivement créées en base.
     * En cas d'indisponibilité de l'API, retourne 0 sans propager d'exception.
     */
    public function generate(
        string $topic,
        Category $category,
        Difficulty $difficulty,
        int $count = 5,
    ): int {
        $prompt = $this->buildPrompt($topic, $category, $difficulty, $count);

        try {
            $rawJson = $this->callClaude($prompt, maxTokens: min(4096, $count * 600));
        } catch (\Throwable $e) {
            Log::error('QuestionGeneratorService: API Anthropic indisponible', [
                'topic'      => $topic,
                'category'   => $category->slug,
                'difficulty' => $difficulty->value,
                'count'      => $count,
                'error'      => $e->getMessage(),
            ]);

            // Fallback : ne pas crasher — retourner 0 questions générées
            return 0;
        }

        try {
            $questions = $this->parseJson($rawJson);
        } catch (\JsonException $e) {
            Log::error('QuestionGeneratorService: JSON invalide reçu de Claude', [
                'topic' => $topic,
                'raw'   => mb_substr($rawJson, 0, 500),
                'error' => $e->getMessage(),
            ]);

            return 0;
        }

        return $this->persist($questions, $category, $difficulty);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function buildPrompt(
        string $topic,
        Category $category,
        Difficulty $difficulty,
        int $count,
    ): string {
        $difficultyLabel = $difficulty->label();

        return <<<PROMPT
        Tu es un expert en création de questions de quiz pédagogiques.

        Génère exactement {$count} questions à choix multiples (QCM) avec les contraintes suivantes :
        - Thème : {$topic}
        - Catégorie : {$category->name}
        - Niveau de difficulté : {$difficultyLabel}
        - Langue : français
        - Exactement 4 choix par question
        - Exactement 1 seule bonne réponse par question
        - Les distracteurs doivent être plausibles mais clairement incorrects
        - L'explication doit être pédagogique (2-3 phrases)
        - Temps estimé : facile=15-20s, moyen=25-35s, difficile=40-60s

        Réponds UNIQUEMENT avec du JSON valide, sans markdown, sans texte avant ou après :
        {
          "questions": [
            {
              "text": "Texte de la question ?",
              "explanation": "Explication pédagogique de la bonne réponse.",
              "estimated_time_seconds": 20,
              "choices": [
                {"text": "Bonne réponse", "is_correct": true},
                {"text": "Mauvaise réponse 1", "is_correct": false},
                {"text": "Mauvaise réponse 2", "is_correct": false},
                {"text": "Mauvaise réponse 3", "is_correct": false}
              ]
            }
          ]
        }
        PROMPT;
    }

    /**
     * @throws \RuntimeException si l'API retourne une erreur HTTP
     */
    private function callClaude(string $prompt, int $maxTokens): string
    {
        $apiKey = (string) config('services.anthropic.key');

        $response = $this->http->withHeaders([
            'x-api-key'         => $apiKey,
            'anthropic-version' => self::ANTHROPIC_VERSION,
            'content-type'      => 'application/json',
        ])->post(self::ANTHROPIC_API_URL, [
            'model'      => self::MODEL,
            'max_tokens' => $maxTokens,
            'system'     => 'Tu es un générateur de questions de quiz. Réponds uniquement avec du JSON valide, sans markdown.',
            'messages'   => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        if ($response->failed()) {
            throw new \RuntimeException(
                sprintf(
                    'Anthropic API error [HTTP %d]: %s',
                    $response->status(),
                    $response->body(),
                )
            );
        }

        /** @var array<string, mixed> $body */
        $body = $response->json();

        /** @var array<int, array<string, mixed>> $content */
        $content = $body['content'] ?? [];

        if (empty($content) || !isset($content[0]['text'])) {
            throw new \RuntimeException('Réponse Anthropic inattendue : champ "content[0].text" manquant');
        }

        return (string) $content[0]['text'];
    }

    /**
     * @return array<int, array<string, mixed>>
     * @throws \JsonException
     */
    private function parseJson(string $json): array
    {
        // Nettoyer les éventuels blocs markdown (```json ... ```)
        $cleaned = preg_replace('/^```(?:json)?\s*/m', '', $json);
        $cleaned = preg_replace('/```\s*$/m', '', (string) $cleaned);
        $cleaned = trim((string) $cleaned);

        /** @var array<string, mixed> $data */
        $data = json_decode($cleaned, true, 512, JSON_THROW_ON_ERROR);

        if (!isset($data['questions']) || !is_array($data['questions'])) {
            throw new \JsonException('Clé "questions" manquante ou invalide dans la réponse JSON');
        }

        /** @var array<int, array<string, mixed>> $questions */
        $questions = $data['questions'];

        return $questions;
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     */
    private function persist(array $questions, Category $category, Difficulty $difficulty): int
    {
        $created = 0;

        DB::transaction(function () use ($questions, $category, $difficulty, &$created): void {
            foreach ($questions as $questionData) {
                if (!$this->isValidQuestion($questionData)) {
                    Log::warning('QuestionGeneratorService: question ignorée (format invalide)', [
                        'data' => $questionData,
                    ]);
                    continue;
                }

                /** @var Question $question */
                $question = Question::create([
                    'category_id'            => $category->id,
                    'text'                   => (string) $questionData['text'],
                    'difficulty'             => $difficulty,
                    'type'                   => QuestionType::MultipleChoice,
                    'source'                 => QuestionSource::AiGenerated,
                    'explanation'            => (string) ($questionData['explanation'] ?? ''),
                    'estimated_time_seconds' => (int) ($questionData['estimated_time_seconds'] ?? 30),
                    'tags'                   => [],
                    'is_active'              => true,
                ]);

                /** @var array<int, array<string, mixed>> $choices */
                $choices = $questionData['choices'];

                foreach ($choices as $order => $choiceData) {
                    Choice::create([
                        'question_id' => $question->id,
                        'text'        => (string) $choiceData['text'],
                        'is_correct'  => (bool) $choiceData['is_correct'],
                        'order'       => $order,
                    ]);
                }

                $created++;
            }
        });

        return $created;
    }

    /**
     * Valide la structure d'une question générée.
     *
     * @param array<string, mixed> $data
     */
    private function isValidQuestion(array $data): bool
    {
        if (empty($data['text']) || !is_string($data['text'])) {
            return false;
        }

        if (!isset($data['choices']) || !is_array($data['choices'])) {
            return false;
        }

        /** @var array<int, mixed> $choices */
        $choices = $data['choices'];

        if (count($choices) !== 4) {
            return false;
        }

        $correctCount = 0;

        foreach ($choices as $choice) {
            if (!is_array($choice)) {
                return false;
            }

            if (empty($choice['text']) || !is_string($choice['text'])) {
                return false;
            }

            if (!isset($choice['is_correct'])) {
                return false;
            }

            if ((bool) $choice['is_correct']) {
                $correctCount++;
            }
        }

        return $correctCount === 1;
    }
}
