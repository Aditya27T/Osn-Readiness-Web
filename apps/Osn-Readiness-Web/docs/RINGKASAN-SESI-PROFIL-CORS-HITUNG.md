# Ringkasan Sesi — Profil, CORS, dan Layanan Hitung Python

Tanggal: 5 Oktober 2026. Tiga pekerjaan: endpoint ubah profil (P0-2), CORS
untuk frontend Vite, dan revisi endpoint Python agar sesuai kontrak Laravel.

---

## 1. Endpoint ubah profil — `PUT /api/auth/profile`

Kontrak (sesuai permintaan FE, FE tidak diubah): `PUT {name, email}` →
`{message, data: user}` dengan bentuk `UserResource` + roles.

### File yang berubah (repo Laravel, sudah push `eb75f90` ke `origin/dev`)

| File | Perubahan |
|---|---|
| `app/Http/Requests/Auth/UpdateProfileRequest.php` (baru) | Validasi: `name` required; `email` required + unik case-insensitive kecuali milik sendiri (cek via `UserRepository::findByEmail`, sama seperti register); email di-lowercase di `prepareForValidation` |
| `app/Contracts/Services/AuthServiceInterface.php` | Tambah `updateProfile(User, array): User` |
| `app/Services/AuthService.php` | Implementasi via `UserRepository::update` dalam `DB::transaction`; race email kembar dipetakan ke 422 seperti register |
| `app/Http/Controllers/Api/AuthController.php` | Tambah `updateProfile()` tipis: validated → service → resource, pesan `Profil berhasil diperbarui` |
| `routes/api.php` | `Route::match(['put','patch'], 'profile', ...)` di grup `auth:sanctum` + `active` (akun nonaktif langsung 403) |
| `tests/Feature/Api/AuthTest.php` | 6 test baru (sukses + lowercase, 401, 422, tolak email milik akun lain, email sendiri boleh, PATCH) |
| `docs/API-ENDPOINTS.md`, `postman/*.json` | Baris/item `PUT /api/auth/profile` |

### Alur request

```
PUT /api/auth/profile {name, email}
  → auth:sanctum + active (401 tanpa token, 403 bila nonaktif)
  → UpdateProfileRequest (lowercase email, 422 bila tidak valid/kembar)
  → AuthController::updateProfile
  → AuthService::updateProfile → UserRepository::update
  → 200 {message, data: UserResource + roles}
```

Catatan: patch asing `/tmp/asing-updateProfile.patch` dari tooling luar tidak
dipakai — ia menulis `$user->save()` langsung di Service (melanggar
Service-Repository pattern) dan tanpa lowercasing/penanganan race email.

---

## 2. CORS untuk `http://localhost:5173`

Masalah awal: middleware global `HandleCors` aktif, tetapi `config/cors.php`
belum ada sehingga tidak ada origin yang diizinkan. Masalah kedua: FE request
dengan `withCredentials: true`, sehingga browser mewajibkan
`Access-Control-Allow-Credentials: true`.

### File yang berubah

| File | Perubahan |
|---|---|
| `config/cors.php` (baru) | `paths: api/*`; origin default `http://localhost:5173` + `http://127.0.0.1:5173`, bisa ditambah lewat env `FRONTEND_URL` (koma-separated); `supports_credentials: true` (origin wajib eksplisit, tidak boleh `*`) |
| `.env.example` | Tambah `FRONTEND_URL=http://localhost:5173` |

Terverifikasi live: preflight `OPTIONS` → 204 + `Allow-Origin` +
`Allow-Credentials: true`; origin asing tidak mendapat header ACAO.

---

## 3. Revisi endpoint Python — `/Volumes/PS50U/bigdata/data-analytics`

Masalah awal: submit pre-test/latihan/simulasi 503 `HASIL_SEDANG_DIPROSES`
karena tidak ada layanan hitung di `PERHITUNGAN_URL` (port 8001 kosong).
Server palsu PHP sempat dinyalakan untuk memastikan, lalu **dimatikan lagi**
atas permintaan — kondisi 503 dipulihkan sebelum revisi Python dikerjakan.

### Temuan: kontrak tidak cocok

Sumber truth Laravel: `PerhitunganClientInterface` + test kontrak
`tests/Feature/Clients/PerhitunganClientTest.php`. Realita Python saat itu:

- Req `POST /hitung/penilaian`: Laravel kirim `soal_id:int, tipe_soal,
  bobot, jawaban_user, kunci_jawaban`; Python wajibkan `soal_id:str, level,
  tipe, kunci, jawaban` → FastAPI 422 → Laravel petakan ke 502.
