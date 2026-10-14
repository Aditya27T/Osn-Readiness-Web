<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Soal beserta kunci dan pembahasan untuk review setelah pengerjaan selesai.
 *
 * Membungkus baris jawaban (PretestJawaban, QuizJawaban, atau
 * HasilSimulasiJawaban) dengan relasi soal yang sudah dimuat bersama konteks
 * dan pembahasannya.
 */
class SoalReviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $soal = $this->resource->soal;

        return [
            'urutan' => $this->resource->urutan,
            'bobot' => $this->resource->bobot,
            'jawaban_user' => $this->resource->jawaban_user,
            'status_benar' => $this->resource->status_benar,
            'soal' => [
                'id' => $soal->id,
                'materi_id' => $soal->materi_id,
                'level' => $soal->level->value,
                'tipe_soal' => $soal->tipe_soal->value,
                'pertanyaan' => $soal->pertanyaan,
                'pilihan_jawaban' => $soal->pilihan_jawaban,
                'kunci_jawaban' => $soal->kunci_jawaban,
                'gambar' => $soal->gambar === null
                    ? null
                    : Storage::disk('public')->url($soal->gambar),
                'konteks' => $soal->konteks === null ? null : [
                    'judul' => $soal->konteks->judul,
                    'isi_konteks' => $soal->konteks->isi_konteks,
                ],
                'pembahasan' => $soal->pembahasan?->isi_pembahasan,
            ],
        ];
    }
}
