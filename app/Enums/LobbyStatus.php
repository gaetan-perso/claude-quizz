<?php declare(strict_types=1);
namespace App\Enums;

enum LobbyStatus: string
{
    case Waiting    = 'waiting';
    case InProgress = 'in_progress';
    case Completed  = 'completed';
}
