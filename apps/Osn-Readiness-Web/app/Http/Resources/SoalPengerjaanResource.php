<?php

namespace App\Http\Resources;

use App\Models\Soal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Satu soal di dalam pre-test atau latihan, lengkap dengan urutan, bobot, dan
 * jawaban yang sudah tersimpan.
 *
 * Isinya dibangun di atas SoalResource, sehingga kunci jawaban tidak mungkin
 * ikut terbawa walau kelas ini lupa dicek ulang.
 */
class SoalPengerjaanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{soal: Soal, urutan: ?int, bobot: ?int, jawaban_user: ?string} $baris */
        $baris = $this->resource;

        return array_merge(
            (new SoalResource($baris['soal']))->toArray($request),
            [
                'urutan' => $baris['urutan'],
                'bobot' => $baris['bobot'],
                'jawaban_user' => $baris['jawaban_user'],
            ],
        );
    }
}
