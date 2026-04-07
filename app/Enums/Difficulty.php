<?php declare(strict_types=1);
namespace App\Enums;

enum Difficulty: string
{
    case Easy   = 'easy';
    case Medium = 'medium';
    case Hard   = 'hard';

    public function label(): string
    {
        return match($this) {
            self::Easy   => 'Facile',
            self::Medium => 'Moyen',
            self::Hard   => 'Difficile',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Easy   => 'success',
            self::Medium => 'warning',
            self::Hard   => 'danger',
        };
    }
}
