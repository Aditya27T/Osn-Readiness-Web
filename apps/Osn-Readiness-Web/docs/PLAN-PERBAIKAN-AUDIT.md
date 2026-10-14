# Plan Perbaikan Temuan Audit Batch 1–3

## Konteks

[docs/AUDIT-BATCH-1-3.md](AUDIT-BATCH-1-3.md) menemukan 8 bug tingkat tinggi, sekitar 10 bug tingkat sedang, dan sejumlah temuan rendah di `dev` (`dbb091c`), padahal 331 test hijau. Semua harus beres sebelum B4 (test skenario penuh), karena B4 tidak melewati jalur-jalur ini.

Keputusan yang sudah diambil:
- **Rute ikut spesifikasi.** Pakai `?tingkat_id=` untuk syarat simulasi dan kecukupan bank, dan `POST /api/admin/materi/gambar` yang membalas path relatif.
- **Format soal ikut impor.** `pilihan_jawaban` berupa objek berkunci huruf (`{"A": ..}`), dan `kunci_jawaban` adalah huruf yang ada di pilihan. Format ini cocok dengan kolom `string` di DB dan dengan 1.719 soal hasil impor.
- **Dikerjakan Claude** sebagai PR berurutan ke `dev`. Pemilik paket asli (Orang 1/2) me-review.

## Alur kerja tiap PR (berlaku untuk semua)

1. `git switch dev && git pull`, lalu buat branch baru dari `dev` terbaru. Semua PR ke `dev`, tidak pernah ke `main`.
2. Tulis test yang gagal lebih dulu (membuktikan bug), baru perbaiki.
3. Sebelum PR, jalankan:
   - `DB_HOST=127.0.0.1 php artisan test`
   - `vendor/bin/pint --test`
   - `php artisan route:list --path=api`
   - `migrate:fresh --seed` di database sementara (`osn_audit_tmp`, dibuat lalu di-drop lewat container `osn-readiness-web-pgsql-1`)
