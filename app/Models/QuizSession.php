<?php declare(strict_types=1);
namespace App\Models;

use App\Enums\Difficulty;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class QuizSession extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'quiz_sessions';

    protected $fillable = [
        'user_id',
        'category_id',
        'status',
        'current_difficulty',
        'consecutive_correct',
        'consecutive_wrong',
        'score',
        'completed_at',
    ];

    protected $casts = [
        'current_difficulty'  => Difficulty::class,
        'consecutive_correct' => 'integer',
        'consecutive_wrong'   => 'integer',
        'score'               => 'integer',
        'completed_at'        => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SessionAnswer::class, 'session_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
