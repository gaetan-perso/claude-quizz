<?php declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Choice extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = ['question_id', 'text', 'is_correct', 'order'];

    protected $casts = ['is_correct' => 'boolean', 'order' => 'integer'];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
