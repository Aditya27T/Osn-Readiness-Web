<?php

namespace App\Http\Resources;

use App\Models\Materi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Materi resource untuk admin dan siswa view.
 */
class MateriResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Materi $materi */
        $materi = $this->resource;

        return [
            'id' => $materi->id,
            'id_sumber' => $materi->id_sumber,
            'tingkat_id' => $materi->tingkat_id,
            'kompetensi_id' => $materi->kompetensi_id,
            'urutan' => $materi->urutan,
            'judul' => $materi->judul,
            'deskripsi' => $materi->deskripsi,
            'isi_materi' => $materi->isi_materi,
            'file_materi' => $this->getFileMateriUrl($materi->file_materi),
            'created_at' => $materi->created_at,
            'updated_at' => $materi->updated_at,
        ];
    }

    private function getFileMateriUrl(?string $filePath): ?string
    {
        return $filePath === null ? null : Storage::disk('public')->url($filePath);
    }
}
