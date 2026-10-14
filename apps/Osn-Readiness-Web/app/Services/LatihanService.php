<?php

namespace App\Services;

use App\Contracts\Clients\PerhitunganClientInterface;
use App\Contracts\Repositories\QuizJawabanRepositoryInterface;
use App\Contracts\Repositories\QuizPengerjaanRepositoryInterface;
use App\Contracts\Services\LatihanServiceInterface;
use App\Contracts\Services\PutaranServiceInterface;
use App\Contracts\Services\SoalPickerServiceInterface;
use App\DTOs\LatihanDimulai;
use App\DTOs\PermintaanSoal;
use App\Enums\JenisPengerjaan;
use App\Enums\Peruntukan;
use App\Exceptions\BelumPretestException;
use App\Exceptions\PengerjaanSudahDisubmitException;
use App\Exceptions\PerhitunganTidakTersediaException;
use App\Exceptions\TingkatTerkunciException;
use App\Jobs\NilaiUlangJob;
use App\Models\Quiz;
use App\Models\QuizJawaban;
use App\Models\QuizPengerjaan;
use App\Models\Soal;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LatihanService implements LatihanServiceInterface
{
    public function __construct(
        private readonly QuizPengerjaanRepositoryInterface $pengerjaanRepository,
        private readonly QuizJawabanRepositoryInterface $jawabanRepository,
        private readonly SoalPickerServiceInterface $soalPicker,
        private readonly PutaranServiceInterface $putaranService,
        private readonly PerhitunganClientInterface $perhitungan,
    ) {}

    /**
     * Mulai latihan, atau kembalikan pengerjaan yang belum disubmit untuk
     * quiz itu supaya siswa melanjutkan, bukan mengulang dari nol.
     */
    public function mulai(User $user, Quiz $quiz): LatihanDimulai
    {
        $materi = $quiz->materi;
        $tingkat = $materi->tingkat;

        $status = $this->putaranService->status($user, $tingkat);

        if (! $status->tingkatTerbuka) {
            throw new TingkatTerkunciException("Tingkat {$tingkat->nama_tingkat} belum terbuka.");
        }

        if ($status->putaranAktifId === null) {
            throw new BelumPretestException('Kerjakan latihan setelah pre-test memetakan materi.');
        }

        $berjalan = $this->pengerjaanRepository->berjalan($user, (int) $quiz->id);

        if ($berjalan instanceof QuizPengerjaan) {
            return $this->balasan($berjalan, baru: false);
        }

        $terpilih = $this->soalPicker->pilih(new PermintaanSoal(
            tingkatId: (int) $tingkat->id,
            peruntukan: Peruntukan::Latihan,
            jumlahSoal: (int) $quiz->jumlah_soal,
            // Latihan mengambil semua level tanpa kuota persentase.
            persenLevel: [],
            materiId: (int) $materi->id,
        ));

        try {
            $pengerjaan = DB::transaction(function () use ($user, $quiz, $terpilih): QuizPengerjaan {
                $pengerjaan = $this->pengerjaanRepository->create([
                    'user_id' => $user->id,
                    'quiz_id' => $quiz->id,
                ]);

                $this->jawabanRepository->buatBanyak($pengerjaan->id, $terpilih->butir);

                return $pengerjaan;
            });
        } catch (UniqueConstraintViolationException $exception) {
            // Dua request mulai yang bersamaan sama-sama lolos pemeriksaan di
            // atas. Index parsial quiz_pengerjaan_berjalan_unique menolak yang
            // kedua, dan siswa melanjutkan pengerjaan yang sudah dibuat.
            $berjalan = $this->pengerjaanRepository->berjalan($user, (int) $quiz->id);

            if (! $berjalan instanceof QuizPengerjaan) {
                throw $exception;
            }

            return $this->balasan($berjalan, baru: false);
        }

        return $this->balasan($pengerjaan, baru: true);
    }

    public function ringkasan(User $user, int $pengerjaanId): LatihanDimulai
    {
        return $this->balasan($this->pengerjaanMilik($user, $pengerjaanId), baru: false);
    }

    public function simpanJawaban(User $user, int $pengerjaanId, int $soalId, ?string $jawaban): void
    {
        $pengerjaan = $this->pengerjaanMilik($user, $pengerjaanId);

        if ($pengerjaan->disubmit_pada !== null) {
            throw new PengerjaanSudahDisubmitException('Jawaban sudah dikunci saat latihan disubmit.');
        }

        $baris = $this->jawabanRepository->findJawaban($pengerjaan->id, $soalId);

        if (! $baris instanceof QuizJawaban) {
            throw ValidationException::withMessages([
                'soal_id' => ['Soal ini bukan bagian dari latihan tersebut.'],
            ]);
        }

        $baris->forceFill(['jawaban_user' => $jawaban])->save();
    }

    public function submit(User $user, int $pengerjaanId): QuizPengerjaan
    {
        $pengerjaan = $this->pengerjaanMilik($user, $pengerjaanId);

        // Transaksi 1: kunci baris dan isi disubmit_pada. Jawaban terkunci
        // dari titik ini walau Python belum memberi nilai.
        $terkunci = DB::transaction(function () use ($pengerjaan): QuizPengerjaan {
            $baris = $this->pengerjaanRepository->findUntukUpdate($pengerjaan->id);

            if (! $baris instanceof QuizPengerjaan) {
                throw (new ModelNotFoundException)->setModel(QuizPengerjaan::class, [$pengerjaan->id]);
            }

            if ($baris->selesai_pada === null && $baris->disubmit_pada === null) {
                $baris->forceFill(['disubmit_pada' => now()])->save();
            }

            return $baris;
        });

        // Sudah selesai: kembalikan hasil yang ada, jangan nilai ulang.
        if ($terkunci->selesai_pada !== null) {
            return $terkunci;
        }

        try {
            $this->selesaikanPenilaian($terkunci->id);
        } catch (PerhitunganTidakTersediaException $exception) {
            // Sama seperti pre-test: yang mengirimi job adalah submit, job
            // memakai retry miliknya sendiri.
            NilaiUlangJob::dispatch(JenisPengerjaan::Latihan, $terkunci->id);

            throw new PerhitunganTidakTersediaException(
                "Penilaian latihan #{$terkunci->id} gagal dan dijadwalkan untuk dicoba lagi.",
            );
        }

        return $terkunci->fresh() ?? $terkunci;
    }

    /**
     * Susun payload, panggil Python di luar transaksi, simpan hasil dalam
     * transaksi kedua. Idempoten untuk NilaiUlangJob: pemeriksaan di awal
     * hanya menghemat panggilan Python, penjaga yang sebenarnya ada di
     * transaksi kedua, di bawah kunci baris.
     */
    public function selesaikanPenilaian(int $id): void
    {
        $pengerjaan = $this->pengerjaanRepository->find($id);

        if (! $pengerjaan instanceof QuizPengerjaan || $pengerjaan->selesai_pada !== null) {
            return;
        }

        $jawaban = $this->jawabanRepository->untukPengerjaan($id);

        $balasan = $this->perhitungan->hitungPenilaian(
            $this->payloadSoal($jawaban)
        );

        $statusBenar = collect($balasan['jawaban'])->keyBy('soal_id');

        DB::transaction(function () use ($id, $jawaban, $balasan, $statusBenar): void {
            // Job dan submit ulang bisa sampai di sini bersamaan. Yang kalah
            // menunggu kunci, lalu melihat selesai_pada sudah terisi.
            $pengerjaan = $this->pengerjaanRepository->findUntukUpdate($id);

            if (! $pengerjaan instanceof QuizPengerjaan || $pengerjaan->selesai_pada !== null) {
                return;
            }

            foreach ($jawaban as $baris) {
                $status = $statusBenar->get((int) $baris->soal_id);

                $baris->forceFill([
                    'status_benar' => (bool) ($status['status_benar'] ?? false),
                ])->save();
            }

            $pengerjaan->forceFill([
                'nilai' => $balasan['nilai'],
                'selesai_pada' => now(),
            ])->save();
        });
    }

    /**
     * @param  Collection<int, QuizJawaban>  $jawaban
     * @return list<array{soal_id: int, tipe_soal: string, bobot: int, jawaban_user: ?string, kunci_jawaban: string}>
     */
    private function payloadSoal(Collection $jawaban): array
    {
        $payload = [];

        foreach ($jawaban as $baris) {
            /** @var Soal $soal */
            $soal = $baris->soal;

            $payload[] = [
                'soal_id' => (int) $soal->id,
                'tipe_soal' => $soal->tipe_soal->value,
                'bobot' => (int) $baris->bobot,
                'jawaban_user' => $baris->jawaban_user,
                'kunci_jawaban' => (string) $soal->kunci_jawaban,
            ];
        }

        return $payload;
    }

    private function pengerjaanMilik(User $user, int $pengerjaanId): QuizPengerjaan
    {
        $pengerjaan = $this->pengerjaanRepository->findMilik($pengerjaanId, $user->id);

        // Milik orang lain dianggap tidak ada, bukan Forbidden.
        if (! $pengerjaan instanceof QuizPengerjaan) {
            throw (new ModelNotFoundException)->setModel(QuizPengerjaan::class, [$pengerjaanId]);
        }

        return $pengerjaan;
    }

    private function balasan(QuizPengerjaan $pengerjaan, bool $baru): LatihanDimulai
    {
        $jawaban = $this->jawabanRepository->untukPengerjaan($pengerjaan->id);

        $peta = [];

        foreach ($jawaban as $baris) {
            $peta[(int) $baris->soal_id] = $baris->soal;
        }

        $pengerjaan->setRelation('jawaban', $jawaban);
        $pengerjaan->setRelation('soal', new Collection(array_values($peta)));

        return new LatihanDimulai(
            pengerjaan: $pengerjaan,
            soal: new Collection(array_values($peta)),
            baru: $baru,
        );
    }
}
