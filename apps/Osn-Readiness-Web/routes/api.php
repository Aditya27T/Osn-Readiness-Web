<?php

use App\Http\Controllers\Api\Admin\AturanPemetaanController;
use App\Http\Controllers\Api\Admin\BankSoalController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\KompetensiController;
use App\Http\Controllers\Api\Admin\KonteksSoalController;
use App\Http\Controllers\Api\Admin\LatihanController;
use App\Http\Controllers\Api\Admin\MateriController;
use App\Http\Controllers\Api\Admin\PembahasanController;
use App\Http\Controllers\Api\Admin\SimulasiController;
use App\Http\Controllers\Api\Admin\SiswaController;
use App\Http\Controllers\Api\Admin\SoalController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BelajarController;
use App\Http\Controllers\Api\HasilSimulasiController;
use App\Http\Controllers\Api\PretestController;
use App\Http\Controllers\Api\QuizController;
use App\Http\Controllers\Api\QuizPengerjaanController;
use App\Http\Controllers\Api\RiwayatController;
use App\Http\Controllers\Api\SyaratSimulasiController;
use App\Http\Controllers\Api\TingkatController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute publik
|--------------------------------------------------------------------------
| Registrasi dan login tidak butuh token. Login dibatasi lima kali per menit
| per email + IP lewat named limiter "login" (lihat AppServiceProvider).
*/

Route::prefix('auth')->group(function (): void {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
});

/*
|--------------------------------------------------------------------------
| Rute siswa
|--------------------------------------------------------------------------
| Semua rute di sini berada di balik auth:sanctum + active, sehingga akun yang
| dinonaktifkan di tengah sesi langsung tertolak.
|
| Blok per paket; hanya isi blok milik paket yang sedang dikerjakan supaya
| konflik merge antar orang tetap kecil.
*/

Route::middleware(['auth:sanctum', 'active'])->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::match(['put', 'patch'], 'profile', [AuthController::class, 'updateProfile']);
    });

    // [B1-A] Akses tingkat dan status putaran.
    Route::get('tingkat', [TingkatController::class, 'index']);

    // [B2-A] Pre-test dan pemetaan.
    Route::post('pretest', [PretestController::class, 'store']);
    Route::get('pretest/{pretest}', [PretestController::class, 'show']);
    Route::put('pretest/{pretest}/jawaban', [PretestController::class, 'simpanJawaban']);
    Route::post('pretest/{pretest}/submit', [PretestController::class, 'submit']);

    // [B2-B] Materi, progress, latihan, dan syarat simulasi.
    Route::get('materi', [BelajarController::class, 'index']);
    Route::get('materi/{materi}', [BelajarController::class, 'show']);
    Route::put('materi/{materi}/progress', [BelajarController::class, 'perbaruiProgress']);
    Route::post('quiz/{quiz}/mulai', [QuizController::class, 'mulai']);
    Route::get('quiz-pengerjaan/{pengerjaan}', [QuizPengerjaanController::class, 'show']);
    Route::put('quiz-pengerjaan/{pengerjaan}/jawaban', [QuizPengerjaanController::class, 'simpanJawaban']);
    Route::post('quiz-pengerjaan/{pengerjaan}/submit', [QuizPengerjaanController::class, 'submit']);
    Route::get('simulasi/syarat', [SyaratSimulasiController::class, 'show']);

    // [B3-A] Simulasi, kelulusan, dan review.
    Route::get('simulasi', [App\Http\Controllers\Api\SimulasiController::class, 'index']);
    Route::post('simulasi/{simulasi}/mulai', [App\Http\Controllers\Api\SimulasiController::class, 'mulai']);
    Route::get('hasil-simulasi/{hasil}', [HasilSimulasiController::class, 'show']);
    Route::put('hasil-simulasi/{hasil}/jawaban', [HasilSimulasiController::class, 'simpanJawaban']);
    Route::post('hasil-simulasi/{hasil}/submit', [HasilSimulasiController::class, 'submit']);
    Route::get('hasil-simulasi/{hasil}/review', [HasilSimulasiController::class, 'review']);

    // [B3-B] Dashboard dan riwayat.
    Route::get('dashboard', [App\Http\Controllers\Api\DashboardController::class, 'show']);
    Route::get('riwayat', [RiwayatController::class, 'index']);

    // [B1-D + B1-E] Admin. Satu grup, satu lapisan: middleware role
    // Super Admin, lalu policy per endpoint di controller masing-masing.
    Route::prefix('admin')->middleware('role:Super Admin')->group(function (): void {
        // [B1-D] Struktur konten dan siswa
        // FQCN dipakai karena TingkatController tanpa awalan sudah dipakai
        // endpoint siswa GET /api/tingkat.
        Route::apiResource('tingkat', App\Http\Controllers\Api\Admin\TingkatController::class)->only(['index', 'show', 'update']);
        Route::apiResource('kompetensi', KompetensiController::class);
        // Didaftarkan sebelum apiResource supaya "gambar" tidak dibaca
        // sebagai {materi}.
        Route::post('materi/gambar', [MateriController::class, 'uploadGambar']);
        Route::apiResource('materi', MateriController::class);
        Route::apiResource('konteks-soal', KonteksSoalController::class);
        Route::apiResource('siswa', SiswaController::class)->only(['index', 'show', 'update', 'destroy']);
        Route::post('siswa/{siswa}/deactivate', [SiswaController::class, 'deactivate']);

        // [B1-E] Soal, pembahasan, latihan, simulasi, aturan pemetaan
        Route::apiResource('soal', SoalController::class);
        Route::post('soal/{soal}/pembahasan', [PembahasanController::class, 'store']);
        Route::put('soal/{soal}/pembahasan', [PembahasanController::class, 'update']);
        Route::get('soal/{soal}/pembahasan', [PembahasanController::class, 'show']);
        Route::delete('soal/{soal}/pembahasan', [PembahasanController::class, 'destroy']);
        Route::apiResource('latihan', LatihanController::class);
        Route::apiResource('simulasi', SimulasiController::class);
        Route::get('tingkat/{tingkat}/aturan-pemetaan', [AturanPemetaanController::class, 'show']);
        Route::put('tingkat/{tingkat}/aturan-pemetaan', [AturanPemetaanController::class, 'update']);

        // [B2-C] Kecukupan bank soal dan dashboard admin
        Route::get('bank-soal/kecukupan', [BankSoalController::class, 'kecukupan']);
        Route::get('dashboard', [DashboardController::class, 'ringkasan']);
    });
});
