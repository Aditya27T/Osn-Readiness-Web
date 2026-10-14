<?php

namespace App\Models;

use Database\Factories\QuizJawabanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('quiz_jawaban')]
#[Fillable(['pengerjaan_id', 'soal_id', 'urutan', 'bobot', 'jawaban_user', 'status_benar'])]
#[UseFactory(QuizJawabanFactory::class)]
class QuizJawaban extends Model
{
    /** @use HasFactory<QuizJawabanFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status_benar' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<QuizPengerjaan, $this>
     */
    public function pengerjaan(): BelongsTo
    {
        return $this->belongsTo(QuizPengerjaan::class, 'pengerjaan_id');
    }

    /**
     * @return BelongsTo<Soal, $this>
     */
    public function soal(): BelongsTo
    {
        // Soal yang di-soft delete admin tetap harus bisa dinilai dan
        // ditinjau oleh pengerjaan yang sudah memuatnya.
        return $this->belongsTo(Soal::class)->withTrashed();
    }
}
