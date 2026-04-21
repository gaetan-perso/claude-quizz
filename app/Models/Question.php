<?php declare(strict_types=1);
namespace App\Models;

use App\Enums\Difficulty;
use App\Enums\QuestionSource;
use App\Enums\QuestionType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

final class Question extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'category_id', 'text', 'difficulty', 'type', 'explanation',
        'tags', 'estimated_time_seconds', 'is_active', 'source',
    ];

    protected $casts = [
        'difficulty'             => Difficulty::class,
        'type'                   => QuestionType::class,
        'source'                 => QuestionSource::class,
        'tags'                   => 'array',
        'is_active'              => 'boolean',
        'estimated_time_seconds' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function choices(): HasMany
    {
        return $this->hasMany(Choice::class)->orderBy('order');
    }

    public function correctChoice(): HasMany
    {
        return $this->hasMany(Choice::class)->where('is_correct', true);
    }

    public function views(): HasMany
    {
        return $this->hasMany(QuestionView::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeForDifficulty(Builder $query, Difficulty $difficulty): void
    {
        $query->where('difficulty', $difficulty->value);
    }
}
