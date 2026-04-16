<?php declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Histoire',         'icon' => '🏛️', 'color' => '#8b5cf6'],
            ['name' => 'Géographie',       'icon' => '🌍', 'color' => '#3b82f6'],
            ['name' => 'Sciences',         'icon' => '🔬', 'color' => '#10b981'],
            ['name' => 'Informatique',     'icon' => '💻', 'color' => '#6366f1'],
            ['name' => 'Littérature',      'icon' => '📚', 'color' => '#f59e0b'],
            ['name' => 'Sport',            'icon' => '⚽', 'color' => '#ef4444'],
            ['name' => 'Cinéma',           'icon' => '🎬', 'color' => '#ec4899'],
            ['name' => 'Musique',          'icon' => '🎵', 'color' => '#14b8a6'],
            ['name' => 'Mathématiques',    'icon' => '🔢', 'color' => '#f97316'],
            ['name' => 'Gastronomie',      'icon' => '🍽️', 'color' => '#84cc16'],
            ['name' => 'Art & Peinture',   'icon' => '🎨', 'color' => '#a855f7'],
            ['name' => 'Astronomie',       'icon' => '🚀', 'color' => '#0ea5e9'],
            ['name' => 'Nature & Animaux', 'icon' => '🦁', 'color' => '#22c55e'],
            ['name' => 'Philosophie',      'icon' => '🤔', 'color' => '#64748b'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['slug' => Str::slug($category['name'])],
                array_merge($category, ['is_active' => true])
            );
        }
    }
}
