# Ringkasan Sesi — Penyelesaian Batch 1 Akhir, Batch 2 Penuh, dan B3-A

Cakupan: PR #14 sampai PR #26 (3 Oktober 2026). Semua ter-merge ke `dev`.

Keadaan awal sesi: `dev` di `e860823`, 180/180 test hijau. `origin` belum punya
apa-apa dari Orang 2. Keadaan akhir: `dev` di `dbb091c`, **331/331 test hijau,
1267 assertion, 0 risky**, Pint bersih, CI GitHub Actions hijau.

---

## B0 — Fondasi (sudah ada sebelum sesi)

Tidak ada perubahan B0 di sesi ini, kecuali satu perbaikan seeder (lihat B2-C).

- B0-A, B0-B: ✅ sudah merged.
- KontenContohSeeder: diubah di sesi ini untuk bank latihan (lihat B2-C §3).

## B1 — Service inti & admin dasar

B1-A, B1-B, B1-C, B3-C sudah merged sebelum sesi. Yang dikerjakan sesi ini
adalah penyelesaian dua PR Orang 2 yang dibuka di tengah sesi:

| PR | Isi | Status akhir |
| --- | --- | --- |
| #15 | Dokumen status repo (diselaraskan: sebelumnya melapor keadaan pra-PR#7) | ✅ merged |
| #16 | Workflow CI: Pint + PHPUnit di Postgres 16 + build aset Vite | ✅ merged |
| #17 | **File test B2-A yang tertinggal dari PR #14** — `git add` saya hanya menunjuk `tests/Fakes`, bukan `tests/Feature`. Dev berjalan 180 test, bukan 203 seperti yang saya laporkan | ✅ merged |
| #18 | Test B1-C yang risky (`shouldHaveReceived` tidak dihitung PHPUnit sebagai assertion) | ✅ merged |
| #19 | B1-E milik Orang 2, diperbaiki langsung di branch-nya lalu di-merge | ✅ merged |
| #20 | B1-D milik Orang 2, di-sinkron + dikonsolidasi lalu di-merge | ✅ merged |

### Perbaikan di PR #19 (B1-E)

1. **Migration B0-A yang sudah jalan ditulis ulang.** Tabel `aturan_pemetaan`
   diganti dari bentuk baris-per-parameter (teks) menjadi kolom bertipe penuh,
   beserta `AturanService`. CI tidak menangkap karena selalu bikin DB dari nol;
   database yang sudah ada akan crash. Ketiga migration + `AturanService` +
   seeder + factory dikembalikan; fitur admin dibangun ulang **di atas**
   skema teks via `AturanPemetaanService` baru (tampilan flat 16 parameter,
   validasi jumlah persen = 100, batas materi wajib, kecukupan kuota).
   bonus desain mereka yang hilang (`latihan_min_soal` hardcoded 0, bobot
   per-level jadi aproksimasi /2) ikut pulih.
2. **`SoalGuard` mati diam-diam.** Mengecek tabel `pengerjaan` yang tidak ada,
   lalu `catch (\Throwable) { return false; }` menelannya → guard tidak pernah
   memblokir, dan tidak ada test yang membuktikannya (test update mereka
   di-skip). Sekarang cek via `SoalRepository::sedangDipakai`
   (`pretest_jawaban` + `quiz_jawaban`), tanpa menelan error, plus dua test baru.
