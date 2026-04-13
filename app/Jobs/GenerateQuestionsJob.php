<?php declare(strict_types=1);

namespace App\Jobs;

use App\Enums\Difficulty;
use App\Models\Category;
use App\Contracts\QuestionGeneratorContract;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

final class GenerateQuestionsJob implements ShouldQueue
{
    use Queueable;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(
        public readonly string $topic,
        public readonly string $categorySlug,
        public readonly Difficulty $difficulty,
        public readonly int $count = 5,
    ) {}

    public function handle(QuestionGeneratorContract $generator): void
    {
        $category = Category::where('slug', $this->categorySlug)->first();

        if ($category === null) {
            Log::error('GenerateQuestionsJob: catégorie introuvable', [
                'category_slug' => $this->categorySlug,
                'topic'         => $this->topic,
            ]);

            // Pas de retry pour une catégorie inconnue
            $this->fail(new \RuntimeException("Catégorie introuvable : {$this->categorySlug}"));

            return;
        }

        try {
            $created = $generator->generate(
                topic:      $this->topic,
                category:   $category,
                difficulty: $this->difficulty,
                count:      $this->count,
            );

            Log::info('GenerateQuestionsJob: questions générées avec succès', [
                'topic'         => $this->topic,
                'category_slug' => $this->categorySlug,
                'difficulty'    => $this->difficulty->value,
                'requested'     => $this->count,
                'created'       => $created,
            ]);
        } catch (\Throwable $e) {
            Log::error('GenerateQuestionsJob: échec de la génération', [
                'topic'         => $this->topic,
                'category_slug' => $this->categorySlug,
                'difficulty'    => $this->difficulty->value,
                'error'         => $e->getMessage(),
            ]);

            // Relancer l'exception pour déclencher le mécanisme de retry de Laravel Queue
            throw $e;
        }
    }
}
