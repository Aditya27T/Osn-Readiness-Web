# Plan Integrasi Lanjutan — Siap OSN (FE + BE + Layanan Hitung)

Disusun 6 Oktober 2026 dari pembacaan docs dan kode ketiga repo:

| Repo | Folder | Branch kerja | Peran |
| --- | --- | --- | --- |
| Backend Laravel | `Osn-Readiness-Web/` | `dev` | API `/api/*`, port 8000 |
| Frontend Vue | `siap-osn-fe/` | `integrasi` | SPA Vite, port 5173 |
| Layanan hitung Python | `data-analytics/` | `main` | `/hitung/*`, port 8001 |

Cara pakai dokumen ini: kerjakan fase berurutan (0 → 7). Tiap langkah punya
kotak centang, file yang disentuh, kode yang ditempel, dan cara memeriksa
hasilnya. Fase 0–3 memperbaiki yang sudah terintegrasi; fase 4–7 integrasi baru.

**Catatan kejujuran:** potongan kode di bawah ditulis dari pembacaan kode,
belum dijalankan. Gate test di tiap fase adalah penentunya. Yang sudah
terverifikasi dengan dijalankan: test unit FE 76/76 lulus, test kontrak Python
`tests/test_api_hitung.py` 19/19 lulus, token bersama di kedua `.env` cocok.

---

## Konteks

Bagian ini cukup dibaca sekali. Isinya hal yang harus diketahui sebelum
menyentuh kode, supaya tiap langkah di fase-fase berikutnya bisa dipahami
tanpa membuka docs lain.

### K.1 Produk dan alur siswa

Siap OSN adalah web persiapan seleksi OSN Informatika. Ada dua tingkat seleksi:
Kabupaten (urutan 1, selalu terbuka) dan Provinsi (urutan 2, terbuka setelah
lulus Kabupaten). Dua role: `siswa` dan `Super Admin`.

Alur satu siswa di satu tingkat:

```
pre-test ──► pemetaan per materi + daftar materi wajib
                 │
                 ▼
        belajar materi wajib ──► latihan per materi (nilai ≥ batas)
                 │
                 ▼  semua materi wajib: selesai DAN latihan lolos
             simulasi (berbatas waktu, kuota percobaan terbatas)
                 │
        ┌────────┴─────────┐
        ▼                  ▼
      lulus          kuota habis tanpa lulus
  (tingkat berikut    (wajib pre-test ulang =
     terbuka)            putaran baru)
```

Istilah yang dipakai di kode dan di dokumen ini:

| Istilah | Arti |
| --- | --- |
| Putaran | Satu siklus pre-test → belajar → simulasi. Dikenali dari id pre-test-nya |
| Putaran aktif | Pre-test terakhir siswa di tingkat itu yang sudah selesai dinilai |
| Materi wajib | Materi hasil pemetaan pre-test (tabel `rekomendasi_materi`), punya `prioritas` |
| Syarat simulasi | Tiap materi wajib: progress `selesai` **dan** nilai latihan terbaik ≥ `latihan_min_nilai` |
| Aturan pemetaan | 16 parameter per tingkat (jumlah soal, persen level, kuota simulasi, passing grade), diatur admin |
| Tahap | Posisi siswa di satu tingkat; dihitung backend, FE hanya membaca |

Nilai `tahap` (dari `PutaranService::tentukanTahap`, urutan pengecekan dari atas):

| Tahap | Kondisi | Aksi yang seharusnya ditawarkan FE |
| --- | --- | --- |
| `LULUS` | Sudah lulus tingkat ini | Lanjut ke tingkat berikutnya |
| `PRETEST_BERJALAN` | Ada pre-test belum disubmit | Lanjutkan pre-test |
| `SIMULASI_BERJALAN` | Ada percobaan simulasi berjalan | Lanjutkan simulasi |
| `BELUM_PRETEST` | Belum ada putaran aktif | Mulai pre-test |
| `PUTARAN_HABIS` | Kuota simulasi habis, belum lulus | **Pre-test ulang** (backend mengizinkan pre-test baru) |
| `SIAP_SIMULASI` | Syarat terpenuhi, kuota masih ada | Mulai simulasi |
| `BELAJAR` | Selain di atas | Belajar materi wajib + latihan |

### K.2 Tiga layanan dan cara mereka bicara

```
Browser (Vue, :5173)
   │  Authorization: Bearer <token Sanctum>     Accept: application/json
   ▼
Laravel API (:8000, /api/*)  ── PostgreSQL 16 (container, :5432)
   │  X-Internal-Token: <token bersama>   (hanya saat submit)
   ▼
Layanan hitung Python (:8001, /hitung/penilaian dan /hitung/pretest)
```

- **FE tidak pernah memanggil Python.** Semua lewat Laravel.
- **Bank soal, materi, jawaban, dan nilai disimpan di Laravel.** Python hanya
  menghitung: mencocokkan jawaban dengan kunci, nilai berbobot, dan (khusus
  pre-test) pemetaan per materi + materi wajib. Python tidak menyentuh DB
  untuk dua endpoint itu.
- **Token siswa:** didapat dari `POST /api/auth/login` (`data.token`), berlaku
  7 hari, disimpan FE di `localStorage` kunci `siap_osn_token`, dipasang oleh
  interceptor di `lib/api.js`. Bukan cookie; `lib/api.js` tidak memakai
  `withCredentials`.
- **Token internal:** `PERHITUNGAN_TOKEN` (Laravel) harus sama dengan
  `INTERNAL_API_TOKEN` (Python). Sudah cocok di kedua `.env`. Salah token →
  Python 403 → Laravel 502 `LAYANAN_HITUNG_SALAH_KONFIGURASI`.
- **Kontrak Laravel ↔ Python** ada di `data-analytics/docs/kontrak-laravel.md`;
  sumber kebenarannya `PerhitunganClientInterface` + `tests/Feature/Clients/PerhitunganClientTest.php`
  di Laravel, dan `tests/test_api_hitung.py` di Python.

### K.3 Bentuk balasan Laravel yang harus diingat

- **Envelope sukses ada dua macam.** Kebanyakan `{message, data}`. Endpoint
  yang me-return Resource langsung hanya `{data}`: `GET /materi`,
  `GET /materi/{id}`, `GET /simulasi/syarat`, `GET /riwayat` (yang terakhir
  ditambah `meta` + `links` paginasi). `unwrap()` FE mengambil `res.data.data`,
  jadi aman untuk keduanya; untuk paginasi baca `res.data` langsung.
- **Error bisnis:** `{message, kode, detail}` dengan status 403/409/502/503.
  FE membedakan kasus dari `kode`, bukan dari teks `message`.
- **Error validasi:** 422 `{message, errors: {kolom: [pesan]}}`.
- **Data milik siswa lain dibalas 404**, bukan 403.
- **`kunci_jawaban` tidak pernah dikirim** ke siswa kecuali di
  `GET /hasil-simulasi/{id}/review`.
- **Soal hanya dua tipe:** `pilihan_ganda` (`pilihan_jawaban` berupa objek
  `{A: teks, B: teks, ...}`, jawaban = hurufnya) dan `isian` (string ≤ 255).
- **Endpoint "lihat pengerjaan" bercabang.** `GET /pretest/{id}`,
  `GET /quiz-pengerjaan/{id}`, `GET /hasil-simulasi/{id}` membalas bentuk
  bersoal (`data.soal`) selama belum dinilai, dan bentuk hasil setelah
  `selesai_pada` terisi. FE membedakannya dari ada-tidaknya `soal`.
  (Untuk latihan, cabang hasil baru benar setelah langkah 1.2.)

### K.4 Submit dua tahap — kenapa ada status "sedang dinilai"

Ketiga jenis pengerjaan (pre-test, latihan, simulasi) memakai pola yang sama:

```
POST .../submit
  1. Transaksi 1: isi disubmit_pada           ← jawaban TERKUNCI mulai sini
  2. Panggil Python (di luar transaksi)
  3. Transaksi 2: isi status_benar, nilai, selesai_pada
  → 200 + hasil

Bila langkah 2 gagal (Python mati / timeout):
  → NilaiUlangJob masuk antrean (butuh `php artisan queue:work`)
  → 503 {kode: "HASIL_SEDANG_DIPROSES"}
```

Akibatnya ada keadaan antara: `disubmit_pada` terisi, `selesai_pada` masih
null. Pada keadaan itu:

- simpan jawaban ditolak 409 `SUDAH_DISUBMIT`;
- `GET` pengerjaan masih membalas bentuk bersoal;
- `POST submit` boleh diulang (idempoten) dan mencoba menilai lagi.

FE saat ini tidak mengenal keadaan antara ini, sehingga siswa dikembalikan ke
mode mengerjakan. Langkah 2.3–2.6 menambah status `menilai` untuk itu.

### K.5 Aturan arsitektur yang mengikat

**Frontend** (`siap-osn-fe/docs/ARCHITECTURE_RULES.md`):

```
view / layout / component   render, validasi form, toast via pesanError()
        ↓
stores/                     state lintas halaman, dedupe, cache, $reset()
        ↓
services/<domain>.js        satu fungsi = satu endpoint, unwrap()
services/mappers/*.js       respons backend → bentuk FE (camelCase)
        ↓
lib/api.js · lib/endpoints.js · lib/errors.js
```

- Hanya `services/` yang boleh import `lib/api.js` dan `ENDPOINTS`.
- Store dan view tidak boleh tahu bentuk respons backend. Karena itu plan ini
  menaruh pembacaan `kode` dan status HTTP di `lib/errors.js` (`kodeError`,
  `statusError`), bukan di store.
- Store data per-user wajib punya `$reset()` yang dipanggil saat login/logout.
- Halaman baru di-import lazy di `router/index.js` dan wajib punya
  `meta.requiresAuth` + `meta.role`.
