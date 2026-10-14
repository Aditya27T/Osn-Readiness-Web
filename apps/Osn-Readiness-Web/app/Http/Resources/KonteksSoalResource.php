<?php

namespace App\Http\Resources;

use App\Models\KonteksSoal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class KonteksSoalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var KonteksSoal $konteksSoal */
        $konteksSoal = $this->resource;

        return [
            'id' => $konteksSoal->id,
            'tingkat_id' => $konteksSoal->tingkat_id,
            'judul' => $konteksSoal->judul,
            'isi_konteks' => $konteksSoal->isi_konteks,
            'gambar' => $konteksSoal->gambar === null ? null : Storage::disk('public')->url($konteksSoal->gambar),
            'created_at' => $konteksSoal->created_at,
            'updated_at' => $konteksSoal->updated_at,
        ];
    }
}
