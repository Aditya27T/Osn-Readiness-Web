<?php

namespace App\Http\Resources;

use App\DTOs\SimulasiDimulai;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Simulasi yang sedang berjalan: soal, urutan, bobot, jawaban tersimpan,
 * dan sisa waktu, supaya siswa bisa menyambung setelah reload.
 */
class SimulasiPengerjaanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var SimulasiDimulai $dimulai */
        $dimulai = $this->resource;

        $hasil = $dimulai->hasil;

        $sudahDijawab = $hasil->relationLoaded('jawaban')
            ? $hasil->jawaban->keyBy('soal_id')
            : collect();

        $soal = $dimulai->soal->map(fn ($soal): array => [
            'soal' => $soal,
            'urutan' => $sudahDijawab->get((int) $soal->id)?->urutan,
            'bobot' => $sudahDijawab->get((int) $soal->id)?->bobot,
            'jawaban_user' => $sudahDijawab->get((int) $soal->id)?->jawaban_user,
        ]);

        return [
            'id' => $hasil->id,
            'simulasi_id' => $hasil->simulasi_id,
            'pretest_id' => $hasil->pretest_id,
            'mulai_pada' => $hasil->mulai_pada,
            'batas_pada' => $hasil->batas_pada,
            'disubmit_pada' => $hasil->disubmit_pada,
            'selesai_pada' => $hasil->selesai_pada,
            'nilai' => $hasil->nilai === null ? null : (float) $hasil->nilai,
            'soal' => SoalPengerjaanResource::collection($soal),
        ];
    }
}
