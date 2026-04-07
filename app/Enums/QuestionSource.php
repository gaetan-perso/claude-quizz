<?php declare(strict_types=1);
namespace App\Enums;

enum QuestionSource: string
{
    case Manual      = 'manual';
    case AiGenerated = 'ai_generated';
}
