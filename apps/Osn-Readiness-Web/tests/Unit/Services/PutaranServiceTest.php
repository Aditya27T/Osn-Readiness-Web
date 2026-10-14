<?php

namespace Tests\Unit\Services;

use App\Contracts\Repositories\HasilSimulasiRepositoryInterface;
use App\Contracts\Repositories\KenaikanTingkatRepositoryInterface;
use App\Contracts\Repositories\PretestRepositoryInterface;
use App\Contracts\Repositories\TingkatSeleksiRepositoryInterface;
use App\Contracts\Services\AturanServiceInterface;
use App\Contracts\Services\SyaratSimulasiServiceInterface;
use App\DTOs\AturanTingkat;
use App\DTOs\SyaratSimulasi;
use App\Enums\TahapSiswa;
use App\Models\HasilSimulasi;
use App\Models\Pretest;
use App\Models\TingkatSeleksi;
use App\Models\User;
use App\Services\PutaranService;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Tests\TestCase;

class PutaranServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_tingkat_pertama_selalu_terbuka(): void
    {
        $kabupaten = $this->tingkat(1);
        $provinsi = $this->tingkat(2);

        $status = $this->layanan($kabupaten, [$kabupaten, $provinsi])
            ->status($this->siswa(), $kabupaten);

        $this->assertTrue($status->tingkatTerbuka);
        $this->assertSame(TahapSiswa::BelumPretest, $status->tahap);
        $this->assertTrue($status->bolehPretestBaru);
    }

    public function test_tingkat_berikutnya_terkunci_sampai_kenaikan_lulus(): void
    {
        $kabupaten = $this->tingkat(1);
        $provinsi = $this->tingkat(2);

        $status = $this->layanan($provinsi, [$kabupaten, $provinsi])
            ->status($this->siswa(), $provinsi);

        $this->assertFalse($status->tingkatTerbuka);
        $this->assertSame(TahapSiswa::BelumPretest, $status->tahap);
        $this->assertFalse($status->bolehPretestBaru);
    }

    public function test_tahap_pretest_berjalan(): void
    {
        $tingkat = $this->tingkat(1);
        $user = $this->siswa();
        $pretest = $this->pretest(11);

        $status = $this->layanan(
            $tingkat,
            [$tingkat],
            user: $user,
            pretestBerjalan: $pretest,
        )->status($user, $tingkat);

        $this->assertSame(TahapSiswa::PretestBerjalan, $status->tahap);
        $this->assertSame(11, $status->pretestBerjalanId);
        $this->assertTrue($status->pretestBerjalan());
        $this->assertFalse($status->bolehPretestBaru);
    }

    public function test_tahap_belajar_saat_putaran_aktif_dan_syarat_belum_terpenuhi(): void
    {
        $tingkat = $this->tingkat(1);
        $user = $this->siswa();
        $putaran = $this->pretest(21);

        $status = $this->layanan(
            $tingkat,
            [$tingkat],
            user: $user,
            putaranAktif: $putaran,
            syaratTerpenuhi: false,
        )->status($user, $tingkat);

        $this->assertSame(21, $status->putaranAktifId);
        $this->assertSame(0, $status->percobaanTerpakai);
        $this->assertSame(TahapSiswa::Belajar, $status->tahap);
        $this->assertFalse($status->bolehPretestBaru);
    }

    public function test_tahap_siap_simulasi_saat_syarat_terpenuhi_dan_kuota_tersisa(): void
    {
        $tingkat = $this->tingkat(1);
        $user = $this->siswa();
        $putaran = $this->pretest(22);

        $status = $this->layanan(
            $tingkat,
            [$tingkat],
            user: $user,
            putaranAktif: $putaran,
            percobaanTerpakai: 1,
            syaratTerpenuhi: true,
        )->status($user, $tingkat);

        $this->assertSame(1, $status->percobaanTerpakai);
        $this->assertSame(TahapSiswa::SiapSimulasi, $status->tahap);
        $this->assertFalse($status->putaranHabis);
    }

    public function test_tahap_simulasi_berjalan(): void
    {
        $tingkat = $this->tingkat(1);
        $user = $this->siswa();
        $putaran = $this->pretest(23);
        $simulasi = $this->simulasi(31);

        $status = $this->layanan(
            $tingkat,
            [$tingkat],
            user: $user,
            putaranAktif: $putaran,
            percobaanTerpakai: 1,
            simulasiBerjalan: $simulasi,
        )->status($user, $tingkat);

        $this->assertSame(31, $status->simulasiBerjalanId);
        $this->assertTrue($status->simulasiBerjalan());
        $this->assertSame(TahapSiswa::SimulasiBerjalan, $status->tahap);
    }

    public function test_tahap_putaran_habis_membuka_pretest_baru(): void
    {
        $tingkat = $this->tingkat(1);
        $user = $this->siswa();
        $putaran = $this->pretest(24);

        $status = $this->layanan(
            $tingkat,
            [$tingkat],
            user: $user,
            putaranAktif: $putaran,
            percobaanTerpakai: 3,
        )->status($user, $tingkat);

        $this->assertTrue($status->putaranHabis);
        $this->assertSame(TahapSiswa::PutaranHabis, $status->tahap);
        $this->assertTrue($status->bolehPretestBaru);
    }

    public function test_tahap_lulus_menang_dari_semua_kondisi_lain(): void
    {
        $tingkat = $this->tingkat(1);
        $user = $this->siswa();
        $putaran = $this->pretest(25);
        $simulasi = $this->simulasi(32);

        $status = $this->layanan(
            $tingkat,
            [$tingkat],
            user: $user,
            putaranAktif: $putaran,
            pretestBerjalan: $this->pretest(26),
            simulasiBerjalan: $simulasi,
            percobaanTerpakai: 3,
            sudahLulus: true,
        )->status($user, $tingkat);

        $this->assertSame(TahapSiswa::Lulus, $status->tahap);
        $this->assertFalse($status->bolehPretestBaru);
        $this->assertFalse($status->putaranHabis);
    }

    public function test_tahap_belajar_bukan_siap_simulasi_kalau_kuota_sudah_pakai(): void
    {
        $tingkat = $this->tingkat(1);
        $user = $this->siswa();

        $status = $this->layanan(
            $tingkat,
            [$tingkat],
            user: $user,
            putaranAktif: $this->pretest(27),
            percobaanTerpakai: 3,
            syaratTerpenuhi: true,
            maksPercobaan: 2,
        )->status($user, $tingkat);

        $this->assertTrue($status->putaranHabis);
        $this->assertSame(TahapSiswa::PutaranHabis, $status->tahap);
    }

    public function test_simulasi_berjalan_membuat_boleh_pretest_baru_false(): void
    {
        $tingkat = $this->tingkat(1);
        $user = $this->siswa();
        $simulasi = $this->simulasi(33);

        $status = $this->layanan(
            $tingkat,
            [$tingkat],
            user: $user,
            putaranAktif: $this->pretest(28),
            percobaanTerpakai: 3,
            simulasiBerjalan: $simulasi,
        )->status($user, $tingkat);

        // Percobaan sudah maksimal tapi masih ada simulasi berjalan: putaran
        // belum dinyatakan habis dan pre-test baru belum boleh.
        $this->assertFalse($status->putaranHabis);
        $this->assertFalse($status->bolehPretestBaru);
        $this->assertSame(TahapSiswa::SimulasiBerjalan, $status->tahap);
    }

    /**
     * @param  array<int, TingkatSeleksi>  $semuaTingkat
     */
    private function layanan(
        TingkatSeleksi $tingkat,
        array $semuaTingkat,
        ?User $user = null,
        ?Pretest $pretestBerjalan = null,
        ?Pretest $putaranAktif = null,
        ?HasilSimulasi $simulasiBerjalan = null,
        int $percobaanTerpakai = 0,
        bool $syaratTerpenuhi = false,
        int $maksPercobaan = 3,
        bool $sudahLulus = false,
    ): PutaranService {
        $user ??= $this->siswa();

        $tingkatRepository = Mockery::mock(TingkatSeleksiRepositoryInterface::class);
        $tingkatRepository->shouldReceive('semuaTerurut')->andReturn(
            Collection::make($semuaTingkat)
        );

        $pretestRepository = Mockery::mock(PretestRepositoryInterface::class);
        $pretestRepository->shouldReceive('berjalan')->andReturn($pretestBerjalan);
        $pretestRepository->shouldReceive('putaranAktifTerbaru')->andReturn($putaranAktif);

        $hasilRepository = Mockery::mock(HasilSimulasiRepositoryInterface::class);
        $hasilRepository->shouldReceive('jumlahPercobaan')->andReturn($percobaanTerpakai);
        $hasilRepository->shouldReceive('berjalan')->andReturn($simulasiBerjalan);

        $kenaikanRepository = Mockery::mock(KenaikanTingkatRepositoryInterface::class);
        $kenaikanRepository->shouldReceive('adaLulus')->andReturn($sudahLulus);

        $aturanService = Mockery::mock(AturanServiceInterface::class);
        $aturanService->shouldReceive('untukTingkat')->andReturn(new AturanTingkat(
            tingkatId: $tingkat->id,
            bobotMudah: 1,
            bobotSedang: 2,
            bobotSulit: 3,
            pretestJumlahSoal: 30,
            persenLevelPretest: ['mudah' => 50.0, 'sedang' => 30.0, 'sulit' => 20.0],
            pretestMinSoalPerMateri: 2,
            jumlahMateriWajib: 3,
            latihanMinSoal: 10,
            latihanMinNilai: 50.0,
            persenLevelSimulasi: ['mudah' => 30.0, 'sedang' => 40.0, 'sulit' => 30.0],
            simulasiMaksPercobaan: $maksPercobaan,
            passingGrade: 70.0,
        ));

        $syaratService = Mockery::mock(SyaratSimulasiServiceInterface::class);
        $syaratService->shouldReceive('periksa')->andReturn(new SyaratSimulasi($syaratTerpenuhi));

        return new PutaranService(
            $tingkatRepository,
            $pretestRepository,
            $hasilRepository,
            $kenaikanRepository,
            $aturanService,
            $syaratService,
        );
    }

    private function tingkat(int $urutan): TingkatSeleksi
    {
        $tingkat = new TingkatSeleksi(['nama_tingkat' => "Tingkat {$urutan}", 'urutan' => $urutan]);
        $tingkat->id = $urutan * 10;

        return $tingkat;
    }

    private function pretest(int $id): Pretest
    {
        $pretest = new Pretest;
        $pretest->id = $id;

        return $pretest;
    }

    private function simulasi(int $id): HasilSimulasi
    {
        $simulasi = new HasilSimulasi;
        $simulasi->id = $id;

        return $simulasi;
    }

    private function siswa(): User
    {
        $user = new User;
        $user->id = 5;

        return $user;
    }
}
