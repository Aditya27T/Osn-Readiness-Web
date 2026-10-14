<?php

namespace App\Http\Resources;

use App\DTOs\PretestDimulai;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Pre-test yang sedang berjalan: soal, urutan, bobot, dan jawaban yang sudah
 * tersimpan, supaya siswa bisa menyambung setelah reload.
 */
class PretestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PretestDimulai $dimulai */
        $dimulai = $this->resource;

        $sudahDijawab = $dimulai->pretest->relationLoaded('jawaban')
            ? $dimulai->pretest->jawaban->keyBy('soal_id')
            : collect();

        $soal = $dimulai->soal->map(fn ($soal): array => [
            'soal' => $soal,
            'urutan' => $sudahDijawab->get((int) $soal->id)?->urutan,
            'bobot' => $sudahDijawab->get((int) $soal->id)?->bobot,
            'jawaban_user' => $sudahDijawab->get((int) $soal->id)?->jawaban_user,
        ]);

        return [
            'id' => $dimulai->pretest->id,
            'tingkat_id' => $dimulai->pretest->tingkat_id,
            'disubmit_pada' => $dimulai->pretest->disubmit_pada,
            'selesai_pada' => $dimulai->pretest->selesai_pada,
            'soal' => SoalPengerjaanResource::collection($soal),
        ];
    }
}
