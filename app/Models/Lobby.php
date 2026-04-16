<?php declare(strict_types=1);
namespace App\Models;

use App\Enums\LobbyStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final class Lobby extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'host_user_id',
        'category_id',
        'category_ids',
        'status',
        'code',
        'max_players',
        'max_questions',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'status'        => LobbyStatus::class,
        'max_players'   => 'integer',
        'max_questions' => 'integer',
        'category_ids'  => 'array',
        'started_at'    => 'datetime',
        'completed_at'  => 'datetime',
    ];

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(LobbyParticipant::class);
    }

    public function isWaiting(): bool
    {
        return $this->status === LobbyStatus::Waiting;
    }

    public static function generateCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (self::where('code', $code)->exists());

        return $code;
    }
}
