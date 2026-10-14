<?php

namespace App\Http\Resources;

use App\Models\QuizPengerjaan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Hasil latihan yang sudah dinilai: nilai, jawaban per soal, dan waktu.
 */
class HasilLatihanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var QuizPengerjaan $pengerjaan */
        $pengerjaan = $this->resource;

        return [
            'id' => (int) $pengerjaan->id,
            'quiz_id' => (int) $pengerjaan->quiz_id,
            'materi_id' => (int) $pengerjaan->quiz->materi_id,
            'nilai' => $pengerjaan->nilai === null ? null : (float) $pengerjaan->nilai,
            'disubmit_pada' => $pengerjaan->disubmit_pada?->toISOString(),
            'selesai_pada' => $pengerjaan->selesai_pada?->toISOString(),
            'jawaban' => $pengerjaan->jawaban->map(fn ($baris): array => [
                'soal_id' => (int) $baris->soal_id,
                'urutan' => (int) $baris->urutan,
                'bobot' => (int) $baris->bobot,
                'jawaban_user' => $baris->jawaban_user,
                'status_benar' => $baris->status_benar,
            ])->all(),
        ];
    }
}
