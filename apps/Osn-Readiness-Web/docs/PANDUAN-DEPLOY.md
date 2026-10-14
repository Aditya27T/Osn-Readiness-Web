# Panduan Deploy

Panduan untuk deploy dua service backend: **Laravel API** (`Osn-Readiness-Web`) dan
**layanan hitung Python** (`data-analytics`). Ditulis supaya DevOps bisa setup dari
nol tanpa perlu membaca kode.

Untuk panduan menjalankan **test** lokal, lihat
[`MENJALANKAN-TESTING.md`](../Siap-osn-Fe/docs/MENJALANKAN-TESTING.md) dan
[`LAPORAN-PINGGIRAN-TEST.md`](../Siap-osn-Fe/docs/LAPORAN-PINGGIRAN-TEST.md).

---

## 1. Arsitektur

```
                 Browser / FE (Vite)
                        |
                        v
        +-------------------------------+
        |  Laravel API  (Sail, :8000)   |
        |  PHP 8.5 + PostgreSQL 16      |
        +-------------------------------+
                        |
                        |  POST /hitung/*  (shared secret)
                        v
        +-------------------------------+
        |  Layanan hitung Python        |
        |  FastAPI / uvicorn  (:8001)   |
        +-------------------------------+
```

Kedua service wajib punya **shared secret yang sama**:

| Service | Variabel |
| --- | --- |
| Laravel | `PERHITUNGAN_TOKEN` |
| Python | `INTERNAL_API_TOKEN` |

Kalau berbeda: Python membalas `403`, Laravel membalas `502
LAYANAN_HITUNG_SALAH_KONFIGURASI`, dan semua submit pre-test/simulasi berakhir `503`.

---

## 2. Prasyarat

| Kebutuhan | Versi | Catatan |
| --- | --- | --- |
| Docker Desktop / Engine | 20.10+ | Wajib untuk Laravel via Sail |
| Node.js | 20+ | hanya untuk build frontend |
| PHP | **8.4.1+** | lihat peringatan di §3 |
| Composer | 2.x | hanya di dalam container |
| Python | 3.11+ | untuk layanan hitung |

---

## 3. PERINGATAN: constraint PHP belum sinkron

`composer.json` mendeklarasikan `require.php: ^8.3`, tetapi `composer.lock`
mengunci `symfony/*` ke `v8.1.x` yang mensyaratkan **`php >= 8.4.1`**.

Akibatnya `composer install` **gagal** di mesin mana pun selama lockfile tidak
diperbarui. Pilih salah satu sebelum deploy:

**Opsi A (disarankan) -- seimbangkan constraint dengan lockfile:**

```bash
# di dalam container
docker compose run --rm laravel.test composer require --no-update php:^8.4
docker compose run --rm laravel.test composer update --lock
```

Lalu commit `composer.json` dan `composer.lock` yang baru.

**Opsi B -- jangan sentuh lockfile, pakai `--ignore-platform-req`:**

```bash
docker compose run --rm laravel.test \
  composer install --no-interaction --no-scripts --ignore-platform-req=php
```

Opsi B lebih cepat tapi flagged di `docs/LAPORAN-PINGGIRAN-TEST.md` §2 sebagai
masalah yang akan menimpa siapa pun yang setup ulang. Sebaiknya Opsi A.

---

## 4. Environment variables

### 4.1 Laravel (`Osn-Readiness-Web/.env`)

Salin dari `.env.example`, lalu isi. **WAJIB** berarti deployment gagal atau
fitur rusak kalau kosong.

| Variabel | Wajib | Isi | Keterangan |
| --- | --- | --- | --- |
| `APP_ENV` | ya | `production` | menentukan perilaku seeding, lihat §7 |
| `APP_KEY` | ya | hasil `key:generate` | jangan pernah commit |
| `APP_DEBUG` | ya | `false` | `true` di production membuat bocor stack trace |
| `APP_URL` | ya | `https://api.domain.tld` | |
| `FRONTEND_URL` | ya | `https://app.domain.tld,https://www.domain.tld` | **daftar dipisah koma**, untuk CORS |
| `DB_CONNECTION` | ya | `pgsql` | PostgreSQL hanya, tidak ada MySQL/SQLite |
| `DB_HOST` | ya | `pgsql` | hostname service di network Docker |
| `DB_PORT` | ya | `5432` | |
| `DB_DATABASE` | ya | `osn_readiness` | |
| `DB_USERNAME` | ya | | |
| `DB_PASSWORD` | ya | | |
| `PERHITUNGAN_URL` | ya | lihat §5 | cara menuju layanan Python |
| `PERHITUNGAN_TOKEN` | ya | **sama dengan** `INTERNAL_API_TOKEN` Python | |
| `PERHITUNGAN_TIMEOUT` | tidak | `5` | detik |
| `PERHITUNGAN_RETRY` | tidak | `2` | |
| `SUPER_ADMIN_EMAIL` | ya | email admin nyata | dipakai `SuperAdminSeeder` |
| `SUPER_ADMIN_PASSWORD` | ya | password kuat | **jangan** pakai `password` |
| `CACHE_STORE` | tidak | `database` | |
| `QUEUE_CONNECTION` | tidak | `database` | wajib ada worker, lihat §6 |
| `SESSION_DRIVER` | tidak | `cookie` | |
| `MAIL_MAILER` | tidak | `smtp` | untuk email verifikasi |
| `RETENSI_AKUN_HARI` | tidak | `30` | masa retensi akun dihapus |

