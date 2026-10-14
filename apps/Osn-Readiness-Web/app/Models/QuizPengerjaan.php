<?php

namespace App\Models;

use Database\Factories\QuizPengerjaanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('quiz_pengerjaan')]
#[Fillable(['user_id', 'quiz_id', 'disubmit_pada', 'nilai', 'selesai_pada'])]
#[UseFactory(QuizPengerjaanFactory::class)]
class QuizPengerjaan extends Model
{
    /** @use HasFactory<QuizPengerjaanFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'disubmit_pada' => 'datetime',
            'selesai_pada' => 'datetime',
            'nilai' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Quiz, $this>
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * @return HasMany<QuizJawaban, $this>
     */
    public function jawaban(): HasMany
    {
        return $this->hasMany(QuizJawaban::class, 'pengerjaan_id');
    }
}
