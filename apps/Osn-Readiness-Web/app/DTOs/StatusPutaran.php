<?php

namespace App\DTOs;

use App\Enums\TahapSiswa;

/**
 * Keadaan siswa di satu tingkat, seluruhnya diturunkan dari data.
 *
 * Delapan nilai turunan: tingkat_terbuka, sudah_lulus, pretest_berjalan,
 * putaran_aktif, percobaan_terpakai, simulasi_berjalan, putaran_habis, dan
 * boleh_pretest_baru. Tidak ada kolom status di database.
 *
 * sisaKuotaSimulasi diturunkan dari percobaan_terpakai dan aturan tingkat,
 * supaya dashboard dan daftar simulasi tidak menghitungnya sendiri-sendiri.
 *
 * Id disimpan, bukan hanya boolean, supaya pemanggil tidak perlu query ulang
 * untuk membuka pre-test atau simulasi yang sedang berjalan.
 */
final class StatusPutaran
{
    public function __construct(
        public readonly int $tingkatId,
        public readonly bool $tingkatTerbuka,
        public readonly bool $sudahLulus,
        public readonly ?int $pretestBerjalanId,
        public readonly ?int $putaranAktifId,
        public readonly int $percobaanTerpakai,
        public readonly ?int $simulasiBerjalanId,
        public readonly bool $putaranHabis,
        public readonly bool $bolehPretestBaru,
        public readonly TahapSiswa $tahap,
        public readonly int $sisaKuotaSimulasi = 0,
    ) {}

    public function pretestBerjalan(): bool
    {
        return $this->pretestBerjalanId !== null;
    }

    public function putaranAktif(): bool
    {
        return $this->putaranAktifId !== null;
    }

    public function simulasiBerjalan(): bool
    {
        return $this->simulasiBerjalanId !== null;
    }
}
