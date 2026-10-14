# Audit Kemajuan — Siap OSN (BE + FE + Layanan Hitung)

Tanggal audit: **11 Oktober 2026**
Ruang lingkup: `Osn-Readiness-Web` (BE), `siap-osn-fe` (FE), `data-analytics` (DA)
Acuan: [`PLAN-INTEGRASI-LANJUTAN.md`](PLAN-INTEGRASI-LANJUTAN.md), status tertulis terakhir 7 Oktober 2026.

---

## 1. Ringkasan eksekutif

Semua **fase 0–7 dalam plan integrator benar-benar selesai** dan klaim di
dokumen terbukti saat ini — diverifikasi ulang lewat eksekusi test, bukan
hanya membaca dokumen. Kualitas kode juga bersih di tiga repo (lint tanpa
temuan).

Yang **belum** selesai bukan fase plan, melainkan pekerjaan yang lahir
**setelah** 7 Oktober: pergantian bank soal ke data OSN nyata. Itu pekerjaan
paling besar di repo dan justru berada di kondisi paling rapuh:

| # | Temuan | Severity |
| --- | --- | --- |
| F1 | Commit impor bank soal kabupaten ada di branch lokal `backup/`, tidak di-push dan tidak di-merge ke mana pun | **P0** |
| F2 | Folder impor 1.612 soal + skrip pembuatnya di `data-analytics` **belum di-commit**; backend menunjuk ke sana lewat env | **P0** |
| F3 | FE `dev` dan `main` bercabang, tidak ada yang superset satu sama lain | **P1** |
| F4 | `d89d987` (durasi 60 menit, coming soon provinsi) hanya ada di `dev` | **P1** |
| F5 | Perubahan lokal FE belum di-commit (`PretestView.vue`, +9 baris) | **P2** |
| F6 | 734 soal provinsi sudah disiapkan tapi belum diimpor ke backend | **P2** |
| F7 | Keputusan #1 masih terbuka: lupa & reset password masih `setTimeout` palsu | **P2** |
| F8 | Siswa tidak bisa diaktifkan kembali (hanya `deactivate`) | **P2** |
| F9–F12 | Angka draft skema tidak sinkron, `peruntukan` direkayasa, file debug & `a.txt` | **P3** |

---

## 2. Bukti eksekusi test

Semua dijalankan ulang hari ini, bukan diambil dari catatan lama.

| Repo | Perintah | Hasil |
| --- | --- | --- |
| BE | `./vendor/bin/phpunit --no-coverage` | **448/448 lulus**, 1.712 assertion, 147 detik |
| BE | `./vendor/bin/pint --test` | **passed** |
| FE | `npx vitest run` | **169/169 lulus**, 22 file |
| FE | `npx oxlint .` + `npx eslint .` | **bersih, tanpa temuan** |
| DA | `.venv/bin/python -m pytest -q` | **109/109 lulus** |

Catatan: test BE **tidak bisa jalan tanpa Postgres**. Postgres hanya hidup lewat
Docker. Di awal sesi ini `docker compose up -d pgsql` dijalankan ulang karena
container belum ada — seluruh suite gagal dengan `Connection refused` sampai
container sehat. Ini urutan wajib untuk siapa pun yang mau menjalankan test BE.

Jumlah test FE naik dari 92 (angka di plan) ke 169, dan DA naik dari 19 ke 109.
— kedua sisi tumbuh, tidak ada test yang hilang.

---

## 3. Status fase plan (0–7)

Semua diverifikasi terhadap kode nyata, bukan hanya mengikuti dokumen.

| Fase | Status plan | Verifikasi hari ini | Catatan |
| --- | --- | --- | --- |
| 0 — lingkungan dev | Selesai | **Terverifikasi** | Perlu `docker compose up -d pgsql` dulu (lihat §2) |
| 1 — BE `fix/kontrak-latihan` | Selesai | **Terverifikasi** | `LatihanPengerjaanResource.php:39` dan `HasilLatihanResource.php:24` sudah mengirim `quiz_id` |
| 2 — FE `fix/integrasi-stabil` | Selesai | **Terverifikasi** | 26 view aktif, semua ada |
| 3 — uji end-to-end | Selesai | **Tidak diulang** | Butuh 4 proses hidup; hanya DB yang dinyalakan hari ini |
| 4 — integrasi simulasi | Selesai | **Terverifikasi** | `SimulasiView.vue` + `UjianSimulasiView.vue` + `ReviewSimulasiView.vue` ada |
| 5 — pelengkap siswa | Selesai | **Terverifikasi** | `ProgressView.vue`, `PemetaanKompetensiView.vue`, mapper statistik ada |
| 6 — admin A1–A6 | Selesai | **Terverifikasi** | 6 view admin lengkap di `src/views/admin/` |
| 7 — rapikan repo | Selesai | **Sebagian** | Lihat F3/F4/F5 — kondisi repo justru memburuk setelah 7 Okt |

