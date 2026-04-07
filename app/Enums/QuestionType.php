<?php declare(strict_types=1);
namespace App\Enums;

enum QuestionType: string
{
    case MultipleChoice = 'multiple_choice';
    case Open           = 'open';
}