4. **Uji HTTP dengan data seeder** untuk alur yang diubah (`php artisan serve` + server hitung palsu, seperti PR #24).
5. Commit dengan atribusi, lalu push dan buka PR ke `dev`. PR berikutnya baru dimulai setelah PR sebelumnya di-merge user.
6. Centang temuan yang beres di `docs/AUDIT-BATCH-1-3.md` di PR yang sama.

---

## PR 1 · `fix/b1b-picker`: SoalPicker (tinggi #1–3, pemilik Orang 1)

- **Randomizer produksi.** Buat `app/Randomizers/AcakRandomizer.php implements RandomizerInterface` memakai `\Random\Randomizer` bawaan PHP (`shuffleArray`, `pickArrayKeys`). Bind di [RepositoryServiceProvider.php:151](../app/Providers/RepositoryServiceProvider.php#L151) menggantikan `SeededRandomizer` dan `env('SOAL_ACAK_SEED')`. Bila ada feature test yang bergantung pada urutan tetap, bind `SeededRandomizer` di `tests/TestCase.php::setUp()`.
- **Latihan mode bebas.** Di `SoalPickerService::kelompokkan()`, tambahkan daftar datar `gabungan` (semua level). Baris 154 dan 247 memakai `gabungan` saat `$bebas`, bukan `semua[0]`.
- **Cadangan soal dihindari** (baris 257–263):
  - Masukkan `$take` pertama ke `$terpilih` sebelum menghitung `$penghindaran`.
  - Hitung ulang `$kurangLevelIni = $butuh - count($take)` setelah cadangan; jangan dipaksa 0.
- **Test** (di `tests/Unit/Services/SoalPickerServiceTest.php`):
  - Latihan dengan Mudah < `jumlah_soal` tetapi total cukup → lolos dan berisi campuran level.
  - Simulasi butuh 8 dengan 5 baru + 5 dihindari → 8 soal unik.
  - Simulasi dengan bank benar-benar kurang → `BankSoalTidakCukupException`.
  - Container me-resolve `AcakRandomizer`.

## PR 2 · `fix/penilaian-konsisten`: Alur penilaian dan pengerjaan (sedang, lintas B1-C/B2-A/B2-B/B3-A)

- **Idempoten di bawah kunci.** Di `selesaikanPenilaian` milik [PretestService](../app/Services/PretestService.php), [LatihanService](../app/Services/LatihanService.php), dan [SimulasiService](../app/Services/SimulasiService.php), transaksi 2 diawali `findUntukUpdate($id)` lalu berhenti bila `selesai_pada` sudah terisi. Pemeriksaan awal di luar transaksi boleh tetap ada untuk menghemat panggilan Python.
- **KelulusanService:**
  - Pindahkan `KenaikanTingkat::create` ke `KenaikanTingkatRepository` (method `catatLulus` dengan `insertOrIgnore`, yaitu `ON CONFLICT DO NOTHING` yang aman untuk index parsial `kenaikan_lulus_unique`, dan `catatTidakLulus`).
  - Buang `try/catch QueryException` yang membatalkan transaksi Postgres.
  - Hapus `hapusDataPutaran` yang mati.
- **NilaiUlangJob:** tangkap `PerhitunganKonfigurasiException` di `handle()`, lalu `$this->fail($e)` dan `return`. Docblock disesuaikan.
- **Mulai latihan ganda:**
  - Migration baru `2026_10_04_000000_add_quiz_pengerjaan_berjalan_unique.php`: `CREATE UNIQUE INDEX quiz_pengerjaan_berjalan_unique ON quiz_pengerjaan (user_id, quiz_id) WHERE disubmit_pada IS NULL`, dengan `down()` yang men-drop index itu.
  - `LatihanService::mulai` menangkap `Illuminate\Database\UniqueConstraintViolationException` lalu mengembalikan pengerjaan yang berjalan.
  - `PretestService` disempitkan dari `QueryException` ke exception yang sama.
- **Soal yang di-soft delete:**
  - Relasi `soal()` di `PretestJawaban`, `QuizJawaban`, dan `HasilSimulasiJawaban` memakai `->withTrashed()`.
  - Konteks di `SoalReviewResource` juga, bila relasinya soft delete.
- **Simpan jawaban setelah submit → 409 seragam.** Buat `PengerjaanSudahDisubmitException extends BisnisException` (409, kode `SUDAH_DISUBMIT`) dan pakai di ketiga service. Pre-test berhenti memakai kode `PUTARAN_MASIH_BERJALAN`.
- **Argumen tertukar.** Perbaiki urutan argumen `PerhitunganTidakTersediaException` di PretestService `:202-205` dan SimulasiService `:270-273` (cek LatihanService juga).
- **Test:**
  - Job: config error → `fail` dipanggil, tidak di-retry.
  - Penilaian kedua setelah selesai tidak menulis apa pun dan tidak menggandakan `kenaikan_tingkat`.
  - Mulai latihan dua kali → satu pengerjaan.
  - Soal di-soft delete saat pretest, latihan, atau simulasi berjalan → submit dan review tetap 200.
  - Simpan jawaban setelah submit → 409 `SUDAH_DISUBMIT` untuk ketiga jenis.

## PR 3 · `fix/b3a-simulasi`: Simulasi (sedang B3-A, pemilik Orang 1)

- **Rollback di `mulai`:**
  - Sebelum `DB::transaction`, cari percobaan berjalan milik siswa di tingkat itu. Bila sudah lewat `batas_pada` + toleransi, jalankan `submitInternal` **di luar transaksi**. Bila Python 503, job sudah ter-dispatch dan 503 diteruskan.
  - Setelah itu lanjutkan langkah 5–9 seperti biasa (kuota, syarat, mulai baru), sesuai BE-09 langkah 4.
  - Di dalam transaksi, `lanjutan()` tidak lagi menilai. Bila masih menemukan percobaan kedaluwarsa (kasus balapan), balas `SimulasiBelumDinilaiException` tanpa menulis.
- **`tutupKedaluwarsa`:** tangkap `Throwable` per baris, log dengan id percobaan, lalu lanjut ke baris berikutnya.
- **Rincian syarat.** `SyaratSimulasiBelumTerpenuhiException` saat mulai membawa `rincian` dari `SyaratSimulasi` sebagai `detail`.
- **Kontrak.** `SimulasiServiceInterface` mendeklarasikan `daftar`, `mulai`, `ringkasan`, `simpanJawaban`, `submit`, `review` yang dipakai controller. Validasi di `SimulasiController::index` pindah ke FormRequest. Respons "belum dinilai" di `HasilSimulasiController:65-70` diganti exception.
- **Test:**
  - `mulai` setelah `batas_pada` → nilai tersimpan, lalu percobaan baru dibuat atau kuota habis.
  - `SUDAH_LULUS`.
  - `SIMULASI_BELUM_DINILAI`.
  - Akses percobaan siswa lain → 404.
  - Scheduler saat Python 503/502 tetap menutup baris lain.
  - Test "bersamaan" diberi nama jujur (berurutan), dengan catatan bahwa penjaganya `lockForUpdate` + `hasil_simulasi_berjalan_unique`.

## PR 4 · `fix/b1e-admin-soal`: Soal admin (tinggi #7–8, sedang `sedangDipakai`, pemilik Orang 2)

- **`StoreSoalRequest` / `UpdateSoalRequest`:**
  - `pilihan_jawaban`: `required_if:tipe_soal,pilihan_ganda`, `prohibited_if:tipe_soal,isian`, `array`, `min:2`. Kuncinya harus huruf `A`–`Z`.
  - `kunci_jawaban`: `string|max:255`.
  - Di `after()`:
    - Pilihan ganda → kunci ada di `array_keys(pilihan)`.
    - `materi.tingkat_id` dan `konteks.tingkat_id` == `tingkat_id`.
  - Update memakai `sometimes`. Pemeriksaan silang memakai nilai lama soal dari route untuk kolom yang tidak dikirim.
- **`SoalGuard`:**
  - Satu konstanta `KOLOM_TERKUNCI = ['kunci_jawaban', 'level', 'peruntukan', 'materi_id']`.
  - `validateUpdate` membandingkan **nilai** (enum dinormalisasi ke `value`), bukan keberadaan key.
  - `filterForUpdate` (dipakai importer) memakai konstanta yang sama.
- **`SoalRepository::sedangDipakai`** ikut mengecek `hasil_simulasi_jawaban`.
- **Lapisan:**
  - Pindahkan query di `Admin/SoalController` dan `PembahasanController` ke service admin (+ interface di `app/Contracts/Services`, binding di `RepositoryServiceProvider`).
  - `destroy` hanya soft delete; **tidak menghapus file gambar**.
- **Test** (ganti stub `assertTrue(true)`):
  - Isian bisa dibuat tanpa pilihan.
  - Kunci di luar pilihan → 422.
  - Materi atau konteks beda tingkat → 422.
  - Soal terpakai: ubah teks → 200; ubah kunci, level, peruntukan, atau materi → 422.
  - Soal yang hanya dipakai simulasi juga terkunci.
  - Hapus soal → file gambar tetap ada.

## PR 5 · `fix/b1e-admin-latihan-simulasi`: Latihan dan simulasi admin (tinggi #4, sedang, pemilik Orang 2)

- **Binding rute.** Argumen controller jadi `Quiz $latihan` sesuai parameter rute `{latihan}`. Cek `SimulasiController` dengan pola yang sama.
- **Service.**
  - Buat `LatihanAdminService` dan `SimulasiAdminService` (+ interface). `Quiz::query()` dan `Simulasi::query()` di controller pindah ke repository.
  - Guard `is_aktif` (memakai `BankSoalService`) pindah dari controller ke service.
- **Validasi latihan:**
  - `materi_id` unik di `quiz` (`Rule::unique(...)->ignore`).
  - `jumlah_soal >= latihan_min_soal` tingkat materi itu, dibaca lewat `AturanServiceInterface` di `after()`.
- **Hapus:**
  - Latihan yang punya pengerjaan → 409 `LatihanMasihDigunakanException` (baru).
  - Simulasi yang punya hasil → 409 `SimulasiMasihDigunakanException` (baru).
  - Keduanya mengikuti pola `MateriMasihDigunakanException`.
- **Test:** CRUD lengkap untuk keduanya (401/403/422/409/sukses) dan guard `is_aktif` (menyala ditolak 422 saat bank kurang, lolos saat cukup).

## PR 6 · `fix/b1d-admin-struktur`: Struktur konten dan siswa (tinggi #5–6, sedang, pemilik Orang 2)

- **Hapus materi.**
  - Ganti `DB::table` di [MateriService.php:60-75](../app/Services/Admin/MateriService.php#L60-L75) dengan method `MateriRepository` yang mengecek soal (termasuk yang di-soft delete, karena FK restrict), `quiz`, `pemetaan_materi`, `rekomendasi_materi`, dan `progress_belajar`. Kasus apa pun → 409.
- **Upload gambar materi.**
  - Rute baru `POST /api/admin/materi/gambar` (tanpa `{materi}`), validasi `UploadMateriImageRequest` yang sudah ada.
  - Simpan ke disk `public/materi` dan balas `{ "path": "/storage/materi/<file>" }`, path relatif tanpa domain dan berformat sama dengan path yang ditulis importer ke `isi_materi`.
  - Hapus rute lama `materi/{materi}/upload-image` dan `$materi->update(['gambar' => ...])`.
  - Test: file ada di disk dan path di respons benar.
- **Validasi:**
  - Materi:
    - `kompetensi.tingkat_id == tingkat_id` (`after()`).
    - `urutan` unik per tingkat (`Rule::unique('materi')->where('tingkat_id', ..)->ignore`).
    - `max` mengikuti panjang kolom migration.
  - Kompetensi: `nama` unik per tingkat.
  - Tingkat: `UpdateTingkatRequest` hanya `nama_tingkat` dan `deskripsi`.
- **SiswaService:**
  - Nonaktifkan dan hapus memanggil `UserServiceInterface::deactivateUser` / `deleteUser` (sudah mencabut token).
  - Daftar dan detail hanya role `siswa`, termasuk yang nonaktif.
  - Akun non-siswa (admin) → 404, sehingga admin tidak bisa menghapus dirinya sendiri lewat endpoint ini.
  - Query pindah ke `UserRepository`.
- **Permission.**
  - Samakan nama di policy dengan `RolesAndPermissionsSeeder` (`konteks-soal.*`, resource sendiri untuk latihan, simulasi, dan aturan).
  - Satu gaya `before()` (`hasRole('Super Admin')`).
  - Pakai atau hapus `SiswaPolicy` / `LatihanPolicy` yang tidak terpakai.
- **Test:**
  - Hapus materi untuk tiap jenis rujukan → 409; materi bersih → 200.
  - Kompetensi kembar → 422.
  - `urutan` materi kembar → 422.
  - Soft delete siswa mencabut token.
  - Super Admin tidak muncul di daftar.
  - 401/403 untuk materi, konteks, dan pembahasan.

## PR 7 · `fix/b2-impor-bank-rute`: Importer, bank soal, dan rute (sedang dan rendah, pemilik Orang 2)

- **Importer** ([ImporKontenService.php](../app/Services/ImporKontenService.php)):
  - Pakai `Level::tryFrom` / `Peruntukan::tryFrom`; nilai tidak valid → soal ditolak dengan alasan.
  - Materi beda tingkat → soal ditolak.
  - Isian memakai `tingkat_kesulitan` dari berkas.
  - `id_sumber` materi kembar → seluruh impor batal.
  - Test per kasus di `tests/Feature/ImporKontenTest.php`.
- **Bank soal.** Laporan "tersedia" memakai stok bank (tingkat + peruntukan, belum dihapus) tanpa mengurangi soal yang pernah dipakai siswa lain, karena picker mengecualikan per siswa. "Putaran pre-test" = min(tersedia/kuota) per level, sesuai tabel BE-19. Di PR, sebutkan bahwa ini membalik keputusan sesi sebelumnya.
- **Rute ke spesifikasi:**
  - `GET /api/simulasi/syarat?tingkat_id=`: FormRequest + cek tingkat terbuka (403).
  - `GET /api/admin/bank-soal/kecukupan?tingkat_id=`.
  - Test lama disesuaikan.
- **Alasan syarat.** `SyaratSimulasi` DTO mendapat field opsional `?string $alasan = null`. Nilainya `belum_pretest` saat tidak ada putaran aktif. Field ini tambahan yang kompatibel, jadi pemanggil lama tidak berubah.

## PR 8 · `chore/rapikan-temuan-rendah`: Temuan rendah lainnya

- **`bootstrap/app.php`:**
  - `dontReport` untuk `BisnisException`, kecuali `BankSoalTidakCukupException`.
  - Tambah `context()` di `BisnisException` supaya `detail` ikut tercatat.
- **Auth:**
  - Email di-lowercase di `prepareForValidation` request register dan login.
  - Register bersamaan → tangkap `UniqueConstraintViolationException` → 422.
  - `Schedule::command('sanctum:prune-expired --hours=24')->daily()` di `routes/console.php`.
- **Dashboard dan riwayat:**
  - `sisa_kuota` dihitung di `PutaranService` (field baru `sisaKuotaSimulasi` di `StatusPutaran`), bukan di `DashboardService`.
  - Buat `RiwayatService` (+ interface) supaya `RiwayatController` tidak menyuntik repository.
- **N+1:**
  - Eager-load `materi.quiz` dan nilai terbaik dalam satu query di `SyaratSimulasiService::periksa` dan `BelajarService`.
  - `BelajarService::daftar` memanggil `PutaranService::status()` sekali.
- **Bersih-bersih:**
  - Hapus `app/Services/PenilaianBelumDiimplementasi.php`.
  - Perbaiki karakter nyasar di `PretestApiTest.php:488`.
  - Pesan `status.in` di `ProgressRequest`.
- **Test tambahan:**
  - Client: koneksi gagal dan timeout → 503.
  - Token kedaluwarsa → 401.
  - Urutan wajib dulu di `GET /api/materi`.
  - 403 detail materi saat tingkat terkunci.

## PR 9 · `docs/pasca-audit`: Dokumen

- Perbarui tabel status di [BATCH_PLAN.md](BATCH_PLAN.md), [BATCH_PLAN_ORANG_1.md](BATCH_PLAN_ORANG_1.md), dan [BATCH_PLAN_ORANG_2.md](BATCH_PLAN_ORANG_2.md) ke keadaan sekarang (Batch 1–3 selesai + perbaikan audit, B4 berikutnya).
- [Logic per fitur.md](Logic%20per%20fitur.md):
  - Kode `SUDAH_DISUBMIT`.
  - Format pilihan berhuruf untuk admin.
  - Arti "tersedia" di laporan bank.
  - Index `quiz_pengerjaan_berjalan_unique`.
- [AUDIT-BATCH-1-3.md](AUDIT-BATCH-1-3.md): tandai tiap temuan beserta nomor PR-nya.
- Tambah bagian di [RINGKASAN-SESI.md](RINGKASAN-SESI.md).

---

## Verifikasi akhir (setelah PR 8 merge)

1. `DB_HOST=127.0.0.1 php artisan test`: semua hijau, jumlah test naik dari 331.
2. `vendor/bin/pint --test`, `php artisan route:list --path=api`, `grep -rnE "DB::|::where\(|::find\(|::query\(" app/Http/Controllers --include=*.php | grep -v Controllers/Auth/` (harus kosong).
3. `migrate:fresh --seed` lalu `migrate:reset` di database sementara.
4. **Uji HTTP end-to-end** dengan seeder dan server hitung palsu:
   - Dua siswa mulai pre-test dan mendapat soal berbeda.
   - Latihan berisi campuran level.
   - Simulasi kedaluwarsa ditutup lewat `mulai`.
   - Admin hapus materi → 409.
   - Upload gambar membalas path.
   - Soal terpakai bisa diperbaiki teksnya tetapi tidak kuncinya.
   - Hapus latihan benar-benar terhapus.
5. Setelah itu baru mulai B4.