### 4.2 Python (`data-analytics/.env`)

| Variabel | Wajib | Isi | Keterangan |
| --- | --- | --- | --- |
| `INTERNAL_API_TOKEN` | ya | **sama dengan** `PERHITUNGAN_TOKEN` Laravel | shared secret |
| `POSTGRES_USER` | tidak | | hanya untuk ingest/migrasi |
| `POSTGRES_PASSWORD` | tidak | | |
| `POSTGRES_DB` | tidak | | |
| `DATABASE_URL` | tidak | | dipakai di luar Docker |
| `DATA_RETENTION_MONTHS` | tidak | `24` | periode retensi data |

---

## 5. Cara Laravel menjangkau layanan Python

Nilai `PERHITUNGAN_URL` berbeda tergantung penempatan service:

| Skenario | Nilai |
| --- | --- |
| Python ikut di network Docker yang sama | `http://nama-service:8001` |
| Python jalan di host, Laravel di container | `http://host.docker.internal:8001` |
| Keduanya jalan di host | `http://127.0.0.1:8001` |

**Jangan pakai `localhost` saat Laravel jalan di container.** `localhost` di dalam
container berarti container itu sendiri, sehingga Python tidak akan pernah
ditemukan dan semua submit berakhir `503`.

`compose.yaml` sudah mendeklarasikan `extra_hosts: host.docker.internal:host-gateway`,
jadi skenario kedua langsung bisa dipakai tanpa konfigurasi tambahan.

---

## 6. Deploy Laravel

### 6.1 Build dan jalankan

```bash
cd Osn-Readiness-Web

cp .env.example .env
# isi .env sesuai tabel di bagian 4.1

# build image + build frontend
docker compose build
docker compose up -d

# generate app key (hanya sekali)
docker compose exec laravel.test php artisan key:generate
```

### 6.2 Migrasi dan seeding

```bash
docker compose exec laravel.test php artisan migrate --force
```

Bila butuh mengisi data awal (role, permission, tingkat seleksi, aturan pemetaan,
akun Super Admin):

```bash
docker compose exec laravel.test php artisan db:seed --force
```

### 6.3 Queue worker (WAJIB)

Tanpa worker, `NilaiUlangJob` tidak pernah jalan dan hasil penilaian bisa
menghang di status "Sedang Dinilai".

```bash
docker compose exec -d laravel.test php artisan queue:work --tries=3
```

Di environment bercontainer, worker sebaiknya dijalankan sebagai proses
terpisah (service komposes atau systemd), bukan `exec -d` interaktif.

### 6.4 Frontend Laravel

Layout Blade memakai Vite, jadi `public/build/manifest.json` wajib ada:

```bash
cd Osn-Readiness-Web
npm ci
npm run build
```

Kalau file ini tidak ada, halaman akan gagal dengan
`ViteManifestNotFoundException` dan test `tests/Feature/Auth/*` ikut gagal.

### 6.5 Port

Default `compose.yaml` mem-publish `APP_PORT` (default 80). Set di `.env`:

| Variabel | Default | Guna |
| --- | --- | --- |
| `APP_PORT` | `80` | port host untuk Laravel |
| `FORWARD_DB_PORT` | `5432` | port host untuk PostgreSQL |

---

## 7. Perilaku seeder di tiap environment

`DatabaseSeeder` memanggil seeder berbeda tergantung `APP_ENV`:

| Seeder | `production` | `local` / `testing` |
| --- | --- | --- |
| `RolesAndPermissionsSeeder` | ya | ya |
| `TingkatSeleksiSeeder` | ya | ya |
| `AturanPemetaanSeeder` | ya | ya |
| `SuperAdminSeeder` | ya | ya |
| `SiswaContohSeeder` | **tidak** | ya |
| `KontenContohSeeder` | **tidak** | ya |

Dua seeder terakhir **sengaja** tidak jalan di production. Alasannya:

- `SiswaContohSeeder` membuat akun `siswa@example.com` dengan password `password`
  yang diketahui publik. Akun ini juga dipakai `e2e/helpers.js` sebagai `AKUN_SISWA`.
  Kalau ikut tercipta di production setiap kali `migrate --seed` dijalankan, ada
  akun dengan password yang sudah ada di repositori publik.
- `KontenContohSeeder` membuat soal dan materi contoh.

