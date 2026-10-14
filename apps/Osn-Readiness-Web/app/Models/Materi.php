<?php

namespace App\Models;

use Database\Factories\MateriFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Table('materi')]
#[Fillable(['id_sumber', 'tingkat_id', 'kompetensi_id', 'urutan', 'judul', 'deskripsi', 'isi_materi', 'file_materi'])]
#[UseFactory(MateriFactory::class)]
class Materi extends Model
{
    /** @use HasFactory<MateriFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<TingkatSeleksi, $this>
     */
    public function tingkat(): BelongsTo
    {
        return $this->belongsTo(TingkatSeleksi::class);
    }

    /**
     * @return BelongsTo<Kompetensi, $this>
     */
    public function kompetensi(): BelongsTo
    {
        return $this->belongsTo(Kompetensi::class);
    }

    /**
     * @return HasMany<Soal, $this>
     */
    public function soal(): HasMany
    {
        return $this->hasMany(Soal::class);
    }

    /**
     * Satu latihan per materi.
     *
     * @return HasOne<Quiz, $this>
     */
    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class);
    }

    /**
     * @return HasMany<ProgressBelajar, $this>
     */
    public function progressBelajar(): HasMany
    {
        return $this->hasMany(ProgressBelajar::class);
    }
}
