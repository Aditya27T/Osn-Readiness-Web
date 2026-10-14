<?php

namespace App\Services;

use App\Contracts\Clients\PerhitunganClientInterface;
use App\Contracts\Repositories\MateriRepositoryInterface;
use App\Contracts\Repositories\PemetaanMateriRepositoryInterface;
use App\Contracts\Repositories\PretestJawabanRepositoryInterface;
use App\Contracts\Repositories\PretestRepositoryInterface;
use App\Contracts\Repositories\RekomendasiMateriRepositoryInterface;
use App\Contracts\Repositories\SoalRepositoryInterface;
use App\Contracts\Repositories\TingkatSeleksiRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Services\AturanServiceInterface;
use App\Contracts\Services\PretestServiceInterface;
use App\Contracts\Services\PutaranServiceInterface;
use App\Contracts\Services\SoalPickerServiceInterface;
use App\DTOs\HasilPretest;
use App\DTOs\PermintaanSoal;
use App\DTOs\PretestDimulai;
use App\Enums\JenisPengerjaan;
use App\Enums\Peruntukan;
use App\Exceptions\PengerjaanSudahDisubmitException;
use App\Exceptions\PerhitunganTidakTersediaException;
use App\Exceptions\PutaranMasihBerjalanException;
use App\Exceptions\SudahLulusException;
use App\Exceptions\TingkatTerkunciException;
use App\Jobs\NilaiUlangJob;
use App\Models\Pretest;
use App\Models\PretestJawaban;
use App\Models\Soal;
use App\Models\TingkatSeleksi;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PretestService implements PretestServiceInterface
{
    public function __construct(
        private readonly PretestRepositoryInterface $pretestRepository,
        private readonly PretestJawabanRepositoryInterface $jawabanRepository,
        private readonly PemetaanMateriRepositoryInterface $pemetaanRepository,
        private readonly RekomendasiMateriRepositoryInterface $rekomendasiRepository,
        private readonly MateriRepositoryInterface $materiRepository,
        private readonly SoalRepositoryInterface $soalRepository,
        private readonly TingkatSeleksiRepositoryInterface $tingkatRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly PutaranServiceInterface $putaranService,
        private readonly AturanServiceInterface $aturanService,
        private readonly SoalPickerServiceInterface $soalPicker,
        private readonly PerhitunganClientInterface $perhitungan,
    ) {}

    public function mulai(User $user, int $tingkatId): PretestDimulai
    {
        $tingkat = $this->tingkatRepository->find($tingkatId);

        if (! $tingkat instanceof TingkatSeleksi) {
            throw (new ModelNotFoundException)->setModel(TingkatSeleksi::class, [$tingkatId]);
        }

        $status = $this->putaranService->status($user, $tingkat);

        if (! $status->tingkatTerbuka) {
            throw new TingkatTerkunciException("Tingkat {$tingkat->nama_tingkat} belum terbuka.");
        }

        if ($status->sudahLulus) {
            throw new SudahLulusException("Tingkat {$tingkat->nama_tingkat} sudah lulus.");
        }

        // Pre-test yang sedang berjalan dikembalikan apa adanya supaya siswa
        // tidak kehilangan jawaban yang sudah ia isi.
        if ($status->pretestBerjalanId !== null) {
            return $this->balasan($this->pretestRepository->find($status->pretestBerjalanId), baru: false);
        }

        if (! $status->bolehPretestBaru) {
            throw new PutaranMasihBerjalanException('Putaran sebelumnya belum selesai.');
        }

        $aturan = $this->aturanService->untukTingkat($tingkatId);

        $terpilih = $this->soalPicker->pilih(new PermintaanSoal(
            tingkatId: $tingkatId,
            peruntukan: Peruntukan::Pretest,
            jumlahSoal: $aturan->pretestJumlahSoal,
            persenLevel: $aturan->persenLevelPretest,
            minimalSoalPerMateri: $aturan->pretestMinSoalPerMateri,
            // Semua soal dari pre-test siswa di tingkat ini, bukan hanya
            // putaran terakhir, supaya tidak pernah berulang.
            soalDikecualikan: $this->jawabanRepository->soalIdsTerpakai($user->id, $tingkatId),
        ));

        try {
            $pretest = DB::transaction(function () use ($user, $tingkatId, $terpilih): Pretest {
                /** @var Pretest $pretest */
                $pretest = $this->pretestRepository->create([
                    'user_id' => $user->id,
                    'tingkat_id' => $tingkatId,
                ]);

                $this->jawabanRepository->buatBanyak($pretest->id, $terpilih->butir);

                $this->userRepository->update($user, ['tingkat_aktif_id' => $tingkatId]);

                return $pretest;
            });
        } catch (UniqueConstraintViolationException $exception) {
            // Permintaan ganda bisa lolos dari pemeriksaan di atas karena
            // keduanya melihat keadaan yang sama. Index parsial
            // pretest_berjalan_unique yang menjadi penjaga terakhir.
            $berjalan = $this->pretestRepository->berjalan($user->id, $tingkatId);

            if (! $berjalan instanceof Pretest) {
                throw $exception;
            }

            return $this->balasan($berjalan, baru: false);
        }

        return $this->balasan($pretest, baru: true);
    }

    public function ringkasan(User $user, int $pretestId): PretestDimulai
    {
        return $this->balasan($this->pretestMilik($user, $pretestId), baru: false);
    }

    public function hasil(User $user, int $pretestId): ?HasilPretest
    {
        $pretest = $this->pretestMilik($user, $pretestId);

        if ($pretest->selesai_pada === null) {
            return null;
        }

        $pretest->load(['pemetaanMateri', 'rekomendasiMateri']);

        return new HasilPretest(
            pretest: $pretest,
            pemetaan: $pretest->pemetaanMateri,
            materiWajib: $pretest->rekomendasiMateri,
        );
    }

    public function simpanJawaban(User $user, int $pretestId, int $soalId, ?string $jawaban): void
    {
        $pretest = $this->pretestMilik($user, $pretestId);

        if ($pretest->disubmit_pada !== null) {
            throw new PengerjaanSudahDisubmitException('Jawaban sudah dikunci saat pre-test disubmit.');
        }

        $baris = $this->jawabanRepository->findJawaban($pretest->id, $soalId);

        if (! $baris instanceof PretestJawaban) {
            throw ValidationException::withMessages([
                'soal_id' => ['Soal ini bukan bagian dari pre-test tersebut.'],
            ]);
        }

        $baris->forceFill(['jawaban_user' => $jawaban])->save();
    }

    public function submit(User $user, int $pretestId): HasilPretest
    {
        $pretest = $this->pretestMilik($user, $pretestId);

        // Transaksi 1: kunci baris dan isi disubmit_pada. Jawaban terkunci dari
        // titik ini walau Python belum memberi nilai.
        $terkunci = DB::transaction(function () use ($pretest): Pretest {
            $baris = $this->pretestRepository->findUntukUpdate($pretest->id);

            if (! $baris instanceof Pretest) {
                throw (new ModelNotFoundException)->setModel(Pretest::class, [$pretest->id]);
            }

            if ($baris->selesai_pada === null && $baris->disubmit_pada === null) {
                $baris->forceFill(['disubmit_pada' => now()])->save();
            }

            return $baris;
        });

        // Sudah selesai: kembalikan hasil yang ada, jangan nilai ulang.
        if ($terkunci->selesai_pada !== null) {
            return $this->hasilDari($terkunci);
        }

        try {
            $this->selesaikanPenilaian($terkunci->id);
        } catch (PerhitunganTidakTersediaException $exception) {
            // Penilaian diserahkan ke job supaya penghapusan saat putaran habis
            // tetap terjadi walau nilainya terlambat. Job punya retry sendiri,
            // jadi method ini yang mengirimi job, bukan sebaliknya; kalau
            // dibalik, job akan memanggil dirinya lagi tanpa henti.
            NilaiUlangJob::dispatch(JenisPengerjaan::Pretest, $terkunci->id);

            throw new PerhitunganTidakTersediaException(
                "Penilaian pre-test #{$terkunci->id} gagal dan dijadwalkan untuk dicoba lagi.",
            );
        }

        return $this->hasilDari($terkunci->fresh());
    }

    /**
     * Langkah 2 sampai 5: susun payload, panggil Python di luar transaksi,
     * lalu simpan hasil dalam transaksi kedua.
     *
     * Dipanggil juga oleh NilaiUlangJob, jadi harus idempoten: bila
     * selesai_pada sudah terisi, ia berhenti tanpa menulis apa pun. Pemeriksaan
     * di awal hanya menghemat panggilan Python; penjaga yang sebenarnya ada di
     * transaksi kedua, di bawah kunci baris.
     */
    public function selesaikanPenilaian(int $id): void
    {
        $pretest = $this->pretestRepository->find($id);

        if (! $pretest instanceof Pretest || $pretest->selesai_pada !== null) {
            return;
        }

        $jawaban = $this->jawabanRepository->untukPretest($id);
        $materi = $this->materiRepository->untukTingkat($pretest->tingkat_id);
        $aturan = $this->aturanService->untukTingkat($pretest->tingkat_id);

        try {
            $balasan = $this->perhitungan->hitungPretest(
                $this->payloadSoal($jawaban),
                $materi->map(fn ($satu): array => [
                    'materi_id' => (int) $satu->id,
                    'urutan' => (int) $satu->urutan,
                ])->values()->all(),
                $aturan->jumlahMateriWajib,
            );
        } catch (PerhitunganTidakTersediaException $exception) {
            // Tidak ada dispatch di sini. Yang mengirimi job adalah submit(),
            // sedangkan pemanggilan dari NilaiUlangJob-andalkan retry milik job
            // itu sendiri.
            throw $exception;
        }

        $statusBenar = collect($balasan['jawaban'])->keyBy('soal_id');

        DB::transaction(function () use ($id, $jawaban, $balasan, $statusBenar): void {
            // Job dan submit ulang bisa sampai di sini bersamaan. Yang kalah
            // menunggu kunci, lalu melihat selesai_pada sudah terisi.
            $pretest = $this->pretestRepository->findUntukUpdate($id);

            if (! $pretest instanceof Pretest || $pretest->selesai_pada !== null) {
                return;
            }

            foreach ($jawaban as $baris) {
                $status = $statusBenar->get((int) $baris->soal_id);

                $baris->forceFill([
                    'status_benar' => (bool) ($status['status_benar'] ?? false),
                ])->save();
            }

            $pretest->forceFill([
                'nilai' => $balasan['nilai'],
                'selesai_pada' => now(),
            ])->save();

            $this->pemetaanRepository->buatBanyak($pretest->id, $pretest->user_id, $balasan['pemetaan']);
            $this->rekomendasiRepository->buatBanyak($pretest->id, $pretest->user_id, $balasan['materi_wajib']);
        });
    }

    /**
     * @param  Collection<int, PretestJawaban>  $jawaban
     * @return list<array{soal_id: int, materi_id: int, tipe_soal: string, bobot: int, jawaban_user: ?string, kunci_jawaban: string}>
     */
    private function payloadSoal(Collection $jawaban): array
    {
        $payload = [];

        foreach ($jawaban as $baris) {
            /** @var Soal $soal */
            $soal = $baris->soal;

            $payload[] = [
                'soal_id' => (int) $soal->id,
                'materi_id' => (int) $soal->materi_id,
                'tipe_soal' => $soal->tipe_soal->value,
                'bobot' => (int) $baris->bobot,
                'jawaban_user' => $baris->jawaban_user,
                'kunci_jawaban' => (string) $soal->kunci_jawaban,
            ];
        }

        return $payload;
    }

    private function pretestMilik(User $user, int $pretestId): Pretest
    {
        $pretest = $this->pretestRepository->findMilik($pretestId, $user->id);

        // Milik orang lain dianggap tidak ada, bukan Forbidden.
        if (! $pretest instanceof Pretest) {
            throw (new ModelNotFoundException)->setModel(Pretest::class, [$pretestId]);
        }

        return $pretest;
    }

    private function balasan(Pretest $pretest, bool $baru): PretestDimulai
    {
        $jawaban = $this->jawabanRepository->untukPretest($pretest->id);

        // Urutan tampil mengikuti kolom urutan pada baris jawaban, bukan id soal.
        $peta = [];

        foreach ($jawaban as $baris) {
            $peta[(int) $baris->soal_id] = $baris->soal;
        }

        $pretest->setRelation('jawaban', $jawaban);
        $pretest->setRelation('soal', new Collection(array_values($peta)));

        return new PretestDimulai(
            pretest: $pretest,
            soal: new Collection(array_values($peta)),
            baru: $baru,
        );
    }

    private function hasilDari(Pretest $pretest): HasilPretest
    {
        $pretest->load(['pemetaanMateri', 'rekomendasiMateri']);

        return new HasilPretest(
            pretest: $pretest,
            pemetaan: $pretest->pemetaanMateri,
            materiWajib: $pretest->rekomendasiMateri,
        );
    }
}
