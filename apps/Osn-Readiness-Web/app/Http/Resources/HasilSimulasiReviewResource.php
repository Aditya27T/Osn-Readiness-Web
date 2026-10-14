<?php

namespace App\Http\Resources;

use App\Models\HasilSimulasi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Review percobaan yang sudah selesai: jawaban siswa, status benar, kunci,
 * dan pembahasan tiap soal.
 */
class HasilSimulasiReviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var HasilSimulasi $hasil */
        $hasil = $this->resource;

        $hasil->jawaban->loadMissing(['soal.konteks', 'soal.pembahasan']);

        return [
            'id' => (int) $hasil->id,
            'simulasi_id' => (int) $hasil->simulasi_id,
            'pretest_id' => (int) $hasil->pretest_id,
            'nilai' => $hasil->nilai === null ? null : (float) $hasil->nilai,
            'jumlah_benar' => $hasil->jumlah_benar === null ? null : (int) $hasil->jumlah_benar,
            'jumlah_salah' => $hasil->jumlah_salah === null ? null : (int) $hasil->jumlah_salah,
            'lulus' => $hasil->lulus,
            'disubmit_pada' => $hasil->disubmit_pada?->toISOString(),
            'selesai_pada' => $hasil->selesai_pada?->toISOString(),
            'soal' => SoalReviewResource::collection($hasil->jawaban),
        ];
    }
}
