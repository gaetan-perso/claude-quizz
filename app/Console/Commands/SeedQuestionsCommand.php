<?php declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Difficulty;
use App\Models\Category;
use App\Services\QuestionGeneratorService;
use Illuminate\Console\Command;

final class SeedQuestionsCommand extends Command
{
    protected $signature   = 'quiz:seed-questions';
    protected $description = 'Génère 10 questions par catégorie via Claude API (easy/medium/hard)';

    /** @var array<string, array<string>> */
    private array $themes = [
        'histoire'     => ['Révolution française', 'Antiquité romaine', 'Seconde Guerre mondiale'],
        'geographie'   => ['Capitales du monde', 'Océans et mers', 'Chaînes de montagnes'],
        'sciences'     => ['Physique quantique', 'Biologie cellulaire', 'Système solaire'],
        'informatique' => ['Algorithmes et structures de données', 'Réseaux et protocoles', 'Bases de données SQL'],
        'litterature'  => ['Romans classiques français', 'Poésie du XIXe siècle', 'Prix Nobel de littérature'],
        'sport'        => ['Football mondial', 'Jeux olympiques', 'Records sportifs'],
        'cinema'       => ['Films cultes hollywoodiens', 'Cinéma français', 'Réalisateurs légendaires'],
        'musique'      => ['Rock and Roll', 'Jazz et blues', 'Musique classique'],
    ];

    public function handle(QuestionGeneratorService $generator): int
    {
        $categories = Category::active()->get()->keyBy('slug');

        if ($categories->isEmpty()) {
            $this->error('Aucune catégorie active trouvée. Lance d\'abord: php artisan db:seed');
            return self::FAILURE;
        }

        $plan = [
            Difficulty::Easy   => 3,
            Difficulty::Medium => 4,
            Difficulty::Hard   => 3,
        ];

        $totalCreated = 0;

        foreach ($this->themes as $slug => $themes) {
            $category = $categories->get($slug);

            if (! $category) {
                $this->warn("Catégorie introuvable : {$slug} — ignorée");
                continue;
            }

            $this->info("\n📂 {$category->name}");

            foreach ($plan as $difficulty => $count) {
                $theme = $themes[array_rand($themes)];
                $this->line("  → {$difficulty->label()} · {$theme} ({$count} questions)...");

                try {
                    $created = $generator->generate(
                        topic:      $theme,
                        category:   $category,
                        difficulty: $difficulty,
                        count:      $count,
                    );
                    $totalCreated += $created;
                    $this->line("     <fg=green>✓ {$created} questions créées</>");
                } catch (\Throwable $e) {
                    $this->line("     <fg=red>✗ Erreur : {$e->getMessage()}</>");
                }
            }
        }

        $this->newLine();
        $this->info("✅ Total : {$totalCreated} questions insérées en base.");

        return self::SUCCESS;
    }
}
