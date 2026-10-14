<?php

namespace App\Models;

use Database\Factories\SimulasiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('simulasi')]
#[Fillable(['tingkat_id', 'nama_simulasi', 'deskripsi', 'jumlah_soal', 'durasi_menit', 'is_aktif'])]
#[UseFactory(SimulasiFactory::class)]
class Simulasi extends Model
{
    /** @use HasFactory<SimulasiFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_aktif' => 'boolean',
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
     * @return HasMany<HasilSimulasi, $this>
     */
    public function hasilSimulasi(): HasMany
    {
        return $this->hasMany(HasilSimulasi::class);
    }
}