- Semua teks UI Bahasa Indonesia; error dan sukses lewat Toast `life: 4000`.
- Gate sebelum commit: `bunx eslint . && bun run test:unit && bun run build`.
  Jangan `bun test` dan jangan `bunx --bun vitest`.
- PrimeVue dikunci 4.5.5; jangan upgrade.

**Backend** (`Osn-Readiness-Web/docs/ARCHITECTURE_RULES.md`, `docs/HANDOFF-KONDISI.md` §9–10):

- Controller → Service → Repository → Model. Query hanya di Repository;
  controller tipis dan membalas lewat API Resource.
- `DB::transaction` di Service. Enum di `app/Enums/`, DTO di `app/DTOs/`.
- PostgreSQL 16 saja (index parsial, `jsonb`, check constraint). SQLite/MySQL
  tidak bisa dipakai, termasuk untuk test.
- Test memakai database terpisah `osn_readiness_testing` (lihat `phpunit.xml`).
- Gate: `composer test` dan `vendor/bin/pint --test`.
- Pelajaran dari sesi sebelumnya: suite hijau belum berarti fitur jalan —
  setiap paket diuji juga lewat HTTP dengan data seeder (itulah Fase 3).
- `CLAUDE.md` repo ini meminta Laravel Boost dipasang sebelum mengubah kode
  bila memakai agen AI (`composer require laravel/boost --dev && php artisan boost:install`).

### K.6 Status integrasi per fitur

| Fitur | Endpoint backend | File FE | Status |
| --- | --- | --- | --- |
| Register, login, logout, me | `/auth/*` | `services/auth.js`, `stores/auth.js` | Tersambung |
| Ubah profil | `PUT /auth/profile` | `ProfileView.vue` | Tersambung (sekolah/kelas hanya lokal) |
| Ubah / lupa / reset kata sandi | tidak ada | `ProfileView.vue`, `components/auth/*PasswordForm.vue` | Palsu (`setTimeout`) |
| Dashboard siswa | `GET /dashboard` | `services/siswa.js`, `stores/progress.js` | Tersambung; statistik simulasi masih 0 |
| Daftar tingkat | `GET /tingkat` | `stores/pretest.js` | Tersambung |
| Pre-test | `/pretest*` | `stores/pretest.js`, `PretestView.vue` | Tersambung, ada bug (Fase 2) |
| Pemetaan | hasil dari `GET /pretest/{id}` | `PemetaanKompetensiView.vue` | Tersambung, hilang saat refresh (5.1) |
| Materi + progress | `/materi*` | `stores/materi.js`, `MateriView.vue` | Tersambung |
| Latihan | `/quiz/{id}/mulai`, `/quiz-pengerjaan/*` | `stores/latihan.js`, `LatihanView.vue` | Tersambung tapi tombolnya tidak muncul (1.1) |
| Riwayat | `GET /riwayat` | `stores/riwayat.js`, `RiwayatView.vue` | Tersambung |
| Simulasi | `/simulasi*`, `/hasil-simulasi/*` | `SimulasiView.vue` | Mock seluruhnya (Fase 4) |
| Progress belajar | — | `PlaceholderView.vue` | Placeholder (5.3) |
| Admin | `/admin/*` (±50 endpoint) | `views/admin/DashboardView.vue` | Hanya tombol ping ke endpoint yang tidak ada |

### K.7 Peta file penting

| Keperluan | Lokasi |
| --- | --- |
| Daftar endpoint + kode error | `Osn-Readiness-Web/docs/API-ENDPOINTS.md` |
| Aturan bisnis per fitur | `Osn-Readiness-Web/docs/Logic per fitur.md` |
| Rute API | `Osn-Readiness-Web/routes/api.php` |
| Bentuk balasan | `Osn-Readiness-Web/app/Http/Resources/*.php` |
| Logika tahap/putaran | `Osn-Readiness-Web/app/Services/PutaranService.php` |
| Klien ke Python | `Osn-Readiness-Web/app/Contracts/Clients/PerhitunganClientInterface.php` |
| Koleksi Postman | `Osn-Readiness-Web/postman/OSN-Readiness.postman_collection.json` |
| Path API di FE | `siap-osn-fe/src/lib/endpoints.js` |
| Terjemahan kode error | `siap-osn-fe/src/lib/errors.js` |
| Kontrak yang dipegang FE | komentar kepala tiap file di `siap-osn-fe/src/services/mappers/` |
| Endpoint hitung | `data-analytics/src/data_analytics/api.py`, `laravel.py`, `schemas.py` |

### K.8 Akun, port, dan env

| Hal | Nilai |
| --- | --- |
| Akun siswa dev | `siswa@example.com` / `password` |
| Akun admin dev | `admin@example.com` / `password` |
| Laravel | `http://127.0.0.1:8000` (`APP_URL=http://localhost:8000`) |
| Python | `http://127.0.0.1:8001` (`PERHITUNGAN_URL=http://localhost:8001`) |
| Vite | `http://localhost:5173` (`strictPort`; aturan FE mewajibkan origin ini) |
| Postgres | container `osn-readiness-web-pgsql-1`, port host 5432 |
| Env FE | `VITE_API_BASE_URL=http://localhost:8000/api` |
| Timeout Laravel → Python | `PERHITUNGAN_TIMEOUT=5` detik, `PERHITUNGAN_RETRY=2` |
| Antrean | `QUEUE_CONNECTION=database` |

File `.env` tidak di-commit di ketiga repo. Laravel memakai env *immutable*:
variabel yang di-export di shell mengalahkan isi `.env` — itu sebabnya
`DB_HOST=127.0.0.1 php artisan ...` bekerja walau `.env` berisi `DB_HOST=pgsql`.

### K.9 Bagian docs yang sudah usang (jangan dijadikan acuan)

| Dokumen | Yang usang | Yang benar sekarang |
| --- | --- | --- |
| `siap-osn-fe/docs/ARCHITECTURE_RULES.md` §2 | `withCredentials: true`, auth cookie, `GET /admin/ping` | Bearer token tanpa credentials; `/admin/ping` tidak ada |
| `siap-osn-fe/docs/PLAN-INTEGRASI-API-P0-P1.md` | "Tidak ada endpoint update profil"; P2–P3 "berikutnya" | Profil, riwayat, materi, latihan sudah tersambung |
| `Osn-Readiness-Web/docs/HANDOFF-KONDISI.md` | Peta branch/PR per 3 Okt, port DB 54329, paket "belum selesai" | Semua paket B0–B3 sudah merge ke `dev`; DB di 5432 |
| `Osn-Readiness-Web/docs/RINGKASAN-SESI-PROFIL-CORS-HITUNG.md` | Path `/Volumes/PS50U/data-analytics`; "data-analytics belum commit" | Folder di `bigdata/`; sudah commit `a8f7b33` |
| `Osn-Readiness-Web/config/cors.php` (komentar) | "frontend mengirim credentials" | FE tidak mengirim credentials; setelan tetap aman dibiarkan |
| `data-analytics/docs/alur-layanan.md`, `CONTEXT.md` | Menggambarkan alur `/api/v1/*` dengan bank soal sendiri | Tidak dipanggil Laravel; yang hidup hanya dua endpoint `/hitung/*` |

### K.10 Yang sudah dan belum diverifikasi

| Klaim | Cara verifikasi | Status |
| --- | --- | --- |
| Test unit FE | `bun run test:unit` → 76/76 | Dijalankan |
| Kontrak Python | `python -m pytest tests/test_api_hitung.py` → 19/19 | Dijalankan |
| Token bersama cocok | Membandingkan hash kedua nilai `.env` | Dijalankan |
| venv Python basi | Shebang `.venv/bin/uvicorn` menunjuk path lama; `uv run pytest` gagal spawn | Dijalankan |
| `public/storage` tidak ada | `ls public/storage` | Dijalankan |
| Hanya Postgres yang hidup | `lsof` port 8000/8001/5173 | Dijalankan |
| Bug kontrak FE ↔ BE (Fase 1–2) | Membaca kode kedua sisi | Belum direproduksi di browser |
| Test Laravel | — | Belum dijalankan |
| Potongan kode di plan ini | — | Belum dijalankan |

---

## Ringkasan fase

| Fase | Isi | Repo | Perkiraan |
| --- | --- | --- | --- |
| 0 | Benahi lingkungan dev (3 masalah) | semua | 15 menit |
| 1 | PR BE `fix/kontrak-latihan` (2 bug) | BE | 1 jam |
| 2 | PR FE `fix/integrasi-stabil` (8 bug) | FE | 0,5–1 hari |
| 3 | Uji manual end-to-end lewat browser | semua | 1 jam |
| 4 | PR FE `feat/simulasi-integration` (P4) | FE | 1,5–2 hari |
| 5 | Pemetaan per id, statistik dashboard, halaman Progress | FE | 0,5–1 hari |
| 6 | Admin | FE | bertahap |
| 7 | Rapikan repo + keputusan terbuka | semua | — |

