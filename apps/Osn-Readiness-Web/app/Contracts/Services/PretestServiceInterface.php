<?php

namespace App\Contracts\Services;

use App\DTOs\HasilPretest;
use App\DTOs\PretestDimulai;
use App\Exceptions\BankSoalTidakCukupException;
use App\Exceptions\PerhitunganTidakTersediaException;
use App\Exceptions\PutaranMasihBerjalanException;
use App\Exceptions\SudahLulusException;
use App\Exceptions\TingkatTerkunciException;
use App\Models\User;

interface PretestServiceInterface extends PenilaianServiceInterface
{
    /**
     * Mulai pre-test, atau kembalikan pre-test yang sedang berjalan.
     *
     * @throws TingkatTerkunciException 403
     * @throws SudahLulusException 409
     * @throws PutaranMasihBerjalanException 409
     * @throws BankSoalTidakCukupException 503
     */
    public function mulai(User $user, int $tingkatId): PretestDimulai;

    /**
     * Pre-test milik siswa beserta soal dan jawabannya, untuk opener atau
     * menyambung setelah reload. 404 bila bukan miliknya.
     */
    public function ringkasan(User $user, int $pretestId): PretestDimulai;

    /**
     * Hasil penilaian yang sudah tersimpan, atau null bila belum dinilai.
     */
    public function hasil(User $user, int $pretestId): ?HasilPretest;

    /**
     * Simpan satu jawaban. 404 bila pre-test bukan miliknya.
     */
    public function simpanJawaban(User $user, int $pretestId, int $soalId, ?string $jawaban): void;

    /**
     * Kunci jawaban lalu nilai. Bila Python tidak tersedia, jawaban tetap
     * terkunci dan NilaiUlangJob dikirimkan.
     *
     * @throws PerhitunganTidakTersediaException 503
     */
    public function submit(User $user, int $pretestId): HasilPretest;
}