- Resp memakai `skor`/`hasil_soal`/`benar`, Laravel harapkan
  `nilai`/`jawaban`/`status_benar`.
- `/hitung/pretest`: Python tak kenal `materi`/`jumlah_materi_wajib` dan tak
  membalas `materi_wajib`; Laravel memvalidasi keduanya secara ketat.
- Docstring Python mengasumsikan "Laravel mengirim level" — faktanya tidak;
  bobot dikirim eksplisit per soal.
- `X-Internal-Token` dikirim Laravel tetapi tidak diperiksa Python.
- `/hitung/simulasi` & `/hitung/latihan` tidak pernah dipanggil Laravel
  (lulus dihitung `KelulusanService` di PHP) — dibiarkan apa adanya.

### Revisi (working tree data-analytics, BELUM commit)

| File | Perubahan |
|---|---|
| `src/data_analytics/schemas.py` | Model kontrak Laravel (`Laravel*`): id int, `bobot` eksplisit, `pemetaan` 7 kolom, `materi_wajib` |
| `src/data_analytics/laravel.py` (baru) | Adapter: mapping `isian`→`isian_singkat` (Laravel `TipeSoal`: `pilihan_ganda`/`isian`), nilai berbobot dari `bobot` Laravel, pemetaan untuk **semua** materi yang dikirim, `persentase` berbobot (aturan final v1), `materi_wajib` = N pertama |
| `src/data_analytics/api.py` | `/hitung/penilaian` & `/hitung/pretest` ikut kontrak Laravel; **semua** `/hitung/*` mewajibkan `X-Internal-Token` (403 bila salah) |
| `src/data_analytics/penilaian.py` | Perbaiki docstring yang salah soal `level` |
| `tests/test_api_hitung.py` | Ditulis ulang untuk kontrak baru + test token 403 |

### Alur penilaian (pre-test sebagai contoh)

```
POST /api/pretest/{id}/submit
  → Transaksi 1: kunci baris, isi disubmit_pada
  → PerhitunganClient::hitungPretest(payload, materi, jumlahMateriWajib)
      → POST {PERHITUNGAN_URL}/hitung/pretest + header X-Internal-Token
      → Python: cocokkan jawaban, nilai berbobot, pemetaan, materi_wajib
      → respons divalidasi ketat (tiap soal & materi harus ada; 502 bila
        tidak cocok, 503 + NilaiUlangJob bila layanan mati)
  → Transaksi 2: isi status_benar, nilai, selesai_pada, pemetaan_materi,
    rekomendasi_materi
  → 200 {nilai, pemetaan, materi_wajib}
```

### Wiring lokal (file `.env` masing-masing, tidak ikut commit)

- Token dev bersama di-generate, dipasang sebagai `INTERNAL_API_TOKEN`
  (data-analytics) dan `PERHITUNGAN_TOKEN` (Laravel);
  `PERHITUNGAN_URL=http://localhost:8001`.
- Jangan jalankan `docker-compose.dev.yml` data-analytics apa adanya untuk
  dev gabungan: ia expose analytics-api ke port host 8000 (tabrakan dengan
  `php artisan serve`). Jalankan langsung:
  `uv run uvicorn data_analytics.api:app --host 127.0.0.1 --port 8001`.

### Verifikasi

- `pytest tests/` data-analytics: 109/109 hijau.
- E2E nyata: mulai latihan → jawab → submit → 200 (`nilai`, `status_benar`
  dihitung Python asli). `/hitung/pretest` langsung: 200 + `pemetaan` +
  `materi_wajib`. Tidak ada error baru di `laravel.log`.

---

## Lampiran

- Server dev: Laravel `http://127.0.0.1:8000` (`DB_HOST=127.0.0.1 php artisan
  serve …` karena `DB_HOST=pgsql` di `.env` hanya resolve di dalam Docker),
  Python `http://127.0.0.1:8001`, FE Vite `http://localhost:5173`.
- Akun test DB dev: Super Admin `admin@example.com` / `password`, siswa
  `siswa@example.com` / `password`.
- Status push: repo Laravel sudah push (`eb75f90`); repo data-analytics
  juga sudah (commit `a8f7b33`).
- Catatan: folder data-analytics pindah dari `/Volumes/PS50U/data-analytics`
  ke `/Volumes/PS50U/bigdata/data-analytics`. Path di dokumen ini sudah
  disesuaikan; kalau dokumentasi lain masih menyebut path lama, abaikan.
