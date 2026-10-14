<?php

namespace App\Services;

use App\Contracts\Services\AturanServiceInterface;
use App\Contracts\Services\BankSoalServiceInterface;
use App\Enums\Level;
use App\Enums\Peruntukan;
use App\Models\Materi;
use App\Models\Quiz;
use App\Models\Simulasi;
use App\Models\Soal;
use App\Support\KuotaLevel;

class BankSoalService implements BankSoalServiceInterface
{
    /** Ambang peringatan putaran pre-test (keputusan #3). */
    private const AMBANG_PUTARAN = 2;

    public function __construct(
        private readonly AturanServiceInterface $aturanService,
    ) {}

    public function laporan(int $tingkatId): array
    {
        $aturan = $this->aturanService->untukTingkat($tingkatId);

        $kuotaPretest = $this->kuotaLevel($aturan->pretestJumlahSoal, $aturan->persenLevelPretest);

        // "Tersedia" adalah stok bank, sama dengan kandidat yang dilihat
        // SoalPicker. Larangan mengulang soal pre-test berlaku per siswa,
        // jadi soal yang pernah dipakai siswa lain tetap tersedia; bagian
        // putaran_pretest yang menjawab berapa kali satu siswa bisa
        // mengulang pre-test tanpa soal berulang.
        $pretest = Soal::query()
            ->where('tingkat_id', $tingkatId)
            ->where('peruntukan', Peruntukan::Pretest->value)
            ->get();

        $simulasi = Soal::query()
            ->where('tingkat_id', $tingkatId)
            ->where('peruntukan', Peruntukan::Simulasi->value)
            ->get();

        $latihan = Soal::query()
            ->where('tingkat_id', $tingkatId)
            ->where('peruntukan', Peruntukan::Latihan->value)
            ->get();

        $materi = Materi::query()->where('tingkat_id', $tingkatId)->orderBy('urutan')->get();

        return [
            'tingkat_id' => $tingkatId,
            'pretest_per_level' => $this->bagianPretestPerLevel($pretest, $kuotaPretest),
            'pretest_per_materi' => $this->bagianPretestPerMateri($pretest, $materi, $aturan->pretestMinSoalPerMateri),
            'putaran_pretest' => $this->bagianPutaranPretest($pretest, $kuotaPretest),
            'simulasi_per_level' => $this->bagianSimulasi($tingkatId, $simulasi, $aturan->persenLevelSimulasi),
            'latihan_per_materi' => $this->bagianLatihan($latihan, $materi),
        ];
    }

