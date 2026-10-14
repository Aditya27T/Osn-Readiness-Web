# Ringkasan Perubahan — 11 Oktober 2026

Catatan apa yang berubah di repo `Osn-Readiness-Web` (backend Laravel) pada
sesi 11 Oktober 2026, Includes code yang ter-commit maupun konfigurasi lokal
yang sengaja tidak ter-commit.

---

## 1. Yang ter-commit

### `624b24a` — merge(konten): impor bank soal kabupaten ke siap-integrasi

Menggabungkan `b0b0a7b` yang sebelumnya hanya ada di branch lokal
`backup/bank-soal-kabupaten`. Branch itu tidak pernah di-push dan tidak masuk
branch mana pun, jadiVKonten Kabupaten utuh hilang kalau tidak digabung.

Isi yang masuk:

| Berkas | Perubahan |
| --- | --- |
| `app/Console/Commands/ImporKontenCommand.php` | Tambah opsi `--tingkat` |
| `app/Services/ImporKontenService.php` | Saring per tingkat, bersihkan artefak `[cite: N]` |
| `app/Services/ImporKontenServiceInterface.php` | Ikut tanggalkan parameter tingkat |
| `app/Support/KuotaLevel.php` | Level berpersen 0 tidak lagi ikut menerima sisa soal |
| `config/osn.php` | Path folder impor dari env, tanpa path absolut |
| `database/seeders/KontenKabupatenSeeder.php` | **Baru** — importer bank soal nyata |
| `database/seeders/KontenContohSeeder.php` | **Dihapus** — digantikan seeder di atas |
| `tests/Unit/Support/KuotaLevelTest.php` | **Baru** — 2 kasus untuk perbaikan kuota |

Perbaikan `KuotaLevel` itu bukan kosmetik. Bank soal kabupaten tidak punya satu
pun soal pre-test berlevel `sedang`. `KuotaLevel::hitung` lama membagikan sisa
soal ke semua level termasuk yang dinolkan, sehingga pre-test selalu meminta
soal ke level kosong dan gagal mulai dengan `BankSoalTidakCukupException`.

### Resolusi konflik merge

`database/seeders/DatabaseSeeder.php` konflik antara dua sisi. Dipilih
**mempertahankan `SiswaContohSeeder`** dan menambahkan pemanggil
`KontenKabupatenSeeder`, bukan mengambil sisi `backup/`.

Alasannya: `backup/bank-soal-kabupaten` ikut menghapus
`database/seeders/SiswaContohSeeder.php`. Akun yang dibuatnya
(`siswa@example.com` / `password`) di-hardcode di `e2e/helpers.js` sebagai
`AKUN_SISWA`, jadi menghapusnya membuat suite e2e frontend gagal. Blok
`local`/`testing` beserta peringatannya juga dipertahankan.

Berkas `docs/PANDUAN-DEPLOY.md` dan `docker/php-cli-opcache.ini` tidak
tersentuh — keduanya hanya ada di `siap-integrasi`.

---

## 2. Hasil impor bank soal

```bash
./vendor/bin/sail artisan impor:konten ../data-analytics/soal/impor --dry-run --tingkat=kabupaten
./vendor/bin/sail artisan impor:konten ../data-analytics/soal/impor --tingkat=kabupaten
```

Hasil keduanya sama:

```
Materi: 5 masuk, 0 diperbarui.
Soal: 922 masuk, 0 diperbarui, 0 ditolak, 0 dilewati.
```

`0 ditolak` adalah syarat yang diminta `data-analytics/docs/impor-bank-soal-laravel.md`.

### Peringatan: bank soal sekarang dobel

Tabel `soal` berisi **1.800 baris** tingkat Kabupaten/Kota, bukan 922:

| Asal | Jumlah | Pola `id_sumber` |
| --- | --- | --- |
| Impor lama (folder `soal_osn/impor/`) | 878 | `kab-2012-036` |
| Impor baru (folder `soal/impor/`) | 922 | `kab_soal*` |

Keduanya tidak terlihat sebagai duplikat karena `id_sumber` berbeda, tapi
**678 baris punya teks `pertanyaan` yang identik**. Cho므로 ini belum
dibereskan. Selama 878 baris lama masih ada, satu soal bisa muncul dua kali
saat pre-test.

Sebaiknya 878 baris lama dihapus sebelum mengimpor tingkat Provinsi.

---

## 3. Konfigurasi lokal (tidak ter-commit)

