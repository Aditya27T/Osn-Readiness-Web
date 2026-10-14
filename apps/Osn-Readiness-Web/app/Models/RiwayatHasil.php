<?php

namespace App\Models;

use App\Enums\JenisPengerjaan;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model baca saja di atas view riwayat_hasil.
 *
 * View tidak punya created_at/updated_at dan tidak boleh ditulis, jadi model ini
 * tidak memakai HasFactory dan tidak punya timestamps.
 */
#[Table('riwayat_hasil')]
#[WithoutTimestamps]
class RiwayatHasil extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jenis_hasil' => JenisPengerjaan::class,
            'nilai' => 'decimal:2',
            'tanggal' => 'datetime',
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
    public function tingkat(): BelongsTo
    {
        return $this->belongsTo(TingkatSeleksi::class);
    }
}