`SuperAdminSeeder` **harus** jalan di production, jadi `SUPER_ADMIN_EMAIL` dan
`SUPER_ADMIN_PASSWORD` wajib diisi dengan nilai nyata sebelum menjalankan
`db:seed`.

---

## 8. Deploy layanan hitung Python

### 8.1 Lokal / single host

```bash
cd data-analytics

cp .env.example .env
# isi INTERNAL_API_TOKEN dengan nilai yang sama dengan PERHITUNGAN_TOKEN Laravel

uv sync
uv run uvicorn data_analytics.api:app --host 0.0.0.0 --port 8001
```

**Pakai `0.0.0.0`, bukan `127.0.0.1`**, kalau service diakses dari luar host
(mis. dari container Laravel). Bind ke loopback tidak bisa dijangkau dari
container lain.

### 8.2 Migrasi database (opsional, hanya untuk ingest)

Endpoint `/hitung/*` adalah perhitungan murni dan **tidak butuh database**.
Database hanya dibutuhkan untuk proses ingest dan pembaruan bank soal.

```bash
cd data-analytics
uv run alembic upgrade head
```

### 8.3 Endpoint

| Method | Path | Keterangan |
| --- | --- | --- |
| `GET` | `/health` | health check, tanpa token |
| `POST` | `/hitung/penilaian` | penilaian soal |
| `POST` | `/hitung/pretest` | penilaian + pemetaan kompetensi |
| `POST` | `/hitung/simulasi` | penilaian simulasi |
| `POST` | `/hitung/latihan` | penilaian latihan |

Semua `/hitung/*` butuh header `X-Internal-Token` yang nilainya sama dengan
`INTERNAL_API_TOKEN`.

---

## 9. Health check setelah deploy

Jalankan berurutan. Semua harus lolos sebelum dianggap siap.

```bash
# 1. Laravel API -- harus 200
curl -s -o /dev/null -w '%{http_code}\n' \
  -X POST https://api.domain.tld/api/auth/login \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"email":"<email-admin>","password":"<password-admin>"}'

# 2. Layanan hitung Python -- tanpa token harus 403 (bukan 000)
curl -s -o /dev/null -w '%{http_code}\n' \
  -X POST http://127.0.0.1:8001/hitung/penilaian \
  -H 'Content-Type: application/json' -d '{"soal":[]}'

# 3. Dengan token -- harus 200 dan nilai 100
curl -s -X POST http://127.0.0.1:8001/hitung/penilaian \
  -H "X-Internal-Token: $INTERNAL_API_TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{"soal":[{"soal_id":1,"tipe_soal":"pilihan_ganda","bobot":1,"jawaban_user":"A","kunci_jawaban":"A"}]}'
```

Penjelasan kode yang diharapkan:

| Kode | Arti |
| --- | --- |
| `000` | gagal konek -- service mati atau URL salah |
| `403` | proses hidup, token belum dikirim (ini yang diharapkan di langkah 2) |
| `200` | berhasil |
| `502` / `503` | Laravel tidak bisa menjangkau Python |

---

## 10. Checklist pasca-deploy

- [ ] `php artisan migrate --force` selesai tanpa error
- [ ] Akun Super Admin dibuat dengan email dan password nyata
- [ ] Tidak ada akun `siswa@example.com` (seeder contoh tidak jalan di production)
- [ ] Queue worker berjalan dan bertahan
- [ ] `PERHITUNGAN_TOKEN` == `INTERNAL_API_TOKEN`
- [ ] `PERHITUNGAN_URL` bukan `localhost` saat Laravel di container
- [ ] `APP_DEBUG=false`
- [ ] Health check di bagian 9 lolos semua
- [ ] `public/build/manifest.json` ada
- [ ] Frontend memakai `VITE_API_BASE_URL` yang menunjuk API production

---

## 11. Jangan lupa: `validate_timestamps` dan OPcache

`docker-compose.override.example.yml` mengaktifkan OPcache untuk CLI, yang
mengaktifkan OPcache untuk CLI, yang memperbaiki performa di bind-mount Windows
(rata-rata 235 ms dibanding 1153 ms). Berkas `docker-compose.override.yml` sudah masuk
`.gitignore` karena berisi setelan lokal.

Untuk mengaktifkannya di server:

```bash
cp docker-compose.override.example.yml docker-compose.override.yml
```

**Tapi hati-hati:** konfigurasi itu memakai `opcache.validate_timestamps=0`, yang
berarti **perubahan file PHP/Blade tidak terlihat sampai container di-restart**.
Untuk server yang lebihBrowsing baik, ubah di `docker/php-cli-opcache.ini`:

```ini
opcache.validate_timestamps=1
opcache.revalidate_freq=60
```

Server Linux dengan filesystem asli tidak membutuhkan override ini sama sekali --
OPcache sudah aktif secara bawaan untuk FPM.
