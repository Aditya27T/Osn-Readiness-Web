<?php

namespace App\Support;

use App\Enums\TipeSoal;
use App\Models\KonteksSoal;
use App\Models\Materi;

/**
 * Aturan isi soal yang sama untuk tambah dan ubah lewat admin (BE-17).
 *
 * Format pilihan mengikuti importer: objek berkunci huruf ({"A": "..."})
 * dengan kunci_jawaban berupa salah satu hurufnya.
 */
final class AturanSoal
{
    /**
     * @param  array<string, mixed>  $soal  nilai akhir soal (request digabung nilai lama saat ubah)
     * @param  bool  $pilihanDikirim  format huruf hanya dituntut dari pilihan yang dikirim request ini
     * @return array<string, string> pesan kesalahan per kolom
     */
    public static function periksa(array $soal, ?Materi $materi, ?KonteksSoal $konteks, bool $pilihanDikirim): array
    {
        $galat = [];
        $tingkatId = (int) ($soal['tingkat_id'] ?? 0);

        if ($materi instanceof Materi && (int) $materi->tingkat_id !== $tingkatId) {
            $galat['materi_id'] = 'Materi harus berasal dari tingkat yang sama dengan soal.';
        }

        if ($konteks instanceof KonteksSoal && (int) $konteks->tingkat_id !== $tingkatId) {
            $galat['konteks_id'] = 'Cerita soal harus berasal dari tingkat yang sama dengan soal.';
        }

        $pilihan = $soal['pilihan_jawaban'] ?? null;
        $adaPilihan = is_array($pilihan) && $pilihan !== [];

        if (($soal['tipe_soal'] ?? null) === TipeSoal::Isian->value) {
            if ($adaPilihan) {
                $galat['pilihan_jawaban'] = 'Soal isian tidak punya pilihan jawaban.';
            }

            return $galat;
        }

        if (! $adaPilihan || count($pilihan) < 2) {
            $galat['pilihan_jawaban'] = 'Soal pilihan ganda butuh minimal dua pilihan.';

            return $galat;
        }

        if ($pilihanDikirim) {
            foreach (array_keys($pilihan) as $huruf) {
                if (preg_match('/^[A-Z]$/', (string) $huruf) !== 1) {
                    $galat['pilihan_jawaban'] = 'Pilihan jawaban harus berkunci huruf A sampai Z, misalnya {"A": "...", "B": "..."}.';

                    return $galat;
                }
            }
        }

        if (! array_key_exists((string) ($soal['kunci_jawaban'] ?? ''), $pilihan)) {
            $galat['kunci_jawaban'] = 'Kunci jawaban harus salah satu huruf pilihan.';
        }

        return $galat;
    }
}
