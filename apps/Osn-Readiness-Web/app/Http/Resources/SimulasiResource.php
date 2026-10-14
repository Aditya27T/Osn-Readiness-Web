<?php

namespace App\Http\Resources;

use App\Models\Simulasi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Simulasi resource untuk admin view.
 */
class SimulasiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Simulasi $simulasi */
        $simulasi = $this->resource;

        return [
            'id' => $simulasi->id,
            'tingkat_id' => $simulasi->tingkat_id,
            'nama_simulasi' => $simulasi->nama_simulasi,
            'deskripsi' => $simulasi->deskripsi,
            'jumlah_soal' => $simulasi->jumlah_soal,
            'durasi_menit' => $simulasi->durasi_menit,
            'is_aktif' => $simulasi->is_aktif,
            'tingkat' => $this->whenLoaded('tingkat', fn () => [
                'id' => $simulasi->tingkat->id,
                'nama' => $simulasi->tingkat->nama,
            ]),
            'created_at' => $simulasi->created_at,
            'updated_at' => $simulasi->updated_at,
        ];
    }
}
