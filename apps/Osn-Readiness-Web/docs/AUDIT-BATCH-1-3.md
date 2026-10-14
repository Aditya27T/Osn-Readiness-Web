# Audit Batch 1–3

Tanggal: 4 Oktober 2026. Posisi `dev`: `dbb091c` (setelah PR #26).

Acuan: [BATCH_PLAN.md](BATCH_PLAN.md), [BATCH_PLAN_ORANG_1.md](BATCH_PLAN_ORANG_1.md), [BATCH_PLAN_ORANG_2.md](BATCH_PLAN_ORANG_2.md), [Logic per fitur.md](Logic%20per%20fitur.md), dan [ARCHITECTURE_RULES.md](ARCHITECTURE_RULES.md).

## Ringkasan

Semua paket Batch 1–3 (B1-A s.d. B1-E, B2-A s.d. B2-D, B3-A s.d. B3-C) sudah merge ke `dev`. Pemeriksaan mekanis semuanya lolos, tetapi audit kode menemukan **8 bug tingkat tinggi** yang tidak tertangkap oleh test. Semua temuan tingkat tinggi sudah dicek ulang langsung di kode. **Perbaiki temuan tingkat tinggi sebelum mulai B4.**

Pelajarannya sama dengan uji manual PR #24: suite hijau belum berarti fitur jalan. Sebagian besar bug di bawah berada di jalur yang tidak pernah dilewati data seeder maupun test.

## Status perbaikan

- [x] Temuan tinggi #1–3 (SoalPicker): implementasi [PR #27](https://github.com/PTI-A-PROJECT/Osn-Readiness-Web/pull/27) (PR 1) di `fix/b1b-picker`; sudah merge ke `dev`.
  - Randomizer produksi menggunakan `Random\Randomizer` tanpa seed tetap.
  - Latihan mengambil kandidat dari semua level; seeder kembali memakai campuran 4/3/3.
  - Cadangan simulasi mengecualikan soal yang baru dipilih dan melaporkan kekurangan nyata.
  - Lima regresi picker gagal sebelum perbaikan dan lolos sesudahnya; delapan kasus tambahan memeriksa kontrak randomizer.
  - Verifikasi akhir: 344 test / 1305 assertion lolos, Pint bersih, 72 rute API, migrasi + seeder + reset di PostgreSQL 16 sementara berhasil.
  - HTTP dengan seeder dan server hitung palsu: dua siswa mendapat set pretest berbeda; tiga latihan campuran 4/3/3 berhasil dimulai dan disubmit; simulasi kedua dengan hanya lima soal baru per level tetap berisi 30 soal unik dan berhasil disubmit.
- [x] Temuan sedang pada alur penilaian dan pengerjaan: implementasi [PR #28](https://github.com/PTI-A-PROJECT/Osn-Readiness-Web/pull/28) (PR 2) di `fix/penilaian-konsisten`; menunggu review dan merge ke `dev`.
  - `selesaikanPenilaian` (pre-test, latihan, simulasi) mengunci ulang baris dan mengecek `selesai_pada` di transaksi kedua.
  - `KelulusanService` menulis `kenaikan_tingkat` lewat repository; insert lulus memakai `ON CONFLICT DO NOTHING`.
  - `NilaiUlangJob` langsung gagal pada kesalahan konfigurasi, tanpa retry.
  - Index parsial `quiz_pengerjaan_berjalan_unique` mencegah dua pengerjaan latihan berjalan; migration-nya menghapus baris kembar lama dan menyisakan yang terbaru.
  - Relasi `soal()` pada ketiga model jawaban memuat soal yang di-soft delete.
  - Simpan jawaban setelah submit dibalas 409 `SUDAH_DISUBMIT` di ketiga alur; argumen `PerhitunganTidakTersediaException` yang tertukar diperbaiki.
  - Verifikasi: 358 test / 1352 assertion lolos, Pint bersih, 72 rute API, migrasi + seeder di PostgreSQL 16 sementara berhasil; 12 test baru gagal terhadap kode lama.
  - HTTP dengan seeder dan server hitung palsu: soal terhapus saat pre-test berjalan tetap bisa disubmit; delapan request mulai latihan bersamaan menghasilkan satu pengerjaan; delapan submit bersamaan menghasilkan satu nilai. Alur simulasi hanya diuji lewat feature test.
  - Belum dikerjakan di PR ini: `simpanJawaban` masih membaca pengerjaan tanpa kunci, dan submit ulang saat Python mati masih mengirim job baru tiap kali.
- [x] Temuan sedang B3-A (simulasi): PR 3 di `fix/b3a-simulasi` (commit lokal, bertumpuk di atas `fix/penilaian-konsisten`).
  - `mulai` menutup percobaan kedaluwarsa sebelum transaksi dibuka, sehingga nilai dan kelulusannya tidak lagi ikut di-rollback.
  - `tutupKedaluwarsa` mencatat dan melewati baris yang gagal, tidak lagi berhenti di baris pertama.
  - `SYARAT_SIMULASI_BELUM_TERPENUHI` saat mulai membawa rincian per materi (JSON di `detail`).
  - `SimulasiServiceInterface` mendeklarasikan semua method yang dipakai controller; validasi daftar pindah ke FormRequest; review yang belum dinilai kini 409 `SIMULASI_BELUM_DINILAI` (sebelumnya 404).
- [x] Temuan tinggi #7–8 dan `sedangDipakai` (soal admin): PR 4 di `fix/b1e-admin-soal` (commit lokal, bertumpuk di atas `fix/b3a-simulasi`).
  - Pilihan jawaban berkunci huruf (`{"A": ...}`) dan kunci berupa hurufnya, sama dengan importer; soal isian dibuat tanpa pilihan; kunci wajib ada di pilihan; materi dan cerita wajib setingkat.
  - `SoalGuard` mengunci kunci, level, peruntukan, dan materi dengan membandingkan nilai, sehingga teks soal terpakai bisa diperbaiki. Update menerima sebagian kolom.
  - `sedangDipakai` ikut mengecek jawaban simulasi. Hapus soal tidak lagi menghapus berkas gambar.
  - Soal dan pembahasan admin lewat `SoalAdminService`; tidak ada lagi tulis langsung ke model di controller.
- [x] Temuan tinggi #4 dan aturan latihan/simulasi admin: PR 5 di `fix/b1e-admin-latihan-simulasi` (commit lokal, bertumpuk di atas `fix/b1e-admin-soal`).
  - Argumen controller latihan disamakan dengan parameter rute `{latihan}`, sehingga show, update, dan destroy benar-benar bekerja.
  - Latihan: satu per materi (422), `jumlah_soal >= latihan_min_soal` (422), hapus ditolak 409 `LATIHAN_MASIH_DIGUNAKAN` bila punya pengerjaan.
  - Simulasi: hapus ditolak 409 `SIMULASI_MASIH_DIGUNAKAN` bila punya hasil; guard `is_aktif` pindah ke service dan hanya memeriksa bank saat dinyalakan atau saat jumlah soal simulasi aktif berubah.
  - Query pindah dari controller ke `LatihanAdminService`/`SimulasiAdminService` dan repository. 21 test baru untuk kedua resource.
- [x] Temuan tinggi #5–6 dan temuan struktur konten/siswa: PR 6 di `fix/b1d-admin-struktur` (commit lokal, bertumpuk di atas `fix/b1e-admin-latihan-simulasi`).
  - Hapus materi mengecek soal (termasuk yang di-soft delete), latihan, pemetaan, materi wajib, dan progress lewat repository; semuanya 409 `MATERI_MASIH_DIGUNAKAN`.
  - Upload gambar pindah ke `POST /api/admin/materi/gambar` dan membalas `data.path` tanpa domain (`/storage/materi/...`). Rute lama `materi/{materi}/upload-image` dan field `gambar` di `MateriResource` dihapus.
  - Validasi: kompetensi setingkat, `urutan` unik per tingkat, nama kompetensi unik per tingkat, panjang teks mengikuti kolom, tingkat hanya nama dan deskripsi.
  - Siswa: daftar hanya role siswa (aktif maupun nonaktif); akun admin 404 di menu siswa; soft delete dan nonaktif lewat `UserService` sehingga token dicabut.
  - Policy memakai satu gaya `before()` dan nama permission yang sama dengan seeder; `LatihanPolicy` dan `SiswaPolicy` yang tidak terpakai dihapus.
  - Belum dikerjakan: service Kompetensi, Konteks, dan Tingkat admin masih memakai Eloquent langsung.
- [x] Importer, laporan bank soal, dan rute: PR 7 di `fix/b2-impor-bank-rute` (commit lokal, bertumpuk di atas `fix/b1d-admin-struktur`).
  - Importer: level atau peruntukan tidak sah dan materi beda tingkat hanya menolak soal itu; soal isian memakai `tingkat_kesulitan` dari berkas; `id_sumber` materi kembar membatalkan seluruh impor.
  - Laporan bank soal menghitung stok bank, tidak lagi mengurangi soal yang pernah dipakai siswa lain. Ini membalik keputusan sesi B2-C, supaya laporan sama dengan kandidat yang dilihat SoalPicker.
  - Rute mengikuti spesifikasi: `GET /api/simulasi/syarat?tingkat_id=` (kini 403 bila tingkat terkunci) dan `GET /api/admin/bank-soal/kecukupan?tingkat_id=`.
  - Balasan syarat simulasi memuat `alasan` (`belum_pretest` saat tidak ada putaran aktif), juga di dashboard.
- [x] Temuan rendah: PR 8 di `chore/rapikan-temuan-rendah` (commit lokal, bertumpuk di atas `fix/b2-impor-bank-rute`).
  - `BisnisException` tidak lagi dicatat sebagai ERROR; yang berstatus 5xx dicatat sebagai peringatan beserta rinciannya (termasuk `BANK_SOAL_TIDAK_CUKUP`).
  - Auth: email disamakan huruf kecilnya dan dicocokkan tanpa membedakan huruf; pendaftaran bersamaan dibalas 422; `sanctum:prune-expired` terjadwal harian.
  - `sisaKuotaSimulasi` dihitung `PutaranService`; `RiwayatService` menggantikan repository di controller.
  - N+1 di daftar materi dan syarat simulasi dihapus (satu query nilai terbaik per siswa).
  - `PenilaianBelumDiimplementasi` dihapus; pesan validasi progress dan karakter nyasar di test diperbaiki.
- [x] Dokumen: PR 9 di `docs/pasca-audit` (commit lokal, bertumpuk di atas `chore/rapikan-temuan-rendah`).
  - Tabel status di ketiga BATCH_PLAN diperbarui ke 4 Oktober.
  - Logic per fitur memuat `SUDAH_DISUBMIT`, format pilihan berhuruf, arti "tersedia" di laporan bank, index latihan berjalan, kolom `alasan` syarat simulasi, dan kode 409 baru untuk hapus latihan/simulasi.

### Verifikasi akhir (di ujung tumpukan, sebelum PR 9)

- `php artisan test`: 432 test, seluruhnya lolos (naik dari 331). Pint bersih. 72 rute API. Tidak ada query di controller.
- `migrate:fresh --seed` dan `migrate:reset` berhasil di PostgreSQL 16 pada database sementara.
- HTTP lewat `php artisan serve` dengan data seeder dan server hitung palsu: dua siswa mendapat set pre-test berbeda; latihan berisi tiga level; simulasi kedaluwarsa ditutup lewat `mulai` dan percobaan baru dibuat; hapus materi yang dirujuk 409; upload gambar membalas path; teks soal terpakai bisa diperbaiki tetapi kuncinya tidak; latihan baru bisa diubah dan dihapus; admin tidak bisa menghapus dirinya lewat menu siswa; soft delete siswa mencabut token.
- Batas verifikasi: layanan hitung yang dipakai adalah tiruan, bukan layanan Python produksi. Balapan penilaian ditiru di test lewat kait pada klien palsu; yang diuji dengan request bersamaan sungguhan hanya mulai dan submit latihan.

### Yang sengaja belum dikerjakan

- `simpanJawaban` masih membaca pengerjaan tanpa kunci baris.
- Submit ulang saat Python mati masih mengirim `NilaiUlangJob` baru tiap kali.
- Service admin Kompetensi, Konteks, dan Tingkat masih memakai Eloquent langsung; `AturanPemetaanController` masih menyuntik kelas konkret.
- Pemeriksaan tingkat terbuka pada `GET /api/simulasi/syarat` ada di controller, karena `SyaratSimulasiService` tidak boleh menyuntik `PutaranService` (siklus dependensi).
- Bentuk respons `GET /api/materi`, `/materi/{id}`, dan `/simulasi/syarat` belum diseragamkan ke `{message, data}`.
- Revisi `ARCHITECTURE_RULES.md` §3.5 dan §8, serta test kontrak terhadap layanan Python (keputusan #6 masih terbuka).

Temuan di bawah dipertahankan sebagai catatan kondisi awal audit.

## Pemeriksaan mekanis

| Pemeriksaan | Hasil |
| --- | --- |
| `php artisan test` (Postgres 16) | ✅ 331/331, 1267 assertion |
| `vendor/bin/pint --test` | ✅ bersih |
| `migrate:fresh --seed` lalu `migrate:reset` sampai bawah (di database sementara) | ✅ sukses |
| Rute siswa di balik `auth:sanctum` + `active`; rute admin di balik `role:Super Admin` | ✅ |
| Tidak ada query di Controller | ❌ [Admin/LatihanController.php:22](../app/Http/Controllers/Api/Admin/LatihanController.php#L22) dan [Admin/SimulasiController.php:24](../app/Http/Controllers/Api/Admin/SimulasiController.php#L24) |

## Temuan tingkat tinggi

### B1-B · SoalPicker

1. **Semua siswa mendapat soal yang sama.** Di produksi, `RandomizerInterface` di-bind ke `SeededRandomizer` dengan seed tetap `20261003` ([RepositoryServiceProvider.php:151-153](../app/Providers/RepositoryServiceProvider.php#L151-L153)). Dengan kandidat yang sama, hasil acak selalu sama: pre-test pertama dan paket simulasi tiap siswa identik, urutannya pun sama. Produksi perlu implementasi acak sungguhan; `SeededRandomizer` cukup untuk test.
2. **Latihan hanya mengambil soal Mudah.** Mode bebas membaca `semua[0]`, padahal `kelompokkan()` mengelompokkan per level, sehingga `semua[0]` hanya berisi soal Mudah ([SoalPickerService.php:154](../app/Services/SoalPickerService.php#L154), [:247](../app/Services/SoalPickerService.php#L247)). Bila soal Mudah di suatu materi kurang dari `jumlah_soal`, latihan gagal dengan `BANK_SOAL_TIDAK_CUKUP` meskipun total soal materi itu cukup. Bug #2 pada uji manual PR #24 sebenarnya hanya tertutup oleh perubahan seeder, bukan diperbaiki.
3. **Cadangan soal dihindari bisa menghasilkan soal kurang dari permintaan tanpa error.** `$penghindaran` dihitung sebelum `$take` level itu masuk ke `$terpilih`, sehingga soal yang sama bisa terambil dua kali dan menyusut saat digabung. `$kurangLevelIni` lalu dipaksa 0 tanpa dicek ([SoalPickerService.php:257-263](../app/Services/SoalPickerService.php#L257-L263)). Akibatnya paket simulasi bisa berisi soal lebih sedikit dari yang diminta. Test yang ada hanya menguji kasus `$take` kosong.

### B1-D / B1-E · Admin

4. **Show, update, dan destroy latihan admin tidak melakukan apa-apa.** Parameter rute `{latihan}` tidak cocok dengan argumen controller `Quiz $quiz`, sehingga implicit binding tidak jalan dan yang disuntikkan adalah `new Quiz` kosong. Contoh: `DELETE /api/admin/latihan/5` membalas 200 "berhasil dihapus" padahal tidak ada yang terhapus.
5. **Menghapus materi selalu 500.** [MateriService.php:69](../app/Services/Admin/MateriService.php#L69) memanggil `DB::table('latihan')`, padahal nama tabelnya `quiz`. Pemeriksaan juga melewatkan `progress_belajar` dan `rekomendasi_materi` (FK restrict), sehingga kasus itu pun berakhir 500, bukan 409. Belum ada test hapus materi.
6. **Path upload gambar materi hilang.** Tabel `materi` tidak punya kolom `gambar` dan kolom itu tidak ada di fillable, jadi `update(['gambar' => $path])` diabaikan diam-diam ([MateriController.php:80-85](../app/Http/Controllers/Api/Admin/MateriController.php#L80-L85)). Respons berisi `gambar: null` dan file menjadi yatim. Test-nya lolos karena kebetulan (`assertExists(null)` di [MateriAdminTest.php:105](../tests/Feature/Api/Admin/MateriAdminTest.php#L105)).
7. **`SoalGuard` mengunci kolom yang salah.** Yang dikunci `pertanyaan`, `pilihan_jawaban`, `kunci_jawaban`, `level` ([SoalGuard.php:42](../app/Guards/SoalGuard.php#L42)); menurut spesifikasi seharusnya kunci, level, peruntukan, dan materi, sedangkan teks boleh diubah.
   - Semua kolom `required` saat update, dan guard menolak hanya karena kolomnya ada di request. Akibatnya soal yang sudah dipakai sama sekali tidak bisa diedit, termasuk untuk memperbaiki salah ketik.
   - Sebaliknya, `peruntukan` dan `materi_id` pada soal terpakai bebas diubah.
   - `test_super_admin_can_update_soal` hanya berisi `assertTrue(true)`.
8. **Validasi soal tidak sesuai spesifikasi** ([StoreSoalRequest.php:26-35](../app/Http/Requests/StoreSoalRequest.php#L26-L35), juga `UpdateSoalRequest`):
   - Soal isian tidak bisa dibuat karena `pilihan_jawaban` wajib untuk semua tipe.
   - Kunci tidak dicek ada di pilihan (kunci 7 dengan 3 pilihan diterima).
   - Tidak ada pemeriksaan materi dan konteks harus setingkat.
   - Kunci berupa indeks integer, sedangkan soal hasil impor memakai `{"A": ...}` dan kunci `"C"`. Mengedit soal hasil impor memaksa kunci integer yang tidak cocok lagi dengan pilihan.

## Temuan tingkat sedang

- **B3-A · `mulai` setelah waktu habis berputar tanpa akhir.** `lanjutan()` menilai percobaan lewat `submitInternal`, lalu melempar `SimulasiBelumDinilaiException` di dalam transaksi `mulai`. Seluruh penilaian (`disubmit_pada`, nilai, kenaikan tingkat, penghapusan data) ikut di-rollback. Bila scheduler tidak jalan, siswa selalu mendapat 409 dan tidak ada yang tersimpan ([SimulasiService.php:360-375](../app/Services/SimulasiService.php#L360-L375)). Belum ada test untuk jalur ini.
- **Soal yang di-soft delete admin membuat pengerjaan berjalan gagal 500.** Relasi `soal()` di `PretestJawaban`, `QuizJawaban`, dan `HasilSimulasiJawaban` tidak memakai `withTrashed()`, sementara `Admin/SoalController::destroy` tidak mengecek soal sedang dipakai. Pre-test yang berisi soal itu tidak bisa disubmit, dan `pretest_berjalan_unique` menghalangi pre-test baru, sehingga siswa macet di tingkat itu. Review simulasi lama juga ikut 500.
- **`selesaikanPenilaian` tidak aman terhadap balapan** (Pretest, Latihan, Simulasi). `selesai_pada` dicek tanpa kunci (`findUntukUpdate` dipanggil di luar transaksi), dan transaksi kedua tidak mengecek ulang. Bila job dan submit ulang berjalan bersamaan, percobaan dinilai dua kali. Pada simulasi gagal ke-3 bisa tercatat dua baris `tidak_lulus`; pada lulus, insert kembar ditelan tetapi transaksi Postgres sudah batal sehingga commit-nya diam-diam di-rollback.
- **Klik ganda "mulai latihan" membuat dua pengerjaan.** Tidak ada index parsial seperti `pretest_berjalan_unique` di `quiz_pengerjaan`, dan pemeriksaan `berjalan()` lalu insert tidak atomik ([LatihanService.php:58-85](../app/Services/LatihanService.php#L58-L85)).
- **`NilaiUlangJob` mengulang kesalahan konfigurasi 5 kali.** `handle()` tidak menangkap `PerhitunganKonfigurasiException` untuk memanggil `$this->fail()`, padahal spesifikasi meminta tidak di-retry. Docblock di [NilaiUlangJob.php:52](../app/Jobs/NilaiUlangJob.php#L52) menyatakan sebaliknya dari perilakunya.
- **`SiswaService` (admin siswa):**
  - Soft delete tidak mencabut token. Hook di `UserService` (B1-A) tidak pernah dipanggil dari API.
  - Daftar siswa ikut menampilkan Super Admin.
  - Siswa nonaktif tidak bisa dilihat atau dihapus (404).
  - Admin bisa menonaktifkan atau menghapus akunnya sendiri.
- **`SoalRepository::sedangDipakai` tidak mengecek `hasil_simulasi_jawaban`.** Kunci soal yang hanya pernah dipakai di simulasi masih bisa diubah, termasuk lewat importer.
- **Beberapa kasus admin berakhir 500, bukan 409/422:**
  - Latihan kedua untuk materi yang sama (unique constraint DB).
  - Hapus latihan yang punya pengerjaan dan hapus simulasi yang punya hasil (FK restrict).
  - `urutan` materi kembar per tingkat dan nama kompetensi kembar per tingkat.
  - Teks melebihi panjang kolom (`max:255` di request, padahal kolom 100–200).
  - Kompetensi materi tidak dicek setingkat; `jumlah_soal >= latihan_min_soal` pada latihan juga belum dicek.
  - Tingkat masih bisa diubah `urutan`-nya (spesifikasi: hanya nama dan deskripsi).
- **Importer ([ImporKontenService.php](../app/Services/ImporKontenService.php)):**
  - `tingkat_kesulitan` atau `peruntukan` yang tidak valid memicu `ValueError`, sedangkan command hanya menangkap `RuntimeException`, sehingga seluruh impor berhenti alih-alih menolak satu soal.
  - Tingkat materi tidak dicek sama dengan tingkat soal.
  - Soal isian dipaksa level Mudah.
  - `id_sumber` kembar pada berkas materi tidak terdeteksi.
- **Laporan kecukupan bank soal mengurangi soal yang pernah dipakai siswa mana pun** ([BankSoalService.php:33-43](../app/Services/BankSoalService.php#L33-L43)), padahal picker hanya mengecualikan soal milik siswa itu sendiri. Setelah banyak pre-test, laporan bisa menyatakan "kurang" padahal picker masih jalan. *Perlu dicek ulang.*

## Temuan tingkat rendah

- **Rute berbeda dari rencana:** `simulasi/syarat/{tingkat}` (rencana `?tingkat_id=`, dan tanpa cek tingkat terbuka), `bank-soal/kecukupan/{tingkat}` (rencana `?tingkat_id=`), `materi/{materi}/upload-image` (rencana `materi/gambar` dengan balasan path relatif).
- **Kode error tidak seragam untuk simpan jawaban setelah submit:** pre-test 409 dengan kode `PUTARAN_MASIH_BERJALAN` (menyesatkan), latihan dan simulasi 422 (spesifikasi 409).
- **Argumen `PerhitunganTidakTersediaException` tertukar** di PretestService (`:202-205`) dan SimulasiService (`:270-273`); teks teknis client masuk ke `message`.
- **`SYARAT_SIMULASI_BELUM_TERPENUHI` saat mulai simulasi hanya berisi jumlah**, bukan rincian seperti di spesifikasi. `periksa()` tanpa putaran aktif juga tidak memberi alasan "belum pre-test".
- **Pelanggaran lapisan arsitektur:**
  - `DashboardService` menghitung `sisa_kuota` sendiri (spesifikasi: tanpa hitungan sendiri).
  - `RiwayatController` menyuntik repository langsung tanpa service.
  - `KelulusanService` memanggil `KenaikanTingkat::create` langsung; `hapusDataPutaran` adalah kode mati.
  - Service B1-D memakai Eloquent dan `DB::table` langsung; `AturanPemetaanController` menyuntik kelas konkret tanpa interface.
  - `SimulasiServiceInterface` hanya mendeklarasikan `tutupKedaluwarsa`, padahal controller memanggil `daftar`, `mulai`, `simpanJawaban`, dan `submit`.
- **Nama permission tidak konsisten:** policy memakai `konteks_soal.*`, seeder `konteks-soal.*`; policy Simulasi/Quiz/Latihan/Aturan memakai `soal.*`. Saat ini aman hanya karena `before()` meloloskan Super Admin; role lain akan kena `PermissionDoesNotExist` (500).
- **`tutupKedaluwarsa` hanya menangkap 503**; error 502 atau soal null menghentikan seluruh putaran scheduler.
- **Semua `BisnisException` tercatat sebagai ERROR** dengan stack trace (misalnya tiap gagal login), sementara rincian `BANK_SOAL_TIDAK_CUKUP` yang diminta spesifikasi justru tidak tercatat.
- **Auth:** email tidak di-lowercase (`A@x.com` dan `a@x.com` jadi dua akun); register bersamaan dengan email sama berakhir 500; tidak ada `sanctum:prune-expired` di jadwal.
- **N+1** di `BelajarService::nilaiLatihanTerbaik` dan `SyaratSimulasiService::periksa`; `PutaranService::status()` dipanggil dua kali per request daftar materi.
- **Hapus soal ikut menghapus file gambar** padahal soal hanya di-soft delete, sehingga review percobaan lama menampilkan gambar rusak.
- **Kode mati:** [PenilaianBelumDiimplementasi.php](../app/Services/PenilaianBelumDiimplementasi.php) tidak dipakai di mana pun dan bisa dihapus.

## Celah test

- Latihan admin, simulasi admin, dan guard `is_aktif` **belum punya test sama sekali**.
- Belum ada test 401/403 untuk admin materi, konteks, pembahasan, latihan, dan simulasi; belum ada test hapus materi/konteks yang ditolak dan keunikan kompetensi.
- Test "dua request mulai bersamaan" ([SimulasiApiTest.php:186](../tests/Feature/Api/SimulasiApiTest.php#L186)) sebenarnya berjalan berurutan.
- Belum ada test untuk `SUDAH_LULUS` dan `SIMULASI_BELUM_DINILAI` pada simulasi, akses milik siswa lain (404) pada simulasi, jalur lewat `batas_pada` di `mulai`, dan scheduler saat Python gagal.
- Belum ada test kesalahan konfigurasi di job (tidak di-retry), koneksi gagal atau timeout di client, masa berlaku token, dan pencabutan token lewat `/api/admin/siswa`.
- Belum ada test urutan "wajib dulu" pada `GET /api/materi`, 403 detail materi saat tingkat terkunci, dan simpan jawaban latihan setelah submit.

## Dokumen yang tertinggal

Saat audit, tabel status di ketiga BATCH_PLAN masih menggambarkan keadaan 3 Oktober. Sudah diperbarui di PR 9.

## Urutan perbaikan yang disarankan (sebelum B4)

1. **SoalPicker** (temuan tinggi 1–3), karena berdampak ke semua siswa.
2. **Bug admin** (temuan tinggi 4–8), sekaligus menambah test latihan dan simulasi admin.
3. **Balapan penilaian dan soal yang di-soft delete** (temuan sedang), lalu rollback di `mulai` B3-A.
4. Sisa temuan sedang dan rendah, lalu perbarui tabel status di ketiga BATCH_PLAN.

Test skenario penuh B4 tidak akan menangkap sebagian besar temuan di atas, karena data seeder tidak pernah melewati jalur-jalur ini. Setiap perbaikan sebaiknya diuji juga lewat HTTP dengan data seeder, seperti pada PR #24.
