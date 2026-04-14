<?php declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LobbyParticipant extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = ['lobby_id', 'user_id', 'score', 'is_ready', 'joined_at', 'left_at'];

    protected $casts = [
        'score'     => 'integer',
        'is_ready'  => 'boolean',
        'joined_at' => 'datetime',
        'left_at'   => 'datetime',
    ];

    public function lobby(): BelongsTo
    {
        return $this->belongsTo(Lobby::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
