<?php declare(strict_types=1);
namespace Database\Factories;

use App\Enums\Difficulty;
use App\Enums\QuestionSource;
use App\Enums\QuestionType;
use App\Models\Category;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

final class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        return [
            'category_id'            => Category::factory(),
            'text'                   => $this->faker->sentence() . ' ?',
            'difficulty'             => $this->faker->randomElement(Difficulty::cases()),
            'type'                   => QuestionType::MultipleChoice,
            'explanation'            => $this->faker->paragraph(),
            'tags'                   => [$this->faker->word(), $this->faker->word()],
            'estimated_time_seconds' => $this->faker->numberBetween(15, 60),
            'is_active'              => true,
            'source'                 => QuestionSource::Manual,
        ];
    }

    public function aiGenerated(): static
    {
        return $this->state(['source' => QuestionSource::AiGenerated]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
