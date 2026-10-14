<?php

namespace App\Services\Admin;

use App\Contracts\Repositories\AturanPemetaanRepositoryInterface;
use App\Models\AturanPemetaan;
use App\Models\Materi;
use App\Models\TingkatSeleksi;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Lapisan tampilan untuk admin di atas tabel aturan_pemetaan yang memakai
 * bentuk baris per parameter (parameter + ketentuan).
 *
 * Admin melihat satu set nilai flat per tingkat; service yang menerjemahkan
 * bolak-balik antara bentuk flat itu dan baris-baris di tabel.
 */
class AturanPemetaanService
{
    /**
     * Urutan parameter saat ditampilkan ke admin.
     *
     * @var list<string>
     */
    private const PARAMETER = [
        'bobot_mudah',
        'bobot_sedang',
        'bobot_sulit',
        'pretest_jumlah_soal',
        'pretest_persen_mudah',
        'pretest_persen_sedang',
        'pretest_persen_sulit',
        'pretest_min_soal_per_materi',
        'jumlah_materi_wajib',
        'latihan_min_soal',
        'latihan_min_nilai',
        'simulasi_persen_mudah',
        'simulasi_persen_sedang',
        'simulasi_persen_sulit',
        'simulasi_maks_percobaan',
        'passing_grade',
    ];

    public function __construct(
        private readonly AturanPemetaanRepositoryInterface $aturanRepository,
    ) {}

    /**
     * @return array<string, int|float>|null null bila tingkat belum punya aturan
     */
    public function perTingkat(int $tingkatId): ?array
    {
        $baris = $this->aturanRepository->untukTingkat($tingkatId);

        if ($baris->isEmpty()) {
            return null;
        }

        return $this->barisKeFlat($baris);
    }

    /**
     * @param  array<string, int|float>  $data
     *
     * @throws ValidationException
     */
    public function perbarui(TingkatSeleksi $tingkat, array $data): array
    {
        $this->validasiLintasBatas($tingkat, $data);

        DB::transaction(function () use ($tingkat, $data): void {
            $this->aturanRepository->simpanBanyak(
                $tingkat->id,
                array_map(
                    static fn (int|float $nilai): string => (string) $nilai,
                    $data
                ),
            );
        });

        return $this->perTingkat($tingkat->id) ?? throw new RuntimeException(
            "Aturan pemetaan tingkat {$tingkat->id} tidak ditemukan setelah disimpan."
        );
    }

    /**
     * @param  Collection<int, AturanPemetaan>  $baris
     * @return array<string, int|float>
     */
    public function barisKeFlat(Collection $baris): array
    {
        $berindeks = $baris->keyBy('parameter');
        $flat = [];

        foreach (self::PARAMETER as $parameter) {
            $barisAturan = $berindeks->get($parameter);

            if ($barisAturan === null) {
                continue;
            }

            $flat[$parameter] = $this->angka($barisAturan->ketentuan);
        }

        return $flat;
    }

    /**
     * Aturan lintas-batas yang tidak bisa dinyatakan sebagai rule per field.
     *
     * @param  array<string, int|float>  $data
     *
     * @throws ValidationException
     */
    private function validasiLintasBatas(TingkatSeleksi $tingkat, array $data): void
    {
        $persenPretest = $data['pretest_persen_mudah'] + $data['pretest_persen_sedang'] + $data['pretest_persen_sulit'];

        if ($persenPretest !== 100) {
            throw ValidationException::withMessages([
                'pretest_persen_mudah' => "Persentase level pre-test harus berjumlah 100, sekarang {$persenPretest}.",
            ]);
        }

        $persenSimulasi = $data['simulasi_persen_mudah'] + $data['simulasi_persen_sedang'] + $data['simulasi_persen_sulit'];

        if ($persenSimulasi !== 100) {
            throw ValidationException::withMessages([
                'simulasi_persen_mudah' => "Persentase level simulasi harus berjumlah 100, sekarang {$persenSimulasi}.",
            ]);
        }

        $jumlahMateri = Materi::query()->where('tingkat_id', $tingkat->id)->count();

        if ($data['jumlah_materi_wajib'] > $jumlahMateri) {
            throw ValidationException::withMessages([
                'jumlah_materi_wajib' => "Jumlah materi wajib ({$data['jumlah_materi_wajib']}) tidak boleh melebihi jumlah materi tingkat ini ({$jumlahMateri}).",
            ]);
        }

        $kebutuhanSoal = $data['pretest_min_soal_per_materi'] * $jumlahMateri;

        if ($kebutuhanSoal > $data['pretest_jumlah_soal']) {
            throw ValidationException::withMessages([
                'pretest_jumlah_soal' => "Jumlah soal pre-test ({$data['pretest_jumlah_soal']}) kurang untuk min_soal_per_materi {$data['pretest_min_soal_per_materi']} dikali {$jumlahMateri} materi (butuh {$kebutuhanSoal}).",
            ]);
        }
    }

    private function angka(string $ketentuan): int|float
    {
        return str_contains($ketentuan, '.') ? (float) $ketentuan : (int) $ketentuan;
    }
}