Modul admin yang diklaim selesai di `main` (PR #26–#37) **juga ada di `dev`**,
jadi isi fiturnya tidak hilang di mana pun. Yang hilang di `dev` adalah lapisan
pengujian, bukan fiturnya.

---

## 4. Status lima keputusan terbuka

| # | Keputusan | Status di plan | Status nyata hari ini |
| --- | --- | --- | --- |
| 1 | Lupa / reset / ubah kata sandi masih `setTimeout` palsu | Belum diputuskan | **Masih terbuka.** `ForgotPasswordForm.vue:41` dan `ResetPasswordForm.vue:53` masih `setTimeout(resolve, 500)` |
| 2 | Jalur Python `/api/v1/*` tidak dipakai Laravel | Diterapkan | **Berlaku.** Tidak ada perubahan |
| 3 | Timer pre-test hanya di klien | Diterapkan (90 mnt) | **Berlaku, tapi angkanya berubah** jadi 60 menit di `src/lib/pretest.js:8` oleh `d89d987` — plan belum diperbarui |
| 4 | Streak belajar disembunyikan | Diterapkan | **Berlaku.** Kartu disembunyikan di `AppLayout.vue:80` |
| 5 | `AGENTS.md` bilang PHP 8.3, lockfile butuh ≥ 8.4 | Belum dikerjakan | **Setengah jalan.** `README.md:16` sudah menulis PHP 8.4.1+ dengan catatan, tapi `AGENTS.md:7` masih "PHP 8.3" |

---

## 5. Temuan detail

### F1 — Commit impor bank soal kabupaten tidak tersimpan di mana pun (P0)

```
b0b0a7b  feat(konten): impor bank soal kabupaten 878 soal dari data-analytics
```

- Hanya ada di branch lokal **`backup/bank-soal-kabupaten`**.
- Tidak ada di `origin` — `git branch -r` tidak memuatnya.
- Tidak masuk ke `siap-integrasi` (branch aktif), `dev`, maupun `main`.
  `git merge-base --is-ancestor b0b0a7b siap-integrasi` → **NO**.
- Isinya bukan perubahan kecil: 9 file, termasuk **menghapus**
  `database/seeders/KontenContohSeeder.php` (200 baris) dan menggantinya dengan
  `KontenKabupatenSeeder`.

Branch aktif masih memakai `KontenContohSeeder` (soal dari factory, bukan data
nyata). Artinya **isi database lokal saat ini bukan bank soal OSN asli** —
pemeriksaan manual pre-test/latihan yang dilakukan beberapa hari terakhir memakai soal contoh.

Commit-nya sendiri sudah, di balik itu ada bug nyata yang diperbaiki: bank kabupaten
tidak punya soal pre-test berlevel `sedang`, sehingga `KuotaLevel::hitung`
selalu meminta soal ke level yang kosong dan pre-test gagal mulai. Fix-nya
benar (level berpersen 0 tidak lagi ikut menerima sisa soal).

**Tindakan:** `git push origin backup/bank-soal-kabupaten`, lalu PR ke `dev`.
Jangan rename ke `feat/...` dulu — nama `backup/` menyesatkan, isinya fitur
produksi, bukan cadangan.

### F2 — Sumber data impor tidak ter-commit (P0)

`data-analytics` punya tiga path **`??` (untracked)**:

| Path | Isi | Ukuran |
| --- | --- | --- |
| `scripts/susun_folder_impor.py` | Skrip penjembatan format | 10 KB |
| `soal_osn/impor/` | 40 file JSON + `materi/` + `gambar/` + `laporan.json` | 3 MB |
| `docs/skema-pemisahan-bank-soal.md` | Draft skema normalisasi | 10 KB |

Rantainya rapuh di dua arah:

```
data-analytics/soal_osn/impor/   ← untracked, tidak ikut clone
            ↑ KONTEN_KABUPATEN_FOLDER=/Volumes/PS50U/bigdata/data-analytics/soal_osn/impor
            │ .env lokal BE (sudah diisi, baris 77)
Osn-Readiness-Web/database/seeders/KontenKabupatenSeeder.php
```

Konsekuensi:

1. **Clone baru `data-analytics` tidak punya folder impor.** Clone baru BE tidak
   punya `KontenKabupatenSeeder` (F1). Siapa pun yang mulai dari nol tidak bisa
   menjalankan seeder bank soal tanpa dua hal yang tidak ada di git.
2. `KONTEN_KABUPATEN_FOLDER` kosong → seeder **dilewati dengan peringatan**,
   bukan error. Kegagalan total terlihat seperti sukses.
3. Jalur env menunjuk ke direktori lokal (`/Volumes/PS50U/bigdata/...`).
   Tidak portabel antar mesin.

`.gitignore` `data-analytics` tidak mengecualikan `soal_osn/impor/`, jadi
file ini **bisa** di-commit tanpa ubah gitignore. Folder sumber
`soal_osn/soal_kabupaten_pembahasan/` sendiri **sudah** ter-commit (284 file
ter-track), jadi tidak ada alasan menyimpan hasil turunannya sebagai untracked.

**Tindakan:** commit `scripts/susun_folder_impor.py` dan `soal_osn/impor/`
(pertimbangkan Git LFS kalau ukuran jadi masalah).

### F3 — FE `dev` dan `main` bercabang, tidak ada yang superset (P1)

Titik divergensi: `992521c`. `dev` punya 3 commit unik, `main` punya 25 commit
unik. Terbagi:

| Milik `main`, tidak ada di `dev` | Milik `dev`, tidak ada di `main` |
| --- | --- |
| `e2e/auth.spec.js`, `e2e/helpers.js`, `e2e/global-setup.js` | `src/lib/pretest.js` |
| `playwright.config.js` | `src/lib/soal.js` + `soal.spec.js` |
| `docs/MENJALANKAN-TESTING.md` (413 baris) | `src/lib/tingkat.js` + `tingkat.spec.js` |
| `vercel.json`, konfigurasi `vite`/`vitest` | `PretestView.vue` versi refactor (524 baris) |
| | `UjianSimulasiView.vue` versi refactor |

Yang paling berbahaya: **`src/lib/pretest.js` tidak ada di `main`**. Di `main`,
durasi pre-test masih magic number di dalam komponen, persis masalah yang
sudah sengaja dipecahkan di `dev` — dan ada spec test-nya (`pretest.js` +
spec) yang hilang kalau `dev` yang dipilih menang.

Sebaliknya kalau `main` yang menang, suite Playwright untuk alur autentikasi
ikut hilang. Dua suite pengujian berbeda, tidak tumpang tindih.

**Tindakan:** tentukan satu garis. Rekomendasi: merge `dev` → `main`, karena
`dev` punya unit test untuk logika yang baru dipecah dan `main` punya e2e yang
tidak menabrakkan file sama sekali. Tapi **periksa konflik `vite.config.js` dan
`vitest.config.js` secara manual** — keduanya berubah di kedua sisi.

### F4 — Commit terbaru FE belum menyentuh `main` (P1)

```
d89d987  feat(siswa): seragamkan tampilan soal, coming soon provinsi, durasi pre-test 60 menit
```

Hanya di `dev`. Mengubah `src/lib/pretest.js` (durasi 60 menit) dan
`PretestView.vue`. Perlu ikut di merge F3.

### F5 — Perubahan lokal FE belum di-commit (P2, rendah)

```
 M src/views/siswa/PretestView.vue   (+9 baris)
```

Menambah computed `waktuTampil` untuk format `mm:ss`. **Sudah lengkap dan
sudah dipakai** di template baris 312 — ini sisa pekerjaan, bukan pekerjaan
setengah. Cukup commit. Tidak ada test yang menyertainya; pertimbangkan menambah
satu spec untuk formatter-nya karena dipakai header dan `PengerjaanSoal`.

Bersama `test-results/` (artefak Playwright) dan `docs/image.png` yang juga
untracked — dua yang terakhir sebaiknya masuk `.gitignore`.

### F6 — 734 soal provinsi sudah siap tapi belum masuk (P2)

`laporan.json` mencatat dua kelompok yang sudah lolos penyaringan:

| | Baris sumber | Lolos | Dilewati |
| --- | --- | --- | --- |
| Kabupaten | 1.044 | **878** | 166 |
| Provinsi | 970 | **734** | 236 |
| **Total** | **2.014** | **1.612** | 402 |

Yang diimpor ke backend baru kabupaten (878). Soal provinsi yang sudah siap
sudah ada di folder yang sama, dan commit `b0b0a7b` sudah menambahkan filter `?tingkat`
khusus untuk itu. Menjalankan provinsi nyaris tanpa kode baru.

Alasan dilewati, dari `laporan.json`:

| Alasan | Jumlah |
| --- | --- |
| tanpa label (`label_materi_level.json`) | 213 |
| cacat di sumber | 82 |
| gambar hilang | 64 |
| tanpa kunci jawaban | 42 |
| kunci di luar pilihan | 1 |

**213 soal tanpa label** adalah angka terbesar. Ini akan berdebat dengan butir
F9 dan dengan pertanyaan terbuka di §10 draft skema. Perlu keputusan: label
manual, atau biarkan dilewati.

### F7 — Keputusan #1 masih menggantung (P2)

Butuh backend endpoint baru (a) atau menyembunyikan di FE (b). Plan menyarankan
(b) untuk rilis ini. Belum ada salah satunya. Lihat §4.

### F8 — Temuan "siswa tidak bisa diaktifkan kembali" belum diselesaikan (P2)

```
routes/api.php:101
  Route::post('siswa/{siswa}/deactivate', [SiswaController::class, 'deactivate']);
```

Tidak ada `activate`. Tombol "Nonaktifkan" di FE jadi jalan satu arah.
Ini sudah tercatat sebagai temuan di plan 7 Oktober dan masih apa adanya.

### F9 — Angka dokumen draft tidak sinkron dengan laporan (P3)

`docs/skema-pemisahan-bank-soal.md` vs `soal_osn/impor/laporan.json`:

| Pernyataan di draft | Angka di draft | Angka di laporan |
| --- | --- | --- |
| "234 soal tanpa label" (§10) | 234 | **213** |
| "Perkiraan hasil akhir ≈ 920 soal" (§6) | ≈920 | **878** |

Keduanya angka yang sama dengan hitungan berbeda. Salah satu sumbernya sudah basi — kemungkinan draft ditulis sebelum
penyaringan final.

Catatan lain di draft yang perlu dikonfirmasi kebenarannya: §4 menyatakan
`gambar_lokal` ada di 125 soal; `laporan.json` mencatat 64 soal **dilewati**
karena gambar hilang.

### F10 — `peruntukan` jadi data Kategori (P3)

Skrip `susun_folder_impor.py` menurunkan `peruntukan` dari tahun
(`peruntukan_dari_tahun`, baris 57) karena sumber tidak punya kolom ini.
Tapi draft skema §5 justru memutuskan **`peruntukan` dihapus** dan diganti
`paket_tes`, dengan alasan "kolom ini menjawab dipakai untuk apa, bukan soal ini apa".

Artinya selama migrasi belum jalan, database lokal berisi klasifikasi yang
direkayasa di atas. Tidak merusak apa pun sekarang, tapi jangan dipakai
sebagai dasar keputusan soal.

### F11 — Berkas debug di root `data-analytics` (P3)

`test_bobot.py` ter-commit di root repo (ter-track). Terlihat seperti skrip
percobaan. Pindahkan ke `scripts/` atau hapus.

### F12 — `docs/a.txt` untracked di backend (P3)

5,7 KB di `Osn-Readiness-Web/docs/`. Perlu dibaca lalu di-commit atau dihapus.

---

## 6. Yang **tidak** diverifikasi audit ini

Dinyatakan terbuka supaya tidak dianggap lulus:

- **Fase 3 (uji end-to-end) tidak diulang.** Butuh keempat proses hidup
  (Laravel, queue worker, layanan hitung Python, Vite). Hanya Postgres yang
  dinyalakan.
- **Tidak ada render Vue yang diperiksa.** Semua test backend dan unit frontend
  berjalan di luar browser. Transisi halaman, polling layar "sedang dinilai",
  dan interaksi form admin tetap belum dilihat langsung di
  `http://localhost:5173`.
- **Bank soal kabupaten 878 soal belum pernah diimpor ke database** — commit-nya
  belum di-merge, jadi belum ada yang bisa diverifikasi.
- **Rencana normalisasi bank soal belum dieksekusi.** Draft skema masih status
  "draft", dan 4 pertanyaannya (§10) belum terjawab.

---

## 7. Urutan yang disarankan

1. **Push `backup/bank-soal-kabupaten` dan commit artefak impor** (F1, F2).
   Paling murah dan paling tinggi Nilsainya dari semua temuan.
2. **Tentukan garis `dev`/`main` FE dan merge** (F3, F4). Cek konflik
   `vite.config.js` dan `vitest.config.js` manual.
3. **Impor provinsi 878 → 734** (F6) setelah langkah 1, pakai filter
   `--tingkat` yang sudah ada.
4. **Commit sisa pekerjaan FE** (F5), rapikan `.gitignore`.
5. **Putuskan butir yang masih terbuka** (§4): keputusan #1 dan #5, temuan
   `activate` (F8), dan 213 soal tanpa label (F6).
6. **Selaraskan angka draft skema** dengan `laporan.json` (F9) sebelum ada
   yang mengubahnya.

Langkah 1 dan 2 tidak bergantung satu sama lain dan bisa jalan paralel.

---

*Laporan ini dibuat dengan memeriksa log git, isi working tree, dan
menjalankan test suite ketiga repo. Tidak ada perubahan kode yang dibuat.*