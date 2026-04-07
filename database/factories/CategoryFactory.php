<?php declare(strict_types=1);
namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);
        return [
            'name'      => ucfirst($name),
            'slug'      => Str::slug($name),
            'icon'      => $this->faker->randomElement(['🎯', '🌍', '🔬', '🎨', '⚽']),
            'color'     => $this->faker->hexColor(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
