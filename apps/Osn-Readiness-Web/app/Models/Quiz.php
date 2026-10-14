<?php

namespace App\Models;

use Database\Factories\QuizFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('quiz')]
#[Fillable(['materi_id', 'nama_quiz', 'deskripsi', 'jumlah_soal'])]
#[UseFactory(QuizFactory::class)]
class Quiz extends Model
{
    /** @use HasFactory<QuizFactory> */
    use HasFactory;

    /**
     * Satu latihan per materi.
     *
     * @return BelongsTo<Materi, $this>
     */
    public function materi(): BelongsTo
    {
        return $this->belongsTo(Materi::class);
    }

    /**
     * @return HasMany<QuizPengerjaan, $this>
     */
    public function pengerjaan(): HasMany
    {
        return $this->hasMany(QuizPengerjaan::class);
    }
}
