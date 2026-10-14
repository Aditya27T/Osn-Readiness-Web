<?php

namespace App\Http\Resources;

use App\Models\Kompetensi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KompetensiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Kompetensi $kompetensi */
        $kompetensi = $this->resource;

        return [
            'id' => $kompetensi->id,
            'tingkat_id' => $kompetensi->tingkat_id,
            'nama_kompetensi' => $kompetensi->nama_kompetensi,
            'deskripsi' => $kompetensi->deskripsi,
            'created_at' => $kompetensi->created_at,
            'updated_at' => $kompetensi->updated_at,
        ];
    }
}