    public function cukupUntukSimulasi(int $tingkatId, int $jumlahSoal): bool
    {
        $aturan = $this->aturanService->untukTingkat($tingkatId);
        $kuota = $this->kuotaLevel($jumlahSoal, $aturan->persenLevelSimulasi);

        foreach ($kuota as $level => $kuotaLevel) {
            $tersedia = Soal::query()
                ->where('tingkat_id', $tingkatId)
                ->where('peruntukan', Peruntukan::Simulasi->value)
                ->where('level', self::caseAt($level))
                ->count();

            if ($tersedia < $kuotaLevel) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  iterable<Soal>  $pretest
     * @return array<int, array{level: string, tersedia: int, kuota: int, kurang: bool}>
     */
    private function bagianPretestPerLevel(iterable $pretest, array $kuotaPretest): array
    {
        $tersedia = [];

        foreach ($pretest as $soal) {
            $tersedia[$soal->level->value] = ($tersedia[$soal->level->value] ?? 0) + 1;
        }

        $bagian = [];

        foreach ($kuotaPretest as $level => $kuota) {
            $jumlah = $tersedia[self::caseAt($level)->value] ?? 0;

            $bagian[] = [
                'level' => self::caseAt($level)->value,
                'tersedia' => $jumlah,
                'kuota' => $kuota,
                'kurang' => $jumlah < $kuota,
            ];
        }

        return $bagian;
    }

    /**
     * @param  iterable<Soal>  $pretest
     * @param  iterable<Materi>  $materi
     * @return list<array{materi_id: int, judul: string, tersedia: int, minimal: int, kurang: bool}>
     */
    private function bagianPretestPerMateri(iterable $pretest, iterable $materi, int $minimal): array
    {
        $perMateri = [];

        foreach ($pretest as $soal) {
            $perMateri[(int) $soal->materi_id] = ($perMateri[(int) $soal->materi_id] ?? 0) + 1;
        }

        $bagian = [];

        foreach ($materi as $satu) {
            $tersedia = $perMateri[(int) $satu->id] ?? 0;

            $bagian[] = [
                'materi_id' => (int) $satu->id,
                'judul' => (string) $satu->judul,
                'tersedia' => $tersedia,
                'minimal' => $minimal,
                'kurang' => $tersedia < $minimal,
            ];
        }

        return $bagian;
    }

    /**
     * Berapa pre-test tanpa soal berulang yang masih bisa dilayani: nilai
     * terkecil dari tersedia dibagi kuota di tiap level.
     *
     * @param  iterable<Soal>  $pretest
     * @return array{putaran: int, ambang: int, kurang: bool}
     */
    private function bagianPutaranPretest(iterable $pretest, array $kuotaPretest): array
    {
        $tersedia = [];

        foreach ($pretest as $soal) {
            $tersedia[$soal->level->value] = ($tersedia[$soal->level->value] ?? 0) + 1;
        }

        $putaran = null;

        foreach ($kuotaPretest as $level => $kuota) {
            if ($kuota === 0) {
                continue;
            }

            $jumlah = intdiv($tersedia[self::caseAt($level)->value] ?? 0, $kuota);

            $putaran = $putaran === null ? $jumlah : min($putaran, $jumlah);
        }

        $putaran = $putaran ?? 0;

        return [
            'putaran' => $putaran,
            'ambang' => self::AMBANG_PUTARAN,
            'kurang' => $putaran < self::AMBANG_PUTARAN,
        ];
    }

    /**
     * @param  iterable<Soal>  $simulasi
     * @return list<array{simulasi_id: int, nama: string, is_aktif: bool, per_level: list<array{level: string, tersedia: int, kuota: int, kurang: bool}>}>
     */
    private function bagianSimulasi(int $tingkatId, iterable $simulasi, array $persenLevelSimulasi): array
    {
        $tersedia = [];

        foreach ($simulasi as $soal) {
            $tersedia[$soal->level->value] = ($tersedia[$soal->level->value] ?? 0) + 1;
        }

        $bagian = [];

        foreach (Simulasi::query()->where('tingkat_id', $tingkatId)->get() as $row) {
            $kuota = $this->kuotaLevel((int) $row->jumlah_soal, $persenLevelSimulasi);
            $perLevel = [];

            foreach ($kuota as $level => $kuotaLevel) {
                $jumlah = $tersedia[self::caseAt($level)->value] ?? 0;

                $perLevel[] = [
                    'level' => self::caseAt($level)->value,
                    'tersedia' => $jumlah,
                    'kuota' => $kuotaLevel,
                    'kurang' => $jumlah < $kuotaLevel,
                ];
            }

            $bagian[] = [
                'simulasi_id' => (int) $row->id,
                'nama' => (string) $row->nama_simulasi,
                'is_aktif' => (bool) $row->is_aktif,
                'per_level' => $perLevel,
            ];
        }

        return $bagian;
    }

    /**
     * @param  iterable<Soal>  $latihan
     * @param  iterable<Materi>  $materi
     * @return list<array{materi_id: int, judul: string, punya_latihan: bool, tersedia: int, dibutuhkan: int|null, kurang: bool}>
     */
    private function bagianLatihan(iterable $latihan, iterable $materi): array
    {
        $perMateri = [];

        foreach ($latihan as $soal) {
            $perMateri[(int) $soal->materi_id] = ($perMateri[(int) $soal->materi_id] ?? 0) + 1;
        }

        $bagian = [];

        foreach ($materi as $satu) {
            /** @var Quiz|null $quiz */
            $quiz = $satu->quiz;

            $bagian[] = [
                'materi_id' => (int) $satu->id,
                'judul' => (string) $satu->judul,
                'punya_latihan' => $quiz !== null,
                'tersedia' => $perMateri[(int) $satu->id] ?? 0,
                'dibutuhkan' => $quiz?->jumlah_soal === null ? null : (int) $quiz->jumlah_soal,
                'kurang' => $quiz === null
                    || ($perMateri[(int) $satu->id] ?? 0) < (int) $quiz->jumlah_soal,
            ];
        }

        return $bagian;
    }

    private static function caseAt(int $index): Level
    {
        return Level::cases()[$index];
    }

    /**
     * Persen level ('mudah' => 50) diurutkan ke indeks 0/1/2 seperti yang
     * dibaca KuotaLevel, lalu dihitung dengan fungsi yang sama milik picker.
     *
     * @param  array<string, float>  $persenLevel
     * @return array<int, int>
     */
    private function kuotaLevel(int $jumlah, array $persenLevel): array
    {
        $terurut = [];

        foreach (Level::cases() as $level) {
            $terurut[$level->index()] = $persenLevel[$level->value] ?? 0.0;
        }

        return KuotaLevel::hitung($jumlah, $terurut);
    }
}
