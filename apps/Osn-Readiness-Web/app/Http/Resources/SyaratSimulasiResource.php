<?php

namespace App\Http\Resources;

use App\DTOs\SyaratSimulasi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Status syarat membuka simulasi: benar bila semua materi wajib sudah
 * selesai dipelajari dan latihannya mencapai batas nilai.
 */
class SyaratSimulasiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var SyaratSimulasi $syarat */
        $syarat = $this->resource;

        return [
            'terpenuhi' => $syarat->terpenuhi,
            'alasan' => $syarat->alasan,
            'rincian' => $syarat->rincian,
        ];
    }
}
