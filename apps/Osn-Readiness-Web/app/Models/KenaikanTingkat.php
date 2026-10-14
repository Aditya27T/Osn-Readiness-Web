<?php

namespace App\Models;

use App\Enums\StatusKenaikan;
use Database\Factories\KenaikanTingkatFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('kenaikan_tingkat')]
#[Fillable(['user_id', 'tingkat_asal_id', 'tingkat_tujuan_id', 'status', 'keterangan'])]
#[UseFactory(KenaikanTingkatFactory::class)]
class KenaikanTingkat extends Model
{
    /** @use HasFactory<KenaikanTingkatFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StatusKenaikan::class,
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
     * @return BelongsTo<TingkatSeleksi, $this>
     */
    public function tingkatAsal(): BelongsTo
    {
        return $this->belongsTo(TingkatSeleksi::class, 'tingkat_asal_id');
    }

    /**
     * Kosong untuk Provinsi, tingkat tertinggi.
     *
     * @return BelongsTo<TingkatSeleksi, $this>
     */
    public function tingkatTujuan(): BelongsTo
    {
        return $this->belongsTo(TingkatSeleksi::class, 'tingkat_tujuan_id');
    }
}
