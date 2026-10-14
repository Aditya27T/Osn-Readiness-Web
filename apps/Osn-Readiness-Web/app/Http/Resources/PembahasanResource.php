<?php

namespace App\Http\Resources;

use App\Models\Pembahasan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Pembahasan resource untuk admin view.
 */
class PembahasanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Pembahasan $pembahasan */
        $pembahasan = $this->resource;

        return [
            'id' => $pembahasan->id,
            'soal_id' => $pembahasan->soal_id,
            'isi_pembahasan' => $pembahasan->isi_pembahasan,
            'soal' => $this->whenLoaded('soal', fn () => [
                'id' => $pembahasan->soal->id,
                'pertanyaan' => $pembahasan->soal->pertanyaan,
            ]),
        ];
    }
}
