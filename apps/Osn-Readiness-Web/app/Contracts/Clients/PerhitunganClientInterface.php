<?php

namespace App\Contracts\Clients;

use App\Exceptions\PerhitunganKonfigurasiException;
use App\Exceptions\PerhitunganTidakTersediaException;

/**
 * Satu-satunya jalan keluar aplikasi menuju layanan hitung Python.
 *
 * Service lain tidak tahu soal HTTP maupun bentuk JSON, sehingga logikanya bisa
 * dipindah ke PHP dengan mengganti satu kelas saja.
 *
 * Bentuk array yang dikembalikan adalah kontrak ke service. Bila kontrak Python
 * berubah, sesuaikan hanya di dalam PerhitunganClient.
 */
interface PerhitunganClientInterface
{
    /**
     * Nilai satu paket soal tanpa pemetaan materi, dipakai latihan dan simulasi.
     *
     * @param  list<array{soal_id: int, tipe_soal: string, bobot: int, jawaban_user: ?string, kunci_jawaban: string}>  $soal
     * @return array{nilai: float, jawaban: list<array{soal_id: int, status_benar: bool}>}
     *
     * @throws PerhitunganTidakTersediaException
     * @throws PerhitunganKonfigurasiException
     */
    public function hitungPenilaian(array $soal): array;

    /**
     * Nilai pre-test sekaligus pemetaan per materi dan daftar materi wajib.
     *
     * @param  list<array{soal_id: int, materi_id: int, tipe_soal: string, bobot: int, jawaban_user: ?string, kunci_jawaban: string}>  $soal
     * @param  list<array{materi_id: int, urutan: int}>  $materi  daftar materi tingkat itu beserta urutannya
     * @param  int  $jumlahMateriWajib  sesuai aturan pemetaan tingkat tersebut
     * @return array{
     *     nilai: float,
     *     jawaban: list<array{soal_id: int, status_benar: bool}>,
     *     pemetaan: list<array{materi_id: int, jumlah_soal: int, jumlah_benar: int, poin_didapat: int, poin_maksimal: int, persentase: float, peringkat: int}>,
     *     materi_wajib: list<array{materi_id: int, prioritas: int}>
     * }
     *
     * @throws PerhitunganTidakTersediaException
     * @throws PerhitunganKonfigurasiException
     */
    public function hitungPretest(array $soal, array $materi, int $jumlahMateriWajib): array;
}
