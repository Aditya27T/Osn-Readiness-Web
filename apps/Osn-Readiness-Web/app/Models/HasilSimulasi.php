<?php

namespace App\Models;

use Database\Factories\HasilSimulasiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('hasil_simulasi')]
#[Fillable([
    'user_id',
    'simulasi_id',
    'pretest_id',
    'mulai_pada',
    'batas_pada',
    'disubmit_pada',
    'selesai_pada',
    'nilai',
    'jumlah_benar',
    'jumlah_salah',
    'lulus',
])]
#[UseFactory(HasilSimulasiFactory::class)]
class HasilSimulasi extends Model
{
    /** @use HasFactory<HasilSimulasiFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mulai_pada' => 'datetime',
            'batas_pada' => 'datetime',
            'disubmit_pada' => 'datetime',
            'selesai_pada' => 'datetime',
            'nilai' => 'decimal:2',
            'lulus' => 'boolean',
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
     * @return BelongsTo<Simulasi, $this>
     */
    public function simulasi(): BelongsTo
    {
        return $this->belongsTo(Simulasi::class);
    }

    /**
     * Putaran tempat percobaan ini dihitung.
     *
     * @return BelongsTo<Pretest, $this>
     */
    public function pretest(): BelongsTo
    {
        return $this->belongsTo(Pretest::class);
    }

    /**
     * @return HasMany<HasilSimulasiJawaban, $this>
     */
    public function jawaban(): HasMany
    {
        return $this->hasMany(HasilSimulasiJawaban::class);
    }
}
