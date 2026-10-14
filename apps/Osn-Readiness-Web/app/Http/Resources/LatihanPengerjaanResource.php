<?php

namespace App\Http\Resources;

use App\DTOs\LatihanDimulai;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Latihan yang sedang berjalan: soal, urutan, bobot, dan jawaban yang sudah
 * tersimpan, supaya siswa bisa menyambung setelah reload. Mirip
 * PretestResource; identitas quiz dan materinya ikut ditampilkan.
 */
class LatihanPengerjaanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var LatihanDimulai $dimulai */
        $dimulai = $this->resource;

        $pengerjaan = $dimulai->pengerjaan;

        $sudahDijawab = $pengerjaan->relationLoaded('jawaban')
            ? $pengerjaan->jawaban->keyBy('soal_id')
            : collect();

        $soal = $dimulai->soal->map(fn ($soal): array => [
            'soal' => $soal,
            'urutan' => $sudahDijawab->get((int) $soal->id)?->urutan,
            'bobot' => $sudahDijawab->get((int) $soal->id)?->bobot,
            'jawaban_user' => $sudahDijawab->get((int) $soal->id)?->jawaban_user,
        ]);

        return [
            'id' => $pengerjaan->id,
            'quiz_id' => $pengerjaan->quiz_id,
            'materi_id' => $pengerjaan->quiz->materi_id,
            'materi_judul' => $pengerjaan->quiz->materi->judul,
            'disubmit_pada' => $pengerjaan->disubmit_pada,
            'selesai_pada' => $pengerjaan->selesai_pada,
            'nilai' => $pengerjaan->nilai,
            'soal' => SoalPengerjaanResource::collection($soal),
        ];
    }
}
