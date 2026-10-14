<?php

namespace App\Models;

use Database\Factories\PemetaanMateriFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('pemetaan_materi')]
#[Fillable([
    'pretest_id',
    'user_id',
    'materi_id',
    'jumlah_soal',
    'jumlah_benar',
    'poin_didapat',
    'poin_maksimal',
    'persentase',
    'peringkat',
])]
#[UseFactory(PemetaanMateriFactory::class)]
class PemetaanMateri extends Model
{
    /** @use HasFactory<PemetaanMateriFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'persentase' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Pretest, $this>
     */
    public function pretest(): BelongsTo
    {
        return $this->belongsTo(Pretest::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Materi, $this>
     */
    public function materi(): BelongsTo
    {
        return $this->belongsTo(Materi::class);
    }
}
