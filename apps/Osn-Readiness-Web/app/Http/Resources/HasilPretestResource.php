<?php

namespace App\Http\Resources;

use App\DTOs\HasilPretest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Hasil pre-test: nilai, pemetaan per materi, dan materi wajib yang harus
 * dipelajari dulu.
 */
class HasilPretestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var HasilPretest $hasil */
        $hasil = $this->resource;

        return [
            'id' => $hasil->pretest->id,
            'tingkat_id' => $hasil->pretest->tingkat_id,
            'nilai' => $hasil->pretest->nilai === null ? null : (float) $hasil->pretest->nilai,
            'disubmit_pada' => $hasil->pretest->disubmit_pada,
            'selesai_pada' => $hasil->pretest->selesai_pada,
            'pemetaan' => $hasil->pemetaan->map(fn ($baris): array => [
                'materi_id' => $baris->materi_id,
                'jumlah_soal' => $baris->jumlah_soal,
                'jumlah_benar' => $baris->jumlah_benar,
                'poin_didapat' => $baris->poin_didapat,
                'poin_maksimal' => $baris->poin_maksimal,
                'persentase' => (float) $baris->persentase,
                'peringkat' => $baris->peringkat,
            ])->values(),
            'materi_wajib' => $hasil->materiWajib->map(fn ($baris): array => [
                'materi_id' => $baris->materi_id,
                'prioritas' => $baris->prioritas,
            ])->values(),
        ];
    }
}
