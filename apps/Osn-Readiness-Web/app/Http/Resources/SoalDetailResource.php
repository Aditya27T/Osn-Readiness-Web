<?php

namespace App\Http\Resources;

use App\Models\Soal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Detailed Soal resource untuk admin view - termasuk kunci_jawaban.
 */
class SoalDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Soal $soal */
        $soal = $this->resource;

        return [
            'id' => $soal->id,
            'id_sumber' => $soal->id_sumber,
            'tingkat_id' => $soal->tingkat_id,
            'materi_id' => $soal->materi_id,
            'konteks_id' => $soal->konteks_id,
            'level' => $soal->level->value,
            'peruntukan' => $soal->peruntukan->value,
            'tipe_soal' => $soal->tipe_soal->value,
            'pertanyaan' => $soal->pertanyaan,
            'pilihan_jawaban' => $soal->pilihan_jawaban,
            'kunci_jawaban' => $soal->kunci_jawaban,
            'gambar' => $this->getGambarUrl($soal->gambar),
            'materi' => $this->whenLoaded('materi', fn () => [
                'id' => $soal->materi->id,
                'judul' => $soal->materi->judul,
            ]),
            'konteks' => $this->whenLoaded('konteks', fn () => $soal->konteks === null ? null : [
                'id' => $soal->konteks->id,
                'judul' => $soal->konteks->judul,
                'isi_konteks' => $soal->konteks->isi_konteks,
                'gambar' => $soal->konteks->gambar === null
                    ? null
                    : Storage::disk('public')->url($soal->konteks->gambar),
            ]),
            'pembahasan' => $this->whenLoaded('pembahasan', fn () => $soal->pembahasan === null ? null : [
                'id' => $soal->pembahasan->id,
                'isi_pembahasan' => $soal->pembahasan->isi_pembahasan,
            ]),
            'created_at' => $soal->created_at,
            'updated_at' => $soal->updated_at,
            'deleted_at' => $soal->deleted_at,
        ];
    }

    private function getGambarUrl(?string $gambar): ?string
    {
        return $gambar === null ? null : Storage::disk('public')->url($gambar);
    }
}