Semua berkas di bagian ini masuk `.gitignore` dan hanya berlaku di mesin ini.

### `.env`

| Kunci | Nilai | Alasan |
| --- | --- | --- |
| `WWWUSER` | `501` | compose.yaml memakainya; kosong sebelumnya jadi warning |
| `WWWGROUP` | `20` | idem |
| `DB_HOST` | `pgsql` | bukan `127.0.0.1` — di dalam container itu dirinya sendiri |
| `PERHITUNGAN_URL` | `http://analytics-api:8000` | DNS internal Docker, bukan `localhost` |

`DB_HOST` diubah ke `pgsql` karena backend dipindah ke Docker. Konsekuensinya
`php artisan serve` dan `./vendor/bin/phpunit` **nativе gagal** — selalu pakai
`./vendor/bin/sail artisan ...`.

### `docker-compose.override.yml`

Dibuat dari `docker-compose.override.example.yml` lalu ditambah dua hal.

1. **Mount `data-analytics`.** Perintah `impor:konten` diberi path relatif
   `../data-analytics/soal/impor`. Tanpa mount, itu resolve ke
   `/var/www/data-analytics` yang tidak ada di dalam container.

   ```yaml
   - '../data-analytics:/var/www/data-analytics:ro'
   ```

2. **Buang mapping port 5173.** `compose.yaml` memetakan
   `${VITE_PORT:-5173}:${VITE_PORT:-5173}` ke `laravel.test`, padahal Sail tidak
   menjalankan Vite di dalam container. Container itu merebut port 5173 di host
   dan membuat Vite gagal start dengan
   `Port 5173 is in use on a wildcard address`.

   ```yaml
   ports: !override
     - '${APP_PORT:-8000}:80'
   ```

   Tag `!override` wajib: Compose v5 **meny_append** daftar `ports`, bukan
   menggantinya, jadi mapping asli ikut terbawa tanpa tag tersebut.

3. **Gabung ke `app-network`** supaya bisa menjangkau `analytics-api`.

Setiap selesai mengedit override, container perlu direcreate:

```bash
docker compose up -d --force-recreate laravel.test
```

`sail up -d` saja **tidak** mendeteksi perubahan konfigurasi.

### Data lokal

Tabel `sessions` di `osn_readiness` pernah dipakai. Aman dihapus kapan saja.

---

## 4. Akun yang dibuat

| Email | Role | Password | Sumber |
| --- | --- | --- | --- |
| `siswa@example.com` | siswa | `password` | `SiswaContohSeeder` |
| `admin@example.com` | Super Admin | `password` | `SuperAdminSeeder` |
| `siswa@osn.test` | siswa | tidak diketahui | sudah ada sebelumnya |

`SiswaContohSeeder` dijalankan khusus untuk mengisi akun siswa. Sengaja **tidak**
menjalankan `db:seed` penuh karena itu akan memicu `KontenKabupatenSeeder`
dan mengimpor ulang 922 soal.

---

## 5. Verifikasi

| Yang dicek | Hasil |
| --- | --- |
| `./vendor/bin/sail artisan test` | **450/450 lulus**, 1.714 assertion |
| `./vendor/bin/pint --test` | lulus |
| `GET http://localhost:8000/` | 200 |
| `POST /api/auth/login` | 200, token terbit |
| Preflight CORS `OPTIONS` dari origin FE | 204, header benar |
| `PerhitunganClientInterface` → `analytics-api` | `nilai: 100`, 3 jawaban benar |

Jumlah test naik dari 448 ke 450 karena `KuotaLevelTest` dari merge.

---

## 6. Belum dikerjakan

- **678 soal dobel** belum dibereskan (bagian 2).
- **Tingkat Provinsi** belum diimpor; filter `--tingkat=provinsi` sudah siap.
- **`activate` siswa** belum ada, hanya `deactivate` (`routes/api.php:101`).
- **Keputusan #1** dalam `PLAN-INTEGRASI-LANJUTAN.md` masih menggantung:
  `ForgotPasswordForm.vue` dan `ResetPasswordForm.vue` masih
  `setTimeout` palsu.
- **`AGENTS.md:7`** masih menulis PHP 8.3 sementara `README.md:16` sudah
  diperbarui ke 8.4.1+.

---

*Dokumen ini ditulis pada 11 Oktober 2026. Perubahan `.env` dan
`docker-compose.override.yml` tidak ikut ter-commit karena keduanya
di-gitignore; jejaknya ada di bagian 3.*