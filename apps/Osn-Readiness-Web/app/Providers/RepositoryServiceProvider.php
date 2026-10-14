<?php

namespace App\Providers;

use App\Clients\PerhitunganClient;
use App\Contracts\Clients\PerhitunganClientInterface;
use App\Contracts\Randomizers\RandomizerInterface;
use App\Contracts\Repositories\AturanPemetaanRepositoryInterface;
use App\Contracts\Repositories\HasilSimulasiJawabanRepositoryInterface;
use App\Contracts\Repositories\HasilSimulasiRepositoryInterface;
use App\Contracts\Repositories\KenaikanTingkatRepositoryInterface;
use App\Contracts\Repositories\MateriRepositoryInterface;
use App\Contracts\Repositories\PemetaanMateriRepositoryInterface;
use App\Contracts\Repositories\PretestJawabanRepositoryInterface;
use App\Contracts\Repositories\PretestRepositoryInterface;
use App\Contracts\Repositories\ProgressBelajarRepositoryInterface;
use App\Contracts\Repositories\QuizJawabanRepositoryInterface;
use App\Contracts\Repositories\QuizPengerjaanRepositoryInterface;
use App\Contracts\Repositories\QuizRepositoryInterface;
use App\Contracts\Repositories\RekomendasiMateriRepositoryInterface;
use App\Contracts\Repositories\RiwayatHasilRepositoryInterface;
use App\Contracts\Repositories\SimulasiRepositoryInterface;
use App\Contracts\Repositories\SoalRepositoryInterface;
use App\Contracts\Repositories\TingkatSeleksiRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Services\AturanServiceInterface;
use App\Contracts\Services\AuthServiceInterface;
use App\Contracts\Services\BankSoalServiceInterface;
use App\Contracts\Services\BelajarServiceInterface;
use App\Contracts\Services\DashboardAdminServiceInterface;
use App\Contracts\Services\DashboardServiceInterface;
use App\Contracts\Services\ImporKontenServiceInterface;
use App\Contracts\Services\KelulusanServiceInterface;
use App\Contracts\Services\KompetensiServiceInterface;
use App\Contracts\Services\KonteksSoalServiceInterface;
use App\Contracts\Services\LatihanAdminServiceInterface;
use App\Contracts\Services\LatihanServiceInterface;
use App\Contracts\Services\MateriServiceInterface;
use App\Contracts\Services\PretestServiceInterface;
use App\Contracts\Services\PutaranServiceInterface;
use App\Contracts\Services\RiwayatServiceInterface;
use App\Contracts\Services\SimulasiAdminServiceInterface;
use App\Contracts\Services\SimulasiServiceInterface;
use App\Contracts\Services\SiswaServiceInterface;
use App\Contracts\Services\SoalAdminServiceInterface;
use App\Contracts\Services\SoalPickerServiceInterface;
use App\Contracts\Services\SyaratSimulasiServiceInterface;
use App\Contracts\Services\TingkatServiceAdminInterface;
use App\Contracts\Services\TingkatServiceInterface;
use App\Contracts\Services\UserServiceInterface;
use App\Randomizers\AcakRandomizer;
use App\Repositories\Eloquent\AturanPemetaanRepository;
use App\Repositories\Eloquent\HasilSimulasiJawabanRepository;
use App\Repositories\Eloquent\HasilSimulasiRepository;
use App\Repositories\Eloquent\KenaikanTingkatRepository;
use App\Repositories\Eloquent\MateriRepository;
use App\Repositories\Eloquent\PemetaanMateriRepository;
use App\Repositories\Eloquent\PretestJawabanRepository;
use App\Repositories\Eloquent\PretestRepository;
use App\Repositories\Eloquent\ProgressBelajarRepository;
use App\Repositories\Eloquent\QuizJawabanRepository;
use App\Repositories\Eloquent\QuizPengerjaanRepository;
use App\Repositories\Eloquent\QuizRepository;
use App\Repositories\Eloquent\RekomendasiMateriRepository;
use App\Repositories\Eloquent\RiwayatHasilRepository;
use App\Repositories\Eloquent\SimulasiRepository;
use App\Repositories\Eloquent\SoalRepository;
use App\Repositories\Eloquent\TingkatSeleksiRepository;
use App\Repositories\Eloquent\UserRepository;
use App\Services\Admin\KompetensiService;
use App\Services\Admin\KonteksSoalService;
use App\Services\Admin\LatihanAdminService;
use App\Services\Admin\MateriService;
use App\Services\Admin\SimulasiAdminService;
use App\Services\Admin\SiswaService;
use App\Services\Admin\SoalAdminService;
use App\Services\Admin\TingkatService as AdminTingkatService;
use App\Services\AturanService;
use App\Services\AuthService;
use App\Services\BankSoalService;
use App\Services\BelajarService;
use App\Services\DashboardAdminService;
use App\Services\DashboardService;
use App\Services\ImporKontenService;
use App\Services\KelulusanService;
use App\Services\LatihanService;
use App\Services\PretestService;
use App\Services\PutaranService;
use App\Services\RiwayatService;
use App\Services\SimulasiService;
use App\Services\SoalPickerService;
use App\Services\SyaratSimulasiService;
use App\Services\TingkatService;
use App\Services\UserService;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(TingkatSeleksiRepositoryInterface::class, TingkatSeleksiRepository::class);
        $this->app->bind(AturanPemetaanRepositoryInterface::class, AturanPemetaanRepository::class);
        $this->app->bind(PretestRepositoryInterface::class, PretestRepository::class);
        $this->app->bind(HasilSimulasiRepositoryInterface::class, HasilSimulasiRepository::class);
        $this->app->bind(KenaikanTingkatRepositoryInterface::class, KenaikanTingkatRepository::class);
        $this->app->bind(SoalRepositoryInterface::class, SoalRepository::class);
        $this->app->bind(PretestJawabanRepositoryInterface::class, PretestJawabanRepository::class);
        $this->app->bind(PemetaanMateriRepositoryInterface::class, PemetaanMateriRepository::class);
        $this->app->bind(RekomendasiMateriRepositoryInterface::class, RekomendasiMateriRepository::class);
        $this->app->bind(MateriRepositoryInterface::class, MateriRepository::class);

        // Service bindings
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(AuthServiceInterface::class, AuthService::class);
        $this->app->bind(TingkatServiceInterface::class, TingkatService::class);

        // [B1-D] Admin services
        $this->app->bind(TingkatServiceAdminInterface::class, AdminTingkatService::class);
        $this->app->bind(KompetensiServiceInterface::class, KompetensiService::class);
        $this->app->bind(MateriServiceInterface::class, MateriService::class);
        $this->app->bind(KonteksSoalServiceInterface::class, KonteksSoalService::class);
        $this->app->bind(SiswaServiceInterface::class, SiswaService::class);

        // Aturan dibaca berkali-kali dalam satu request; scoped menjaga cache per request.
        $this->app->scoped(AturanServiceInterface::class, AturanService::class);
        $this->app->bind(PutaranServiceInterface::class, PutaranService::class);
        $this->app->bind(SyaratSimulasiServiceInterface::class, SyaratSimulasiService::class);
        $this->app->bind(PretestServiceInterface::class, PretestService::class);

        // [B2-B] Latihan yang sesungguhnya menggantikan stub B1-C/B2-A.
        $this->app->bind(BankSoalServiceInterface::class, BankSoalService::class);
        $this->app->bind(ImporKontenServiceInterface::class, ImporKontenService::class);
        $this->app->bind(DashboardAdminServiceInterface::class, DashboardAdminService::class);
        $this->app->bind(SoalAdminServiceInterface::class, SoalAdminService::class);
        $this->app->bind(RiwayatServiceInterface::class, RiwayatService::class);
        $this->app->bind(QuizRepositoryInterface::class, QuizRepository::class);
        $this->app->bind(LatihanAdminServiceInterface::class, LatihanAdminService::class);
        $this->app->bind(SimulasiAdminServiceInterface::class, SimulasiAdminService::class);
        $this->app->bind(BelajarServiceInterface::class, BelajarService::class);
        $this->app->bind(ProgressBelajarRepositoryInterface::class, ProgressBelajarRepository::class);
        $this->app->bind(QuizPengerjaanRepositoryInterface::class, QuizPengerjaanRepository::class);
        $this->app->bind(QuizJawabanRepositoryInterface::class, QuizJawabanRepository::class);
        $this->app->bind(LatihanServiceInterface::class, LatihanService::class);
        // [B3-A] Simulasi yang sesungguhnya menggantikan stub terakhir.
        $this->app->bind(SimulasiServiceInterface::class, SimulasiService::class);
        $this->app->bind(KelulusanServiceInterface::class, KelulusanService::class);
        $this->app->bind(SimulasiRepositoryInterface::class, SimulasiRepository::class);
        $this->app->bind(HasilSimulasiJawabanRepositoryInterface::class, HasilSimulasiJawabanRepository::class);
        $this->app->bind(RiwayatHasilRepositoryInterface::class, RiwayatHasilRepository::class);
        $this->app->bind(DashboardServiceInterface::class, DashboardService::class);
        $this->app->bind(SoalPickerServiceInterface::class, SoalPickerService::class);

        // [B1-C] Klien layanan hitung Python. Dibaca dari config saat
        // instantiate supaya test bisa menyuntikkan stub.
        $this->app->bind(PerhitunganClientInterface::class, fn (): PerhitunganClientInterface => new PerhitunganClient(
            url: rtrim((string) config('services.perhitungan.url'), '/'),
            token: (string) config('services.perhitungan.token'),
            timeout: (int) config('services.perhitungan.timeout'),
            retry: (int) config('services.perhitungan.retry'),
        ));

        // Produksi memakai sumber acak PHP; test dapat menyuntikkan SeededRandomizer.
        $this->app->bind(RandomizerInterface::class, AcakRandomizer::class);
    }
}
