<?php

namespace App\Http\Resources;

use App\DTOs\TingkatSiswa;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TingkatResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var TingkatSiswa $row */
        $row = $this->resource;

        return [
            'id' => $row->tingkat->id,
            'nama_tingkat' => $row->tingkat->nama_tingkat,
            'deskripsi' => $row->tingkat->deskripsi,
            'urutan' => $row->tingkat->urutan,
            'tingkat_terbuka' => $row->status->tingkatTerbuka,
            'tahap' => $row->status->tahap->value,
        ];
    }
}