**Semua fase 0–7 selesai pada 7 Oktober 2026.** Ringkasan dan bukti
verifikasi ada di [Status akhir](#status-akhir-7-oktober-2026).

---

## Fase 0 — Benahi lingkungan dev

Tanpa fase ini semua submit berakhir 503 `HASIL_SEDANG_DIPROSES`.

### 0.1 Bangun ulang venv Python

Penyebab: folder dipindah dari `/Volumes/PS50U/data-analytics` ke
`/Volumes/PS50U/bigdata/data-analytics`; skrip di `.venv/bin` masih menunjuk
path lama sehingga `uv run uvicorn` gagal `Failed to spawn`.

- [ ] Jalankan:

```bash
cd /Volumes/PS50U/bigdata/data-analytics
rm -rf .venv
uv sync
uv run pytest tests/test_api_hitung.py -q      # harus 19 passed
```

### 0.2 Symlink storage Laravel

Penyebab: `public/storage` belum ada, jadi gambar soal dan gambar/file materi 404.

- [ ] Jalankan:

```bash
cd /Volumes/PS50U/bigdata/Osn-Readiness-Web
php artisan storage:link
ls -la public/storage        # harus berupa symlink ke storage/app/public
```

### 0.3 CORS untuk `127.0.0.1` (opsional)

Penyebab: `.env` Laravel berisi `FRONTEND_URL=http://localhost:5173` saja, dan
nilai env menggantikan default dua-origin di `config/cors.php`. Aturan FE
mewajibkan dev di `http://localhost:5173` persis, jadi langkah ini hanya perlu
bila ada yang membuka FE lewat `127.0.0.1`.

- [ ] Di `Osn-Readiness-Web/.env` ubah menjadi:

```
FRONTEND_URL=http://localhost:5173,http://127.0.0.1:5173
```

- [ ] `php artisan config:clear`

### 0.4 Nyalakan empat proses (empat terminal)

Postgres sudah hidup lewat container `osn-readiness-web-pgsql-1` di port 5432.
`.env` Laravel berisi `DB_HOST=pgsql` (hanya resolve di dalam Docker), jadi
perintah di host harus menimpa `DB_HOST`.

```bash
# Terminal 1 — Laravel API
cd /Volumes/PS50U/bigdata/Osn-Readiness-Web
DB_HOST=127.0.0.1 php artisan serve --host=127.0.0.1 --port=8000

# Terminal 2 — queue worker (NilaiUlangJob; QUEUE_CONNECTION=database)
cd /Volumes/PS50U/bigdata/Osn-Readiness-Web
DB_HOST=127.0.0.1 php artisan queue:work --tries=3

# Terminal 3 — layanan hitung Python
cd /Volumes/PS50U/bigdata/data-analytics
uv run uvicorn data_analytics.api:app --host 127.0.0.1 --port 8001

# Terminal 4 — frontend
cd /Volumes/PS50U/bigdata/siap-osn-fe
bun run dev
```

Jangan pakai `docker-compose.dev.yml` data-analytics: ia memakai port host 8000
dan bertabrakan dengan `artisan serve`.

### 0.5 Periksa sambungan

- [ ] Python hidup dan token benar (harus membalas `"nilai": 100.0`):

```bash
cd /Volumes/PS50U/bigdata/data-analytics
curl -s -X POST http://127.0.0.1:8001/hitung/penilaian \
  -H "X-Internal-Token: $(grep '^INTERNAL_API_TOKEN=' .env | cut -d= -f2-)" \
  -H 'Content-Type: application/json' \
  -d '{"soal":[{"soal_id":1,"tipe_soal":"pilihan_ganda","bobot":1,"jawaban_user":"A","kunci_jawaban":"A"}]}'
```

- [ ] Laravel hidup dan DB tersambung (harus membalas token):

```bash
curl -s -X POST http://127.0.0.1:8000/api/auth/login \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"email":"siswa@example.com","password":"password"}'
```

Kalau login gagal karena DB kosong:
`DB_HOST=127.0.0.1 php artisan migrate:fresh --seed` (menghapus data dev).

- [ ] `siap-osn-fe/.env`: `VITE_API_BASE_URL=http://localhost:8000/api` dan
  `VITE_BYPASS_AUTH` tetap dikomentari (bypass tidak punya token Sanctum).

---

## Fase 1 — PR backend `fix/kontrak-latihan`

```bash
cd /Volumes/PS50U/bigdata/Osn-Readiness-Web
git switch dev && git pull
git switch -c fix/kontrak-latihan
```

### 1.1 Kirim `quiz_id` di daftar dan detail materi

Masalah: `GET /api/materi` hanya mengirim flag `latihan_belum_tersedia`. FE
butuh id quiz untuk `POST /api/quiz/{quiz}/mulai`; tanpa itu tombol latihan
tidak pernah muncul (`MateriView.vue` mensyaratkan `detail.quizId`).

- [ ] `app/Services/BelajarService.php`, method `butir()` — tambah satu baris
  setelah `nilai_latihan_terbaik`:

```php
            'nilai_latihan_terbaik' => $nilaiTerbaik,
            'quiz_id' => $materi->quiz === null ? null : (int) $materi->quiz->id,
            'latihan_belum_tersedia' => $materi->quiz === null,
```

Relasi `quiz` sudah dimuat oleh `nilaiLatihanTerbaik()` sebelum `butir()`
dipanggil (di `daftar()` maupun `detail()`), jadi tidak menambah query — test
`test_daftar_materi_tidak_menambah_query_per_materi` harus tetap hijau.

- [ ] `tests/Feature/Api/MateriBelajarApiTest.php`, di
  `test_daftar_materi_menampilkan_progress_dan_nilai_latihan` tambah assertion
  di samping `latihan_belum_tersedia` (sesuaikan nama variabel quiz di test itu):

```php
            ->assertJsonPath('data.0.quiz_id', $quiz->id)
```

- [ ] Tambah test materi tanpa quiz → `quiz_id` null:

```php
    public function test_materi_tanpa_latihan_mengirim_quiz_id_null(): void
    {
        $this->actingAs($this->siswa, 'sanctum')
            ->getJson('/api/materi?tingkat_id='.$this->tingkat->id)
            ->assertOk()
            ->assertJsonPath('data.0.quiz_id', null)
            ->assertJsonPath('data.0.latihan_belum_tersedia', true);
    }
```

(Samakan cara login dan nama properti dengan `setUp()` file itu.)

### 1.2 `GET /api/quiz-pengerjaan/{id}` membalas hasil bila sudah selesai

Masalah: `docs/API-ENDPOINTS.md` menulis "Soal bila berjalan, hasil bila
selesai", tetapi controller selalu membalas bentuk bersoal. FE membedakan
pengerjaan/hasil dari ada-tidaknya `soal`, sehingga latihan yang sudah dinilai
terbuka lagi sebagai pengerjaan dan autosave-nya kena 409.

- [ ] `app/Http/Controllers/Api/QuizPengerjaanController.php`, ganti isi `show()`:

```php
    public function show(Request $request, QuizPengerjaan $pengerjaan): JsonResponse
    {
        $dimulai = $this->latihanService->ringkasan($request->user(), $pengerjaan->id);

        // Sudah dinilai: balas hasil, sama seperti pre-test dan simulasi.
        if ($dimulai->pengerjaan->selesai_pada !== null) {
            return response()->json([
                'message' => 'OK',
                'data' => new HasilLatihanResource($dimulai->pengerjaan),
            ]);
        }

        return response()->json([
            'message' => 'OK',
            'data' => new LatihanPengerjaanResource($dimulai),
        ]);
    }
```

`ringkasan()` sudah memasang relasi `jawaban` lewat `setRelation`, dan
`HasilLatihanResource` membaca `quiz->materi_id` + `jawaban`, jadi tidak perlu
perubahan service.

- [ ] `tests/Feature/Api/LatihanApiTest.php` — tambah test (pakai helper yang
  sudah ada di file: `buatPutaranAktif()`, `isiBankSoal()`):

```php
    public function test_lihat_pengerjaan_selesai_membalas_hasil_tanpa_soal(): void
    {
        $this->buatPutaranAktif();
        $this->isiBankSoal(10);

        $id = $this->actingAs($this->siswa, 'sanctum')
            ->postJson("/api/quiz/{$this->quiz->id}/mulai")
            ->json('data.id');

        $this->postJson("/api/quiz-pengerjaan/{$id}/submit")->assertOk();

        $this->getJson("/api/quiz-pengerjaan/{$id}")
            ->assertOk()
            ->assertJsonMissingPath('data.soal')
            ->assertJsonStructure(['data' => ['id', 'quiz_id', 'materi_id', 'nilai', 'jawaban']]);
    }
```

- [ ] Periksa `tests/Feature/Api/RingkasanPengerjaanApiTest.php`: bila ada test
  yang mengharapkan `data.soal` pada pengerjaan latihan yang sudah selesai,
  ubah harapannya ke bentuk hasil.

### 1.3 Docs + gate + PR

- [ ] `docs/API-ENDPOINTS.md`: baris `GET /api/materi` tambah keterangan
  "tiap baris memuat `quiz_id` (null bila latihan belum tersedia)".
- [ ] Koleksi Postman: tidak ada endpoint baru, tidak perlu diubah.
- [ ] Gate (DB test terpisah `osn_readiness_testing`, lihat `phpunit.xml`):

```bash
DB_HOST=127.0.0.1 composer test
vendor/bin/pint --test
```

- [ ] Commit `fix(latihan): kirim quiz_id di materi dan balas hasil pada pengerjaan selesai`,
  PR ke `dev`, merge, lalu restart `artisan serve`.

---

## Fase 2 — PR frontend `fix/integrasi-stabil`

```bash
cd /Volumes/PS50U/bigdata/siap-osn-fe
git switch integrasi && git pull
git switch -c fix/integrasi-stabil
```

Urutan langkah di bawah sengaja: helper dulu, lalu mapper, store, view.

### 2.1 `lib/errors.js` — helper kode + terjemahan yang kurang

- [ ] Tambah ke `TERJEMAHAN_KODE`:

```js
  BELUM_PRETEST: 'Selesaikan pre-test dulu untuk membuka fitur ini',
  SIMULASI_BELUM_DINILAI: 'Simulasi sebelumnya masih dinilai, coba lagi sebentar',
  LAYANAN_HITUNG_SALAH_KONFIGURASI: 'Penilaian sedang bermasalah, hubungi admin',
```

- [ ] Tambah dua helper (supaya store tidak mem-parsing respons sendiri):

```js
// Kode bisnis backend ({ kode }) atau null.
export function kodeError(err) {
  return err?.response?.data?.kode ?? null
}

// Status HTTP atau null (error jaringan/abort).
export function statusError(err) {
  return err?.response?.status ?? null
}
```

- [ ] `lib/errors.spec.js`: tambah kasus untuk tiga kode baru dan dua helper.

### 2.2 Pesan error bisnis hilang di toast

Masalah: store `pretest`, `latihan`, `materi` menangkap error axios lalu
`throw new Error('gagal-mulai')`. View memanggil `pesanError(err)` pada Error
polos itu, sehingga toast selalu "Terjadi kesalahan, coba lagi" dan pesan
seperti `TINGKAT_TERKUNCI` atau `PUTARAN_MASIH_BERJALAN` tidak pernah tampil.

- [ ] Di `stores/pretest.js`, `stores/latihan.js`, `stores/materi.js`: setiap
  `catch { ...; throw new Error('gagal-...') }` diubah menjadi
  `catch (err) { ...; throw err }`. Contoh `mulai` pre-test:

```js
    try {
      terapkanPengerjaan(await pretestService.mulai({ tingkatId: tid, signal }))
    } catch (err) {
      if (!signal.aborted) error.value = true
      throw err
    } finally {
      if (!signal.aborted) loading.value = false
    }
```

- [ ] Spec store yang mengharapkan pesan `'gagal-mulai'` / `'gagal-muat'` /
  `'gagal-kumpulkan'` diubah menjadi `rejects.toBe(errAsli)` atau `rejects.toThrow()`.

### 2.3 Mapper: kenali "sudah disubmit, belum dinilai"

Masalah: saat layanan hitung gagal, backend sudah mengisi `disubmit_pada` tapi
`selesai_pada` masih null; `GET /pretest/{id}` tetap membalas bentuk bersoal.
Mapper membuang `disubmit_pada`, jadi FE mengira masih boleh dikerjakan.

- [ ] `services/mappers/pretest.js`, `paketPretest`:

```js
export function paketPretest(r) {
  if (Array.isArray(r?.soal)) {
    return {
      // Sudah disubmit tapi belum dinilai -> jawaban terkunci, tunggu hasil.
      jenis: r.disubmit_pada ? 'menunggu' : 'pengerjaan',
      id: r.id,
      tingkatId: r.tingkat_id ?? null,
      soal: [...r.soal].sort((a, b) => angka(a.urutan) - angka(b.urutan)).map(mapSoal),
    }
  }
  return { jenis: 'hasil', hasil: mapHasilPretest(r ?? {}) }
}
```

- [ ] `services/mappers/latihan.js`, `paketLatihan`: perubahan `jenis` yang sama.
- [ ] `services/pretest.js` dan `services/latihan.js`, fungsi `mulai`: ganti
  pengecekan `paket.jenis !== 'pengerjaan'` menjadi `paket.jenis === 'hasil'`
  (mulai tidak pernah membalas hasil; `'menunggu'` tidak mungkin dari mulai,
  tapi jangan dilempar sebagai error).
- [ ] Spec mapper/service: tambah kasus `disubmit_pada` terisi → `jenis: 'menunggu'`.

### 2.4 `stores/pretest.js` — tiga perbaikan

**(a) AbortController terpisah untuk daftar tingkat.** Masalah: `muatTingkat`
memanggil `sinyalBaru()` yang sama dengan `mulai`/`lanjutkan`/`kumpulkan`.
`AppLayout` memanggil `progress.fetchDashboard()` yang di latar memanggil
`pretest.muatTingkat()`; kalau saat itu `lanjutkan` sedang jalan (reload di
`/siswa/pretest` atau `/siswa/pemetaan`), request-nya dibatalkan dan halaman
menampilkan "Tidak dapat membuka pre-test tersimpan".

- [ ] Tambah variabel `let controllerTingkat = null` dan pakai di `muatTingkat`:

```js
  async function muatTingkat({ force = false } = {}) {
    if (inflightTingkat) return inflightTingkat
    if (!force && tingkatList.value.length && Date.now() - tingkatDimuatPada < SEGAR_TINGKAT_MS) {
      return Promise.resolve()
    }
    controllerTingkat?.abort()
    controllerTingkat = new AbortController()
    const { signal } = controllerTingkat
    // `loading` tidak disentuh di sini: itu milik mulai/lanjutkan.
    inflightTingkat = pretestService
      .tingkat({ signal })
      .then((list) => {
        if (signal.aborted) return
        tingkatList.value = list
        tingkatDimuatPada = Date.now()
      })
      .catch(() => {
        if (!signal.aborted) error.value = true
      })
      .finally(() => {
        inflightTingkat = null
      })
    return inflightTingkat
  }
```

- [ ] Di `$reset()` tambah `controllerTingkat?.abort(); controllerTingkat = null`.

**(b) Status "sedang dinilai".**

- [ ] Tambah `MENILAI: 'menilai'` ke `STATUS`.
- [ ] Import `kodeError, statusError` dari `@/lib/errors.js`.
- [ ] Ganti `lanjutkan` dan `kumpulkan`, tambah `cekHasil`:

```js
  function terapkanPaket(paket) {
    if (paket.jenis === 'pengerjaan') return terapkanPengerjaan(paket)
    if (paket.jenis === 'menunggu') {
      pretestId.value = paket.id
      tingkatId.value = paket.tingkatId
      status.value = STATUS.MENILAI
      tulisSimpanan({ id: paket.id, tingkatId: paket.tingkatId })
      return
    }
    hasil.value = paket.hasil
    pretestId.value = paket.hasil.id
    tingkatId.value = paket.hasil.tingkatId
    status.value = STATUS.SELESAI
    tulisSimpanan(null)
  }

  // GET lihat: resume pengerjaan, menunggu nilai, atau langsung hasil.
  async function lanjutkan({ id } = {}) {
    const signal = sinyalBaru()
    loading.value = true
    error.value = false
    try {
      terapkanPaket(await pretestService.lihat({ id, signal }))
    } catch (err) {
      // ID milik akun lain / sudah terhapus: buang supaya tidak macet.
      if (statusError(err) === 404) tulisSimpanan(null)
      if (!signal.aborted) error.value = true
      throw err
    } finally {
      if (!signal.aborted) loading.value = false
    }
  }

  // POST submit (idempoten). 503 HASIL_SEDANG_DIPROSES bukan kegagalan:
  // jawaban sudah terkunci di server, tinggal menunggu nilai.
  async function kumpulkan() {
    const signal = sinyalBaru()
    status.value = STATUS.MENGUMPULKAN
    error.value = false
    try {
      hasil.value = await pretestService.kumpulkan({ id: pretestId.value, signal })
      status.value = STATUS.SELESAI
      tulisSimpanan(null)
      return hasil.value
    } catch (err) {
      if (kodeError(err) === 'HASIL_SEDANG_DIPROSES') {
        status.value = STATUS.MENILAI
        return null
      }
      if (!signal.aborted) {
        error.value = true
        status.value = STATUS.MENGERJAKAN
      }
      throw err
    }
  }

  // Dipanggil berkala oleh view saat status MENILAI. Submit ulang aman
  // (idempoten) dan sekaligus memicu penilaian ulang di server.
  async function cekHasil() {
    if (status.value !== STATUS.MENILAI || !pretestId.value) return null
    try {
      hasil.value = await pretestService.kumpulkan({ id: pretestId.value })
      status.value = STATUS.SELESAI
      tulisSimpanan(null)
      return hasil.value
    } catch {
      return null // masih diproses; view mencoba lagi
    }
  }
```

- [ ] Ekspor `cekHasil` di `return { ... }`.

**(c) Spec.** `stores/pretest.spec.js`:

- [ ] `muatTingkat` saat `lanjutkan` in-flight tidak membatalkan `lanjutkan`.
- [ ] `kumpulkan` dengan error `{ response: { status: 503, data: { kode: 'HASIL_SEDANG_DIPROSES' } } }`
  → resolve `null`, `status === 'menilai'`, `idTersimpan()` tetap ada.
- [ ] `lanjutkan` dengan error 404 → `idTersimpan()` jadi `null`.
- [ ] `lanjutkan` dengan paket `'menunggu'` → `status === 'menilai'`.

### 2.5 `views/siswa/PretestView.vue` — pakai status baru

- [ ] `siapkanAwal()`: bagian resume diganti supaya ID basi jatuh ke pemilih
  tingkat, bukan macet:

```js
  const tersimpan = pretest.idTersimpan()
  if (tersimpan?.id) {
    try {
      await pretest.lanjutkan({ id: tersimpan.id })
    } catch {
      // ID sudah dibuang store (404) -> lanjut ke pemilih tingkat di bawah.
      if (pretest.idTersimpan()) {
        galatAwal.value = 'Tidak dapat membuka pre-test tersimpan. Coba lagi.'
        memuatAwal.value = false
        return
      }
    }
    if (pretest.status === STATUS.SELESAI) {
      router.replace({ name: 'siswa.pemetaan', params: { id: pretest.pretestId } })
      return
    }
    if (pretest.status === STATUS.MENILAI) {
      mulaiPolling()
      memuatAwal.value = false
      return
    }
    if (pretest.status === STATUS.MENGERJAKAN) {
      seedJawaban()
      memuatAwal.value = false
      return
    }
  }
```

- [ ] `kumpulkan()`: tangani hasil `null` (sedang dinilai):

```js
  try {
    const hasil = await pretest.kumpulkan()
    modalSelesai.value = false
    if (!hasil) {
      mulaiPolling()
      return
    }
    progress.markPreTestCompleted()
    await progress.fetchDashboard({ force: true }).catch(() => {})
    router.push({ name: 'siswa.pemetaan', params: { id: pretest.pretestId } })
  } catch (err) { /* tetap seperti sekarang */ }
```

- [ ] Polling (tiap 5 detik, berhenti saat unmount):

```js
let timerPolling = null
function mulaiPolling() {
  clearInterval(timerPolling)
  timerPolling = setInterval(async () => {
    const hasil = await pretest.cekHasil()
    if (!hasil) return
    clearInterval(timerPolling)
    progress.markPreTestCompleted()
    await progress.fetchDashboard({ force: true }).catch(() => {})
    router.push({ name: 'siswa.pemetaan', params: { id: pretest.pretestId } })
  }, 5000)
}
onBeforeUnmount(() => clearInterval(timerPolling))
```

- [ ] Template: tambah satu blok `v-else-if="pretest.status === STATUS.MENILAI"`
  berisi teks "Jawabanmu sudah dikumpulkan dan sedang dinilai. Halaman ini
  akan berpindah otomatis." Timer 90 menit jangan memanggil `kumpulkan()` saat
  status bukan `MENGERJAKAN` (pengecekan itu sudah ada).

### 2.6 Latihan — buang resume lewat localStorage

Masalah: `LatihanView` membuka ID tersimpan apa pun tanpa mencocokkan
`quizId` di URL, jadi latihan materi A bisa terbuka saat siswa memilih materi
B. Backend sudah idempoten: `POST /quiz/{id}/mulai` mengembalikan pengerjaan
berjalan (200) bila ada. Jadi `mulai` saja sudah cukup untuk resume.

- [ ] `stores/latihan.js`: hapus `KUNCI_LATIHAN_AKTIF`, `bacaSimpanan`,
  `tulisSimpanan`, `idTersimpan`, dan `lanjutkan`. Tambah `MENILAI: 'menilai'`
  ke `STATUS_LATIHAN`. `kumpulkan` dan `cekHasil` mengikuti pola 2.4(b)
  (tanpa baris `tulisSimpanan`).
- [ ] `views/siswa/LatihanView.vue`, `onMounted`:

```js
onMounted(async () => {
  const quizId = Number(route.params.quizId)
  if (!Number.isFinite(quizId) || quizId <= 0) {
    galatAwal.value = 'ID quiz tidak valid. Buka latihan dari halaman materi.'
    memuatAwal.value = false
    return
  }
  // Store hangat hanya dipakai bila memang quiz yang sama.
  const hangat =
    latihan.quizId === quizId &&
    latihan.status === STATUS_LATIHAN.MENGERJAKAN &&
    latihan.soal.length
  if (!hangat) {
    try {
      await latihan.mulai({ quizId })
    } catch (err) {
      galatAwal.value = pesanError(err, 'Tidak dapat membuka latihan. Coba lagi.')
      memuatAwal.value = false
      return
    }
  }
  seedJawaban()
  memuatAwal.value = false
})
```

- [ ] `kumpulkan()` di view: bila hasil `null` → tampilkan blok "sedang
  dinilai" + polling `latihan.cekHasil()` tiap 5 detik (pola 2.5).
- [ ] Setelah hasil keluar, panggil `useMateriStore().fetchDaftar({ tingkatId:
  materi.tingkatId, force: true })` supaya nilai terbaik di halaman materi ikut baru.
- [ ] `stores/latihan.spec.js`: hapus kasus `idTersimpan`; tambah kasus 503 → `'menilai'`.
- [ ] `stores/latihan.js` di `localStorage`: bersihkan kunci lama sekali jalan
  di `$reset()` → `localStorage.removeItem('siap_osn_latihan_aktif')`.

### 2.7 `services/mappers/belajar.js` — `quiz_id` sudah ada

- [ ] Hapus komentar TODO; baris `quizId: r.quiz_id ?? null` sudah benar setelah Fase 1.
- [ ] `services/belajar.spec.js`: tambah assertion `quizId` terisi dari `quiz_id`.
- [ ] Cek `MateriView.vue` baris ±164–171: tombol "Mulai latihan" muncul bila
  `detail.latihanTersedia && detail.quizId`; cabang `v-else-if` ("latihan
  tersedia tapi belum bisa dibuka") sekarang tidak akan pernah terpakai —
  hapus cabang itu.

### 2.8 Reset semua store saat login/logout

Masalah: `stores/auth.js` hanya memanggil `useProgressStore().$reset()`.
Store `pretest`, `latihan`, `materi`, `riwayat` (dan `siap_osn_pretest_aktif`
di localStorage) tetap berisi data akun sebelumnya.

- [ ] `stores/auth.js`:

```js
import { useLatihanStore } from '@/stores/latihan.js'
import { useMateriStore } from '@/stores/materi.js'
import { usePretestStore } from '@/stores/pretest.js'
import { useProgressStore } from '@/stores/progress.js'
import { useRiwayatStore } from '@/stores/riwayat.js'

// Satu tempat: semua data milik akun dibuang saat sesi berganti.
function resetDataAkun() {
  useProgressStore().$reset()
  usePretestStore().$reset()
  useLatihanStore().$reset()
  useMateriStore().$reset()
  useRiwayatStore().$reset()
}
```

- [ ] Ganti tiga pemanggilan `useProgressStore().$reset()` (di `login`,
  `logout`, `$reset`) dengan `resetDataAkun()`. Saat Fase 4 selesai, tambahkan
  `useSimulasiStore().$reset()` di sini.
- [ ] `stores/auth.spec.js`: login akun kedua → `usePretestStore().idTersimpan()` null.

### 2.9 Hal kecil

- [ ] `stores/progress.js`: cuplikan riwayat diambil sebelum daftar tingkat
  dimuat, jadi judulnya tanpa nama tingkat. Pindahkan pemanggilan
  `useRiwayatStore().terbaris()` ke dalam `muatStatistikMateri` **setelah**
  `await pretest.muatTingkat()`.
- [ ] `stores/progress.js`: `preTestSelesai: hasil.preTestSelesai ||
  data.value.preTestSelesai` membuat flag menempel selama sesi walau data
  server berkata lain. Ganti jadi `preTestSelesai: hasil.preTestSelesai` —
  `markPreTestCompleted()` tetap memberi update optimistis sampai fetch
  berikutnya.
- [ ] **Pre-test ulang tidak pernah ditawarkan.** Saat kuota simulasi habis
  tanpa lulus, tahap menjadi `PUTARAN_HABIS` dan backend mengizinkan pre-test
  baru (lihat K.1). Tetapi `mappers/dashboard.js` memasukkan `PUTARAN_HABIS`
  ke `TAHAP_PRETEST_SELESAI`, jadi dashboard menganggap pre-test beres dan
  siswa buntu. Perbaikan:
  - `mappers/dashboard.js`: tambah field
    `perluPretestUlang: aktif?.tahap === 'PUTARAN_HABIS'` (dan `false` di
    `dashboardKosong()`). `PUTARAN_HABIS` tetap di himpunan, supaya materi
    dan riwayat putaran lama masih bisa dibuka.
  - `DashboardView.vue`: bila `d.perluPretestUlang`, banner berganti menjadi
    "Kuota simulasi putaran ini habis. Ambil pre-test ulang untuk memulai
    putaran baru." dengan tombol ke `siswa.pretest`.
  - `services/siswa.spec.js`: kasus tahap `PUTARAN_HABIS` → `perluPretestUlang: true`.
- [ ] `services/admin.js` + `lib/endpoints.js`: `/admin/ping` tidak ada di
  backend. Ganti `ping: '/admin/ping'` → `dashboard: '/admin/dashboard'`, dan
  `adminService.ping()` → `adminService.dashboard()` yang mengembalikan
  `unwrap(res)`. Di `views/admin/DashboardView.vue` tombol "tes akses" cukup
  menampilkan toast sukses bila request 200. Perbarui `services/admin.spec.js`.

### 2.10 Gate + PR

```bash
bunx eslint . && bun run test:unit && bun run build
```

- [ ] Commit per langkah (2.1 … 2.9), PR `fix/integrasi-stabil` → `integrasi`.

---

## Fase 3 — Uji manual end-to-end

Empat proses Fase 0 hidup. Buka `http://localhost:5173`, DevTools tab Network terbuka.

| # | Langkah | Hasil yang benar |
| --- | --- | --- |
| 1 | Register akun baru, lalu login | Masuk dashboard, banner "belum pre-test" |
| 2 | Mulai pre-test Kabupaten | `POST /api/pretest` 201, 30 soal tampil, gambar soal termuat |
| 3 | Jawab 3 soal, reload halaman | Kembali ke soal dengan 3 jawaban terisi (tanpa pesan galat) |
| 4 | Kumpulkan | `POST .../submit` 200, pindah ke pemetaan, nilai + tabel materi tampil |
| 5 | Buka Materi | Materi wajib di atas, ada label prioritas |
| 6 | Buka satu materi wajib → Tandai selesai | `PUT .../progress` 200, status "Selesai" |
| 7 | Mulai latihan dari materi itu | `POST /api/quiz/{id}/mulai` 201, 10 soal |
| 8 | Kumpulkan latihan | 200, nilai tampil; kembali ke materi → nilai terbaik terisi |
| 9 | Buka latihan materi lain | Soalnya milik materi itu (bukan latihan sebelumnya) |
| 10 | Riwayat | Baris pre-test + latihan, filter jenis jalan, judul memuat nama tingkat |
| 11 | Matikan Python (Ctrl+C terminal 3), kumpulkan satu latihan | Layar "sedang dinilai", bukan toast gagal |
| 12 | Nyalakan Python lagi | Dalam ≤ 10 detik hasil muncul sendiri |
| 13 | Logout, login `siswa@example.com` / `password` | Tidak ada sisa data akun pertama; `/siswa/pretest` tidak menampilkan galat |
| 14 | Login `admin@example.com` / `password` | Masuk `/admin`, tombol tes akses sukses |

- [ ] Semua baris lolos. Yang gagal: catat request + respons dari tab Network,
  perbaiki di branch fase terkait sebelum lanjut.

### Hasil Fase 3 (6 Oktober 2026)

Dijalankan lewat HTTP dengan skrip `fase3.py` (bukan klik browser), karena
ketika ini hanya terminal yang tersedia. Alur, kode status, dan bentuk JSON
diuji persis seperti yang dilihat FE; yang tidak teruji adalah rendering
Vue/CSS dan perilaku DOM.

**30/30 pemeriksaan lolos.** Skrip: `/tmp/opencode/fase3.py`.

| # | Hasil terukur |
| --- | --- |
| 1 | Register 201, token 51 karakter, dashboard `tahap: BELUM_PRETEST` |
| 2 | `POST /api/pretest` 201, 30 soal; semua `pilihan_jawaban` berupa objek |
| 3 | Setelah jawab 3 soal lalu GET ulang: 3 jawaban terpulih |
| 4 | Submit 200, nilai 9,8, 5 baris pemetaan, 3 materi wajib |
| 5 | 5 materi, 3 wajib (prioritas 1,2,3); tiap baris ada `quiz_id` 1–5 |
| 6 | `PUT .../progress` 200, status `selesai` |
| 7 | `POST /api/quiz/{quiz_id}/mulai` 201, 10 soal (berkas dari `quiz_id`) |
| 8 | Submit 200 nilai 100; `nilai_latihan_terbaik` = 100; GET lagi → hasil tanpa `soal` |
| 9 | Latihan materi 2 → `soal_id` 253, beda dari latihan materi 1 (101) |
| 10 | 2 baris (pretest + latihan), judul "Latihan — Tingkat Kabupaten/Kota", filter jenis jalan |
| 11 | Python mati → submit 503 `HASIL_SEDANG_DIPROSES`; `disubmit_pada` terisi + `selesai_pada` null → mapper `menunggu`; simpan jawaban 409 `SUDAH_DISUBMIT` |
| 12 | Python hidup lagi → nilai muncul sendiri dalam ≤ 2 detik (`NilaiUlangJob` DONE) |
| 13 | Logout 200; pengerjaan akun lama dibalas 404 (bukan 403) |
| 14 | `/admin/dashboard` 200 dengan token admin; siswa biasa dapat 403 |

Catatan operasional: `NilaiUlangJob` pertama FAIL (Python masih mati),
percobaan kedua DONE 12 detik kemudian — sesuai `--tries=3`.

Yang **belum** teruji dan masih perlu cek di browser: transisi halaman
(redirect ke pemetaan), polling 5 detik di layar "sedang dinilai",
autosave 800 ms, dan tampilan tombol latihan di `MateriView`.

---

## Fase 4 — PR frontend `feat/simulasi-integration` (P4)

Backend sudah lengkap. Kontrak di bawah diambil dari controller + resource.

### 4.0 Kontrak backend

| Endpoint | Balasan `data` |
| --- | --- |
| `GET /simulasi/syarat?tingkat_id=` | `{terpenuhi, alasan: 'belum_pretest'\|null, rincian: [{materi_id, judul, prioritas, selesai, nilai_latihan, batas, latihan_belum_tersedia}]}` — **tanpa** `message` |
| `GET /simulasi?tingkat_id=` | `[{id, nama_simulasi, jumlah_soal, durasi_menit, is_aktif, sisa_kuota}]` |
| `POST /simulasi/{id}/mulai` | 201/200 `{id, simulasi_id, pretest_id, mulai_pada, batas_pada, disubmit_pada, selesai_pada, nilai, soal: [...]}` |
| `GET /hasil-simulasi/{id}` | bentuk di atas bila berjalan; bentuk hasil bila `selesai_pada` terisi |
| `PUT /hasil-simulasi/{id}/jawaban` | `{message}`; body `{soal_id, jawaban_user}` |
| `POST /hasil-simulasi/{id}/submit` | `{id, simulasi_id, pretest_id, nilai, jumlah_benar, jumlah_salah, lulus, disubmit_pada, selesai_pada, jawaban: [{soal_id, urutan, bobot, jawaban_user, status_benar}]}` |
| `GET /hasil-simulasi/{id}/review` | hasil + `soal: [{urutan, bobot, jawaban_user, status_benar, soal: {id, materi_id, level, tipe_soal, pertanyaan, pilihan_jawaban, kunci_jawaban, gambar, konteks: {judul, isi_konteks}, pembahasan}}]` |

Hal yang berbeda dari pre-test/latihan:

- Selalu kirim `tingkat_id` ke `GET /simulasi` dan `GET /simulasi/syarat`.
- Ada batas waktu server: `batas_pada`. Simpan jawaban setelah batas + 30
  detik → 409 `WAKTU_HABIS`.
- Soal pada review **bersarang** di `soal: {...}` (pengerjaan: datar), dan
  hanya di sini `kunci_jawaban` + `pembahasan` dikirim.
- Error mulai: 409 `SYARAT_SIMULASI_BELUM_TERPENUHI` (`detail` berisi
  rincian), `SUDAH_LULUS`, `KUOTA_SIMULASI_HABIS`, `SIMULASI_BELUM_DINILAI`;
  403 `TINGKAT_TERKUNCI`; 503 `BANK_SOAL_TIDAK_CUKUP`.
- Resume: tidak perlu localStorage. `POST mulai` membalas 200 dengan
  percobaan berjalan. Dashboard memberi tahu lewat `tahap: 'SIMULASI_BERJALAN'`.

### 4.1 `lib/endpoints.js`

- [ ] Tambah di `siswa`:

```js
    simulasiSyarat: '/simulasi/syarat',
    simulasi: '/simulasi',
    simulasiMulai: (id) => `/simulasi/${id}/mulai`,
    hasilSimulasi: (id) => `/hasil-simulasi/${id}`,
    hasilSimulasiJawaban: (id) => `/hasil-simulasi/${id}/jawaban`,
    hasilSimulasiSubmit: (id) => `/hasil-simulasi/${id}/submit`,
    hasilSimulasiReview: (id) => `/hasil-simulasi/${id}/review`,
```

### 4.2 `services/mappers/simulasi.js` (baru)

- [ ] Isi:

```js
import { mapSoal } from '@/services/mappers/pretest.js'

const angka = (v) => {
  const n = Number(v)
  return Number.isFinite(n) ? n : 0
}
const nilaiAtauNull = (v) => (v == null || !Number.isFinite(Number(v)) ? null : Number(v))
const daftar = (v) => (Array.isArray(v) ? v : [])

export function mapSyarat(r) {
  return {
    terpenuhi: Boolean(r?.terpenuhi),
    alasan: r?.alasan ?? null,
    rincian: daftar(r?.rincian).map((x) => ({
      materiId: x.materi_id,
      judul: x.judul ?? '',
      prioritas: angka(x.prioritas),
      selesai: Boolean(x.selesai),
      nilaiLatihan: nilaiAtauNull(x.nilai_latihan),
      batas: angka(x.batas),
      latihanTersedia: !x.latihan_belum_tersedia,
    })),
  }
}

export function mapSimulasi(r) {
  return {
    id: r.id,
    nama: r.nama_simulasi ?? '',
    jumlahSoal: angka(r.jumlah_soal),
    durasiMenit: angka(r.durasi_menit),
    aktif: Boolean(r.is_aktif),
    sisaKuota: r.sisa_kuota == null ? null : angka(r.sisa_kuota),
  }
}

export function mapHasilSimulasi(r) {
  return {
    id: r.id,
    simulasiId: r.simulasi_id ?? null,
    nilai: nilaiAtauNull(r.nilai),
    jumlahBenar: r.jumlah_benar == null ? null : angka(r.jumlah_benar),
    jumlahSalah: r.jumlah_salah == null ? null : angka(r.jumlah_salah),
    lulus: r.lulus == null ? null : Boolean(r.lulus),
    disubmitPada: r.disubmit_pada ?? null,
    selesaiPada: r.selesai_pada ?? null,
    jawaban: daftar(r.jawaban)
      .map((j) => ({
        soalId: j.soal_id,
        urutan: angka(j.urutan),
        jawabanUser: j.jawaban_user ?? null,
        benar: Boolean(j.status_benar),
      }))
      .sort((a, b) => a.urutan - b.urutan),
  }
}

// GET hasil-simulasi/{id} bercabang: ada `soal` -> pengerjaan/menunggu,
// selain itu -> hasil.
export function paketSimulasi(r) {
  if (Array.isArray(r?.soal)) {
    return {
      jenis: r.disubmit_pada ? 'menunggu' : 'pengerjaan',
      id: r.id,
      simulasiId: r.simulasi_id ?? null,
      mulaiPada: r.mulai_pada ?? null,
      batasPada: r.batas_pada ?? null,
      soal: [...r.soal].sort((a, b) => angka(a.urutan) - angka(b.urutan)).map(mapSoal),
    }
  }
  return { jenis: 'hasil', hasil: mapHasilSimulasi(r ?? {}) }
}

// Review: soal bersarang + kunci + pembahasan.
export function mapReview(r) {
  return {
    ...mapHasilSimulasi({ ...r, jawaban: [] }),
    soal: daftar(r.soal)
      .map((b) => ({
        ...mapSoal({ ...b.soal, urutan: b.urutan, bobot: b.bobot, jawaban_user: b.jawaban_user }),
        benar: Boolean(b.status_benar),
        kunci: b.soal?.kunci_jawaban ?? null,
        pembahasan: b.soal?.pembahasan ?? null,
      }))
      .sort((a, b) => a.urutan - b.urutan),
  }
}
```

- [ ] `services/mappers/simulasi.spec.js`: satu kasus per fungsi, termasuk
  cabang `'menunggu'` dan review bersarang.

### 4.3 `services/simulasi.js` (baru)

- [ ] Satu fungsi per endpoint, pola `services/latihan.js`:
  `syarat({ tingkatId, signal })`, `daftar({ tingkatId, signal })`,
  `mulai({ simulasiId, signal })`, `lihat({ id, signal })`,
  `simpanJawaban({ id, soalId, jawaban, signal })`, `kumpulkan({ id, signal })`,
  `review({ id, signal })`. Semua pakai `unwrap` (termasuk `syarat`, yang
  membalas `{data}` tanpa `message`).
- [ ] `services/simulasi.spec.js` (mock `api`): path + payload tiap fungsi.

### 4.4 `stores/simulasi.js` (baru)

- [ ] State: `status` (`idle | mengerjakan | mengumpulkan | menilai | selesai`),
  `daftar`, `syarat`, `tingkatId`, `hasilId`, `simulasiId`, `batasPada`,
  `soal`, `hasil`, `review`, `loading`, `error`, `simpanError`.
- [ ] Aksi (salin pola `stores/pretest.js` hasil Fase 2):
  - `muatRuang({ tingkatId })` → `Promise.all([daftar, syarat])`, controller sendiri.
  - `mulai({ simulasiId })` → terapkan paket; simpan `batasPada`.
  - `lanjutkan({ id })` → untuk membuka hasil dari riwayat (`referensi_id`).
  - `simpanJawaban({ soalId, jawaban })` → bila `kodeError(err) === 'WAKTU_HABIS'`
    kembalikan `'waktu-habis'` supaya view langsung mengumpulkan.
  - `kumpulkan()` + `cekHasil()` → pola 2.4(b).
  - `muatReview({ id })`.
  - `$reset()`; daftarkan di `resetDataAkun()` (`stores/auth.js`).
- [ ] `stores/simulasi.spec.js`: mulai, 409 syarat, WAKTU_HABIS, 503 → menilai, reset.

### 4.5 View dan route

- [ ] `router/index.js` — tambah (semuanya `requiresAuth`, `role: 'siswa'`):

```js
{ path: 'siswa/simulasi/ujian/:simulasiId', name: 'siswa.ujian', component: UjianSimulasiView },
{ path: 'siswa/simulasi/hasil/:hasilId', name: 'siswa.simulasi.hasil', component: HasilSimulasiView },
{ path: 'siswa/simulasi/hasil/:hasilId/pembahasan', name: 'siswa.simulasi.review', component: ReviewSimulasiView },
```

`SimulasiView.vue` sudah mengecek `router.hasRoute('siswa.ujian')`; ubah
param-nya dari `{ level: level.key }` menjadi `{ simulasiId }`.

- [ ] **`views/siswa/SimulasiView.vue`** (lobi) — ganti seluruh data mock:
  - `onMounted`: `progress.fetchDashboard()` + `pretest.muatTingkat()` →
    kartu per tingkat dari `pretest.tingkatList` (`terbuka`, `tahap`).
  - Pilih tingkat terbuka → `simulasi.muatRuang({ tingkatId })`.
  - Kartu simulasi dari `simulasi.daftar` (nama, jumlah soal, durasi, sisa kuota).
  - Panel syarat dari `simulasi.syarat.rincian`: per materi wajib tampilkan
    centang "materi selesai" dan "nilai latihan ≥ batas", dengan tautan ke
    `/siswa/materi`. `alasan === 'belum_pretest'` → ajakan ke pre-test.
  - Tombol mulai nonaktif bila `!syarat.terpenuhi`, `sisaKuota === 0`, atau
    `!aktif`. Bila `tahap === 'SIMULASI_BERJALAN'` labelnya "Lanjutkan".
  - Kartu "hasil terakhir" dari `hasil_simulasi_terakhir` dashboard — tambah
    `id` ke `hasilTerakhir` di `mappers/dashboard.js` supaya bisa ditautkan ke
    halaman hasil.
- [ ] **`views/siswa/UjianSimulasiView.vue`** (baru) — salin kerangka
  `LatihanView.vue` (navigasi nomor, ganda/isian, ragu-ragu, autosave 800 ms):
  - `onMounted`: `simulasi.mulai({ simulasiId })`. Error → toast `pesanError`
    lalu kembali ke lobi.
  - Timer dari server, bukan hitungan lokal:

```js
const sisaDetik = ref(0)
function hitungSisa() {
  sisaDetik.value = Math.max(0, Math.round((Date.parse(simulasi.batasPada) - Date.now()) / 1000))
  if (sisaDetik.value === 0 && simulasi.status === 'mengerjakan') kumpulkan()
}
```

  - Jalankan `hitungSisa` tiap detik; reload otomatis benar karena sumbernya `batasPada`.
  - `simpanJawaban` mengembalikan `'waktu-habis'` → panggil `kumpulkan()`.
  - Selesai → `router.replace({ name: 'siswa.simulasi.hasil', params: { hasilId } })`;
    status `menilai` → blok tunggu + polling.
- [ ] **`views/siswa/HasilSimulasiView.vue`** (baru): nilai, benar/salah,
  lulus/tidak, kisi nomor benar-salah (dari `hasil.jawaban`), tombol
  "Lihat pembahasan". Data: store bila hangat, kalau tidak `lanjutkan({ id })`.
- [ ] **`views/siswa/ReviewSimulasiView.vue`** (baru): `muatReview({ id })`;
  per soal tampilkan pertanyaan, jawaban siswa, kunci, pembahasan. 409
  `SIMULASI_BELUM_DINILAI` → pesan tunggu.
- [ ] `RiwayatView.vue`: baris `jenis === 'simulasi'` ditautkan ke
  `siswa.simulasi.hasil` dengan `referensiId`.
- [ ] Setelah hasil keluar: `progress.fetchDashboard({ force: true })` (tahap
  bisa berubah jadi `LULUS` / `PUTARAN_HABIS`, tingkat berikutnya bisa terbuka)
  dan `pretest.muatTingkat({ force: true })`.

### 4.6 Gate + uji manual + PR

```bash
bunx eslint . && bun run test:unit && bun run build
```

| # | Langkah | Hasil yang benar |
| --- | --- | --- |
| 1 | Buka Simulasi sebelum syarat terpenuhi | Rincian per materi tampil, tombol mulai nonaktif |
| 2 | Penuhi syarat (selesaikan semua materi wajib + latihan ≥ batas) | `terpenuhi: true`, tombol aktif |
| 3 | Mulai simulasi | 201, timer sesuai `durasi_menit` |
| 4 | Reload di tengah ujian | Soal + jawaban kembali, timer melanjutkan (tidak reset) |
| 5 | Kumpulkan | Hasil + status lulus tampil; kuota berkurang 1 di lobi |
| 6 | Pembahasan | Kunci + pembahasan per soal tampil |
| 7 | Habiskan kuota tanpa lulus | Tahap `PUTARAN_HABIS`, dashboard mengajak pre-test ulang |

---

## Fase 5 — Pelengkap siswa

### 5.1 Pemetaan per id (hilang saat refresh)

Masalah: setelah submit sukses ID tersimpan dihapus, jadi refresh
`/siswa/pemetaan` kosong dan pemetaan lama tidak bisa dibuka lagi.

- [ ] Route: `path: 'siswa/pemetaan/:id?'`.
- [ ] `PemetaanKompetensiView.vue`, `onMounted`:
  1. `route.params.id` ada → `pretest.lanjutkan({ id })`.
  2. Tidak ada dan `pretest.hasil` kosong → ambil pre-test terakhir:
     `riwayatService.daftar({ jenis: 'pretest', perPage: 1 })` → `items[0]?.referensiId`
     → `router.replace` ke route ber-id.
  3. Status hasil `mengerjakan` → `router.replace({ name: 'siswa.pretest' })`.
- [ ] Nama materi: ganti `namaMateri = (id) => \`Materi ${id}\`` dengan lookup
  dari `useMateriStore().fetchDaftar({ tingkatId: hasil.tingkatId })` →
  `Map(id → judul)`.
- [ ] Tandai materi wajib dari `hasil.materiWajib` (label "Wajib · prioritas N").
- [ ] `RiwayatView.vue`: baris `jenis === 'pretest'` ditautkan ke
  `siswa.pemetaan` dengan `referensiId`.

### 5.2 Statistik dashboard yang masih nol

- [ ] `stores/progress.js` → `muatStatistikMateri`: tambah satu panggilan
  `riwayatService.daftar({ jenis: 'simulasi', perPage: 100 })`;
  `simulasiDiikuti = total`, `rataRataNilai = rata-rata items.nilai` (abaikan null).
- [ ] `streak`: backend tidak menyediakannya → sembunyikan elemen streak di
  sidebar/dashboard sampai ada endpoint (lihat Fase 7).

### 5.3 Halaman Progress (`/siswa/progress`, masih placeholder)

- [ ] Buat `ProgressView.vue` dari data yang sudah ada: daftar materi per
  tingkat (`materi` store) dengan status + nilai latihan terbaik, dan panel
  syarat simulasi (`simulasi.syarat`). Tidak butuh endpoint baru.

---

## Fase 6 — Admin

Backend siap (±50 endpoint, lihat `Osn-Readiness-Web/docs/API-ENDPOINTS.md`).
Kerjakan per PR kecil, urutan dari yang paling dipakai:

| PR | Isi | Endpoint |
| --- | --- | --- |
| A1 | Layout admin + dashboard ringkasan | `GET /admin/dashboard`, `GET /admin/bank-soal/kecukupan?tingkat_id=` |
| A2 | Kelola siswa (daftar paginated, ubah, nonaktifkan, hapus) | `/admin/siswa*` |
| A3 | Kompetensi + materi (termasuk unggah gambar multipart) | `/admin/kompetensi*`, `/admin/materi*` |
| A4 | Cerita soal + soal + pembahasan | `/admin/konteks-soal*`, `/admin/soal*` |
| A5 | Latihan + simulasi | `/admin/latihan*`, `/admin/simulasi*` |
| A6 | Tingkat + aturan pemetaan (16 parameter, semua wajib dikirim) | `/admin/tingkat*` |

Aturan tiap PR: endpoint di `lib/endpoints.js` → mapper → service (+spec) →
store (+spec) → view. Tangani 409 `*_MASIH_DIGUNAKAN` dan 422 per kolom lewat
`pesanField`. Paginasi dibaca dari `res.data.meta` seperti `services/riwayat.js`.

---

## Fase 7 — Rapikan repo dan keputusan terbuka

### 7.1 Rapikan

- [ ] `Osn-Readiness-Web`: commit `docs/PLAN-PERBAIKAN-AUDIT.md`,
  `docs/RINGKASAN-SESI.md`, `docs/RINGKASAN-SESI-PROFIL-CORS-HITUNG.md`.
- [ ] `Osn-Readiness-Web/docs/RINGKASAN-SESI-PROFIL-CORS-HITUNG.md`: path
  `/Volumes/PS50U/data-analytics` sudah usang, dan catatan "data-analytics
  belum di-commit" sudah tidak benar (commit `a8f7b33`).
- [ ] `Osn-Readiness-Web/README.md`: tambah langkah `php artisan storage:link`
  dan `php artisan queue:work` di bagian menjalankan lokal.
- [ ] `data-analytics`: commit `docs/alur-layanan.md`; tambah `.DS_Store` dan
  `Archive.zip` ke `.gitignore`.
- [ ] `siap-osn-fe/docs/PLAN-INTEGRASI-API-P0-P1.md`: tandai P0–P3 selesai,
  rujuk dokumen ini untuk P4+.
- [ ] Setelah Fase 3 lolos: PR `integrasi` → `dev` → `main` di FE; PR rilis
  `dev` → `main` di BE.

### 7.2 Keputusan yang perlu diambil

| # | Keputusan | Pilihan | Saran |
| --- | --- | --- | --- |
| 1 | Lupa / reset / ubah kata sandi: FE masih `setTimeout` palsu, backend tidak punya endpoint API | (a) tambah `POST /api/auth/forgot-password`, `reset-password`, `PUT /api/auth/password` di BE; (b) sembunyikan tautan dan form di FE | (b) untuk rilis ini; (a) butuh mailer |
| 2 | Layanan Python `/api/v1/*` (bank soal sendiri, paket adaptif, gerbang simulasi, leaderboard) tidak dipakai Laravel | (a) tetap hanya `/hitung/penilaian` + `/hitung/pretest`; (b) integrasikan sebagian (mis. leaderboard) | (a); kalau (b), tentukan dulu satu sumber bank soal |
| 3 | Timer pre-test 90 menit hanya di klien dan reset saat reload | (a) biarkan; (b) tambah `batas_pada` di backend seperti simulasi | (a), sudah diputuskan pemilik produk |
| 4 | Streak belajar | (a) sembunyikan; (b) endpoint baru | (a) |
| 5 | `AGENTS.md` Laravel menulis PHP 8.3, lockfile butuh ≥ 8.4 | perbarui dokumen | perbarui |

---

## Lampiran — peta bug ke langkah

| Bug | Gejala | Langkah |
| --- | --- | --- |
| venv Python basi | Semua submit 503 | 0.1 |
| `public/storage` tidak ada | Gambar 404 | 0.2 |
| CORS hanya `localhost` | `127.0.0.1:5173` diblokir | 0.3 |
| Queue worker tidak jalan | Nilai tertunda tidak pernah keluar | 0.4 |
| `quiz_id` tidak dikirim | Tombol latihan tidak muncul | 1.1, 2.7 |
| Pengerjaan selesai dibalas bersoal | Latihan selesai terbuka lagi | 1.2 |
| Pesan error bisnis hilang | Toast selalu "Terjadi kesalahan" | 2.2 |
| "Sudah disubmit, belum dinilai" tidak dikenali | Kembali ke mode mengerjakan, autosave 409 | 2.3, 2.4b, 2.5 |
| AbortController dipakai bersama | Reload pre-test/pemetaan gagal | 2.4a |
| ID tersimpan basi | Macet di "Tidak dapat membuka pre-test tersimpan" | 2.4b, 2.5 |
| Resume latihan tanpa cek quiz | Latihan materi lain yang terbuka | 2.6 |
| Reset store tidak lengkap | Data akun lama bocor | 2.8 |
| Judul riwayat tanpa tingkat, flag pre-test menempel, `/admin/ping` 404 | — | 2.9 |
| `PUTARAN_HABIS` dianggap pre-test beres | Siswa buntu, pre-test ulang tidak ditawarkan | 2.9 |
| Simulasi masih mock | — | Fase 4 |
| Pemetaan kosong saat refresh, nama materi `Materi {id}` | — | 5.1 |
| Statistik simulasi selalu 0 | — | 5.2 |
| Kartu streak selalu "Belum ada streak" | — | 5.2 |
| Halaman progress masih placeholder | — | 5.3 |
| Admin hanya tombol tes akses | 50 endpoint tak terpakai | Fase 6 |
| Siswa tidak bisa diaktifkan kembali | Hanya `deactivate`, tanpa `activate` | Fase 6 (butuh PR BE) |

---

## Status akhir (7 Oktober 2026)

| Fase | Status | Bukti |
| --- | --- | --- |
| 0 — lingkungan dev | Selesai | 4 proses hidup; 19/19 test kontrak Python |
| 1 — BE `fix/kontrak-latihan` | Selesai | PR #31 → `dev`; 448/448 PHPUnit |
| 2 — FE `fix/integrasi-stabil` | Selesai | PR #26 → `integrasi`; 92/92 unit |
| 3 — uji end-to-end | Selesai | **30/30** lewat HTTP |
| 4 — integrasi simulasi | Selesai | PR #27; **17/17** lewat HTTP |
| 5 — pelengkap siswa | Selesai | PR #28; **11/11** lewat HTTP |
| 6 — admin A1–A6 | Selesai | PR #31 FE; **26/26** lewat HTTP |
| 7 — rapikan repo | Selesai | 3 PR docs; 5 keputusan di bawah |

Uji end-to-end berjalan lewat HTTP, bukan klik browser, karena hanya
terminal yang tersedia. Yang **belum** teruji: render Vue, transisi
halaman (`router.replace`), polling di layar "sedang dinilai", dan
interaksi form admin. Semuanya perlu dicek langsung di `http://localhost:5173`.

### PR yang sudah merge

| Repo | PR | Isi |
| --- | --- | --- |
| BE | #31 | `quiz_id` + balas hasil pada `quiz-pengerjaan` |
| FE | #26 | stabilkan pre-test, latihan, materi, dashboard |
| FE | #27 | integrasi penuh simulasi (P4) |
| FE | #28 | pemetaan per id, statistik, halaman progress |
| FE | #29 | `integrasi` → `dev` |
| FE | #30 | `dev` → `main` |
| FE | #31 | 6 modul admin |
| FE | #32 | tandai P0–P4 selesai |
| BE | #32 | `dev` → `main` (merge 82 commit, konflik diselesaikan ke `dev`) |
| data-analytics | #1 | `alur-layanan.md` |

PR #24 (FE, `feat/simulasi-view` → `main`) ditutup: cabangnya sudah
pernah merge ke `dev` lewat #19, dan meng-mergex ke `main` akan
menurunkan `main` ke versi lama halaman simulasi.

### Keputusan yang perlu diambil

| # | Keputusan | Pilihan | Saran | Status |
| --- | --- | --- | --- | --- |
| 1 | Lupa / reset / ubah kata sandi masih `setTimeout` palsu | (a) tambah 3 endpoint di BE; (b) sembunyikan di FE | (b) untuk rilis ini | **Belum diputuskan** |
| 2 | Jalur Python `/api/v1/*` tidak dipakai Laravel | (a) tetap hanya `/hitung/*`; (b) integrasikan sebagian | (a) | **Diterapkan** — sudah jadi keputusan de facto, tinggal dikonfirmasi |
| 3 | Timer pre-test 90 menit hanya di klien, reset saat reload | (a) biarkan; (b) tambah `batas_pada` di BE | (a) | **Diterapkan** — tidak diubah |
| 4 | Streak belajar | (a) sembunyikan; (b) endpoint baru | (a) | **Diterapkan** — kartu dihapus di 5.2 |
| 5 | `AGENTS.md` menulis PHP 8.3, lockfile butuh ≥ 8.4 | perbarui dokumen | perbarui | **Belum dikerjakan** |

Lima keputusan di atas sudah tercatat; tiga sudah diterapkan di kode,
satu jadi de facto (nomor 2), satu belum (nomor 5).

### Tambahan yang ditemukan saat pengerjaan

1. **Siswa tidak bisa diaktifkan kembali.** `POST /admin/siswa/{id}/deactivate`
   ada, `activate` tidak. Tombol "Nonaktifkan" di FE jadi jalan satu arah.
   Perlu endpoint baru kalau ini tidak diinginkan.
2. **`pembahasan` bank soal dev kosong** (0 dari 30 soal). Bukan bug FE —
   layar Pembahasan sudah menampilkan kondisi kosong. Admin perlu mengisi
   lewat halaman Soal & Pembahasan.
3. **`AuthService` di `main` sempat kehilangan `updateProfile`** saat percobaan
   merge pertama (artefak arah merge). Teratasi setelah `dev` dipilih sebagai
   pihak yang menang — CI PR #32 yang 448/448 lulus adalah buktinya.
4. **FE `main` tertinggal 56 commit** sebelum PR #30. `PasswordForm` di
   `src/components/auth/` sudah ikut ke `main` setelah merge itu.
