<?php

namespace App\Models;

use Database\Factories\KompetensiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('kompetensi')]
#[Fillable(['tingkat_id', 'nama_kompetensi', 'deskripsi'])]
#[UseFactory(KompetensiFactory::class)]
class Kompetensi extends Model
{
    /** @use HasFactory<KompetensiFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<TingkatSeleksi, $this>
     */
    public function tingkat(): BelongsTo
    {
        return $this->belongsTo(TingkatSeleksi::class);
    }

    /**
     * @return HasMany<Materi, $this>
     */
    public function materi(): HasMany
    {
        return $this->hasMany(Materi::class);
    }
}
