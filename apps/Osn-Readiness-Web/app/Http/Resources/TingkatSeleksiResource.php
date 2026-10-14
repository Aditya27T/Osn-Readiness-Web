<?php

namespace App\Http\Resources;

use App\Models\TingkatSeleksi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TingkatSeleksiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var TingkatSeleksi $tingkat */
        $tingkat = $this->resource;

        return [
            'id' => $tingkat->id,
            'nama_tingkat' => $tingkat->nama_tingkat,
            'deskripsi' => $tingkat->deskripsi,
            'urutan' => $tingkat->urutan,
            'created_at' => $tingkat->created_at,
            'updated_at' => $tingkat->updated_at,
        ];
    }
}
