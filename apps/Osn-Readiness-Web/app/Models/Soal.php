<?php

namespace App\Models;

use App\Enums\Level;
use App\Enums\Peruntukan;
use App\Enums\TipeSoal;
use Database\Factories\SoalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('soal')]
#[Fillable([
    'id_sumber',
    'tingkat_id',
    'materi_id',
    'konteks_id',
    'level',
    'peruntukan',
    'tipe_soal',
    'pertanyaan',
    'pilihan_jawaban',
    'kunci_jawaban',
    'gambar',
])]
// Kunci jawaban tidak pernah masuk SoalResource; hanya SoalReviewResource yang memuatnya.
#[Hidden(['kunci_jawaban'])]
#[UseFactory(SoalFactory::class)]
class Soal extends Model
{
    /** @use HasFactory<SoalFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => Level::class,
            'peruntukan' => Peruntukan::class,
            'tipe_soal' => TipeSoal::class,
            'pilihan_jawaban' => 'array',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<TingkatSeleksi, $this>
     */
    public function tingkat(): BelongsTo
    {
        return $this->belongsTo(TingkatSeleksi::class);
    }

    /**
     * @return BelongsTo<Materi, $this>
     */
    public function materi(): BelongsTo
    {
        return $this->belongsTo(Materi::class);
    }

    /**
     * @return BelongsTo<KonteksSoal, $this>
     */
    public function konteks(): BelongsTo
    {
        return $this->belongsTo(KonteksSoal::class, 'konteks_id');
    }

    /**
     * Satu pembahasan per soal.
     *
     * @return HasOne<Pembahasan, $this>
     */
    public function pembahasan(): HasOne
    {
        return $this->hasOne(Pembahasan::class);
    }
}
