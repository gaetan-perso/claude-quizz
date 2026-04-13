<?php declare(strict_types=1);

namespace App\Contracts;

use App\Enums\Difficulty;
use App\Models\Category;

interface QuestionGeneratorContract
{
    public function generate(
        string $topic,
        Category $category,
        Difficulty $difficulty,
        int $count = 5,
    ): int;
}
