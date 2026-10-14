<?php

namespace App\Http\Resources;

use App\Models\RiwayatHasil;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Satu baris riwayat dari view riwayat_hasil: jenis, referensi, nilai, dan
 * tanggal pengerjaannya.
 */
class RiwayatHasilResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var RiwayatHasil $riwayat */
        $riwayat = $this->resource;

        return [
            'jenis_hasil' => $riwayat->jenis_hasil->value,
            'referensi_id' => (int) $riwayat->referensi_id,
            'tingkat_id' => (int) $riwayat->tingkat_id,
            'nilai' => $riwayat->nilai === null ? null : (float) $riwayat->nilai,
            'tanggal' => $riwayat->tanggal?->toISOString(),
        ];
    }
}
