<?php

namespace App\Models;

use Database\Factories\TingkatSeleksiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('tingkat_seleksi')]
#[Fillable(['nama_tingkat', 'deskripsi', 'urutan'])]
#[UseFactory(TingkatSeleksiFactory::class)]
class TingkatSeleksi extends Model
{
    /** @use HasFactory<TingkatSeleksiFactory> */
    use HasFactory;

    /**
     * @return HasMany<Materi, $this>
     */
    public function materi(): HasMany
    {
        return $this->hasMany(Materi::class);
    }

    /**
     * @return HasMany<Kompetensi, $this>
     */
    public function kompetensi(): HasMany
    {
        return $this->hasMany(Kompetensi::class);
    }

    /**
     * @return HasMany<Pretest, $this>
     */
    public function pretest(): HasMany
    {
        return $this->hasMany(Pretest::class);
    }

    /**
     * @return HasMany<Simulasi, $this>
     */
    public function simulasi(): HasMany
    {
        return $this->hasMany(Simulasi::class);
    }

    /**
     * @return HasMany<AturanPemetaan, $this>
     */
    public function aturanPemetaan(): HasMany
    {
        return $this->hasMany(AturanPemetaan::class, 'tingkat_id', 'id');
    }
}
