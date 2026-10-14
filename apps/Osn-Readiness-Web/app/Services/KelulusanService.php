<?php

namespace App\Services;

use App\Contracts\Repositories\HasilSimulasiRepositoryInterface;
use App\Contracts\Repositories\KenaikanTingkatRepositoryInterface;
use App\Contracts\Repositories\MateriRepositoryInterface;
use App\Contracts\Repositories\ProgressBelajarRepositoryInterface;
use App\Contracts\Repositories\QuizPengerjaanRepositoryInterface;
use App\Contracts\Repositories\TingkatSeleksiRepositoryInterface;
use App\Contracts\Services\AturanServiceInterface;
use App\Contracts\Services\KelulusanServiceInterface;
use App\Models\HasilSimulasi;
use App\Models\Materi;

class KelulusanService implements KelulusanServiceInterface
{
    public function __construct(
        private readonly HasilSimulasiRepositoryInterface $hasilRepository,
        private readonly ProgressBelajarRepositoryInterface $progressRepository,
        private readonly QuizPengerjaanRepositoryInterface $quizPengerjaanRepository,
        private readonly MateriRepositoryInterface $materiRepository,
        private readonly AturanServiceInterface $aturanService,
        private readonly TingkatSeleksiRepositoryInterface $tingkatRepository,
        private readonly KenaikanTingkatRepositoryInterface $kenaikanRepository,
    ) {}

    public function menilai(HasilSimulasi $hasil): array
    {
        $simulasi = $hasil->simulasi;
        $tingkat = $simulasi->tingkat;

        $aturan = $this->aturanService->untukTingkat((int) $tingkat->id);
        $passingGrade = (float) $aturan->passingGrade;
        $nilai = (float) $hasil->nilai;

        $hasil->forceFill(['lulus' => $nilai >= $passingGrade])->save();

        if ($nilai >= $passingGrade) {
            return [
                'lulus' => true,
                'tingkat_tujuan_id' => $this->tulisKenaikanLulus($hasil, (int) $tingkat->id),
            ];
        }

        $maksimal = (int) $aturan->simulasiMaksPercobaan;
        $selesai = $this->hasilRepository->jumlahSelesai((int) $hasil->pretest_id);

        // Belum mencapai batas: hanya lulus = salah, tanpa tindakan lain.
        if ($selesai < $maksimal) {
            return ['lulus' => false, 'tingkat_tujuan_id' => null];
        }

        // Kegagalan terakhir: pre-test, pemetaan, materi wajib, dan hasil
        // simulasi putaran lama TIDAK dihapus. Yang dihapus hanya progres
        // belajar materi tingkat ini dan seluruh pengerjaan latihan materi
        // tingkat ini (jawabannya ikut terhapus lewat cascade).
        $materiIds = $this->materiRepository->untukTingkat((int) $tingkat->id)
            ->map(fn (Materi $materi): int => (int) $materi->id)
            ->all();

        $this->progressRepository->hapusUntukMateri($hasil->user, $materiIds);
        $this->quizPengerjaanRepository->hapusUntukMateri($hasil->user, $materiIds);

        $this->tulisKenaikanTidakLulus($hasil, (int) $tingkat->id, $passingGrade);

        return ['lulus' => false, 'tingkat_tujuan_id' => null];
    }

    private function tulisKenaikanLulus(HasilSimulasi $hasil, int $tingkatId): ?int
    {
        $berikutnya = $this->tingkatRepository->semuaTerurut()
            ->firstWhere('urutan', '>', $this->urutanTingkat($tingkatId));

        // Index kenaikan_lulus_unique menjadi penjaga bila penilaian terpicu
        // dua kali; baris yang sudah ada tetap dipakai.
        $this->kenaikanRepository->catatLulus((int) $hasil->user_id, $tingkatId, $berikutnya?->id);

        return $berikutnya?->id;
    }

    private function urutanTingkat(int $tingkatId): int
    {
        $tingkat = $this->tingkatRepository->findOrFail($tingkatId);

        return (int) $tingkat->urutan;
    }

    private function tulisKenaikanTidakLulus(HasilSimulasi $hasil, int $tingkatId, float $passingGrade): void
    {
        $terbaik = $this->hasilRepository->nilaiTerbaik((int) $hasil->pretest_id) ?? 0.0;

        $this->kenaikanRepository->catatTidakLulus(
            (int) $hasil->user_id,
            $tingkatId,
            "Nilai terbaik {$terbaik} dari passing grade {$passingGrade}.",
        );
    }
}