3. Grup admin kembali memakai `role:Super Admin` (alias `role` didaftarkan di
   `bootstrap/app.php`), controller admin pindah ke namespace `Api\Admin\`,
   `SoalController::index` tidak lagi query langsung di controller.
4. `TingkatSeleksiSeeder` mematikan observer saat seeding supaya aturan bawaan
   observer tidak mengalahkan nilai seeder (provinsi selalu 70, bukan 80).

### Perbaikan di PR #20 (B1-D)

1. Sinkron `dev` terbaru; konflik `routes/api.php` + `MateriResource`
   (kedua PR mengubahnya) digabung: `id_sumber` + `file_materi` via helper URL
   + `gambar`.
2. **Keputusan #7 tuntas**: `/api/users` + `UserController` + `UserApiTest`
   dihapus; cakupan testnya (401, 403, soft delete, password tidak bocor)
   pindah ke `SiswaAdminTest` (12 test).
3. `SiswaController::destroy` ditambahkan — sebelumnya route DELETE menunjuk
   method yang tidak ada.
4. Dua grup admin yang duplikat (B1-E dengan `role`, B1-D dengan middleware
   kustom) disatukan dengan alias Spatie `role`; `CheckSuperAdminRole` dihapus.

## B2 — Alur siswa & alat admin (kunci sesi)

### B2-A · Pre-test & pemetaan — PR #14 + #17 (selesai sebelum sesi, dilengkapi di sesi)

4 endpoint: mulai (201 baru / 200 lanjutkan / 403 terkunci / 409 lulus),
ambil hasil, simpan jawaban, submit dua transaksi dengan Python di antaranya.
`selesaikanPenilaian` idempoten, dipakai `NilaiUlangJob`.

### B2-B · Materi, progress, latihan, syarat simulasi — PR #21 ✅

8 endpoint siswa + pengisian `SyaratSimulasiService` (kerangka B1-A yang selalu
`false`). Aturan kunci: materi terbaca selama tingkat terbuka, tapi menandai
selesai hanya saat ada putaran aktif (409 `BELUM_PRETEST`).

Dua temuan sewaktu mengerjakan:

1. **Siklus DI**: `SyaratSimulasiService` tidak boleh menyuntik
   `PutaranService`, karena `PutaranService` sendiri bergantung padanya sejak
   B1-A. Suntik balik = rekursi tanpa henti, test mati *memory exhausted*.
   Putaran aktif diambil langsung dari `PretestRepository`.
2. **Bug warisan B2-A**: `RekomendasiMateriRepository::untukPretest` mengurutkan
   kolom `peringkat` yang tidak ada (kolom benar: `prioritas`). Baru tertangkap
   karena jalur itu pertama kali dilewati data nyata.

### B2-C · Kecukupan bank soal & dashboard admin — PR #22 ✅

Laporan lima bagian sesuai BE-19, semuanya memakai `KuotaLevel::hitung()`
yang sama dengan `SoalPickerService` (wajib plan). Soal pre-test yang sudah
dijawab tidak dihitung tersedia; latihan dan simulasi boleh mengulang.
Guard `is_aktif` simulasi: menyalakan ditolak 422 bila bank kurang untuk satu
percobaan (BE-19). Dashboard admin: siswa aktif, pengerjaan per jenis, siswa
per tingkat aktif, rata-rata nilai per jenis.

### B2-D · Impor konten — PR #23 ✅

`php artisan impor:konten {folder} --dry-run` (BE-21): frontmatter Markdown,
JSON soal, gambar ke storage publik, konteks didedup per tingkat, upsert
`id_sumber`, auto-kompetensi.

- **Dry-run = transaksi yang di-rollback**, bukan sekadar skip tulis: FK,
  `jsonb`, dan check constraint ikut teruji.
- **`id_sumber` kembar membatalkan SELURUH impor** (bukan per berkas) dengan
  kode keluar gagal.
- Soal terpakai: perubahan terlarang (kunci/level/peruntukan/materi) dibuang
  dan dilaporkan, **teks tetap diperbaiki** — implementasi pertama saya salah
  (melewati seluruh baris) dan diperbaiki setelah diuji.

### Uji manual Batch 2 — PR #24 ✅

Setelah 303 test hijau, saya menjalankan alur B2 lewat HTTP sungguhan
(server `artisan serve` + server hitung terpisah dengan bentuk kontrak B1-C).
**Empat bug lolos dari suite**, semuanya karena database test tidak pernah
berisi data versi seeder:

| # | Gejala | Akar |
| --- | --- | --- |
| 1 | Penilaian ulang pre-test → 500 unique constraint `rekomendasi_materi` | `buatBanyak` memakai insert, bukan upsert |
| 2 | `POST /api/quiz/1/mulai` → 503 `BANK_SOAL_TIDAK_CUKUP` | Latihan memakai persen level pre-test (50/30/20); bank hanya berisi soal mudah. Sekarang mode bebas (`tanpaPembagianLevel`), sesuai docblock seeder |
| 3 | Bank latihan seeder 4/3/3 per materi, picker bebas ambil dari level mudah, quiz minta 10 | Sekarang 10 soal mudah per materi |
| 4 | Alt teks gambar impor hilang (`$1` dibaca sebagai variabel PHP dalam string dobel-kutip) + `materi_tingkat_id_urutan_unique` menabrak | Sekarang concat; urutan yang terpakai dilewati (baru) atau dipertahankan (perbarui) |

Bukti alur setelah diperbaiki: pretest 30 soal (15/9/6) → nilai 10 dari 3
benar; latihan 10 soal → nilai 40, tercatat sebagai nilai terbaik; syarat
`terpenuhi: false` (40 < 50); bank (225/15, 141/9, 94/6, putaran 15,
latihan 10/10); dashboard (1 siswa, 1/1/0, 0/40/0); impor (urutan 6, 2 soal
masuk, 1 ditolak dengan alasan jelas).

Pelajaran yang dicatat: **suite hijau ≠ fitur jalan**. Mulai sesi ini, setiap
paket baru dijalankan juga lewat HTTP dengan data seeder, bukan cuma
`composer test`.

## B3 — Simulasi & tampilan baca

### B3-A · Simulasi, kelulusan, penutupan otomatis — PR #25 ✅

Kontrak `SimulasiServiceInterface` (final sejak B1-C) dan
`KelulusanServiceInterface` (sisa B0-B) kini terisi;
`PenilaianBelumDiimplementasi` sudah tidak dipakai di mana pun.

### B3-B · Dashboard siswa + riwayat — PR #26 ✅ (paket terakhir sesi ini)

Dua endpoint baca (BE-15, BE-16). Tidak ada aturan bisnis baru; kelulusan
tidak dievaluasi di sini.

- `DashboardService` menyusun **hanya dari `PutaranService` +
  `SyaratSimulasiService`** — tanpa hitungan sendiri. Test membuktikan tahap
  dan rincian syarat di dashboard **identik** dengan jawaban endpoint yang
  dipakai penjaga simulasi.
- `GET /api/riwayat` membaca view `riwayat_hasil` lewat repository baca saja
  (tanpa create/update/delete), terbaru dulu, paginated, filter `jenis` dan
  `tingkat_id` lewat `FormRequest` khusus. Versi pertama saya mengabaikan
  nilai filter yang salah dalam diam; diperbaiki menjadi 422 sebelum commit.
- Tabrakan nama `DashboardController` (siswa `Api\` vs admin `Api\Admin\`)
  diselesaikan dengan FQCN di routes, persis pola
  `Admin\TingkatController` yang sudah ada.
- Satu asumsi test saya salah (tahap setelah syarat terpenuhi ternyata
  `SIAP_SIMULASI`, bukan `BELUM_PRETEST`/`BELAJAR`); diperbaiki, bukan
  implementasinya yang diubah.

### Sisa paket

| Paket | Pemilik | Status |
| --- | --- | --- |
| B3-B · Dashboard siswa + riwayat | Orang 1 (diambil dari Orang 2) | ✅ merged (PR#26) |
| B3-C · Purge akun | — | ✅ merged sebelum sesi |
| B4 · Skenario penuh + route check + docs | bersama | ⬜ satu-satunya yang tersisa |

## Keputusan yang diambil di sesi ini

1. Skema `aturan_pemetaan` tetap baris-per-parameter (B0-A); layer admin di
   atasnya via service, bukan migrasi ulang.
2. Dry-run impor = transaksi rollback; `id_sumber` kembar = batal seluruhnya.
3. Daftar dihindari simulasi = prioritas (semantik B1-B), bukan pengecualian.
4. Latihan tanpa pembagian level (mode bebas picker, sudah disediakan B1-B).
5. Setiap paket baru diverifikasi manual via HTTP + data seeder.
6. Filter liar di riwayat → 422, bukan diabaikan diam-diam.

## Catatan untuk Orang 2

- Bind `LatihanServiceInterface` sudah diganti ke `LatihanService` sungguhan;
  stub tinggal untuk simulasi (sudah tidak dipakai setelah PR #25).
- Perubahan asing `updateProfile` dari tooling luar tersimpan di
  `/tmp/asing-updateProfile.patch` — bukan dari PR mana pun; jalankan alat
  AI lain di folder terpisah supaya tidak menimpa working tree.
- `AGENTS.md` masih menulis PHP 8.3, padahal lockfile butuh PHP ≥ 8.4 (CI
  memakai 8.4). Perlu diperbarui.