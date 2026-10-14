# Batch Plan — Orang 1: Alur Siswa Inti

Bagian dari [BATCH_PLAN.md](BATCH_PLAN.md). Pasangan kerja: [BATCH_PLAN_ORANG_2.md](BATCH_PLAN_ORANG_2.md).

Sumber: [Logic per fitur.md](Logic%20per%20fitur.md), [Migration.pdf](Migration.pdf), [Kode migration.pdf](Kode%20migration.pdf). Semua kode mengikuti [ARCHITECTURE_RULES.md](ARCHITECTURE_RULES.md).

## Peran

Kamu memegang skema database dan perjalanan siswa dari pre-test sampai lulus: Auth, status putaran, pengambilan soal acak, pre-test, simulasi, dan kelulusan. Bagian ini paling banyak aturan bisnisnya dan menjadi jalur kritis proyek.

## Urutan kerja

| # | Paket | Butuh dari Orang 2 | Ditunggu Orang 2 untuk | Status 4 Okt |
| --- | --- | --- | --- | --- |
| 1 | B0-A · Postgres, migration, model, seeder | — | Semua paketnya | ✅ merged (`dev` `e860823`) |
| 1b | B0-B · Kontrak & error (diambil alih) | — | B1-C | ✅ merged (PR#7) |
| 2 | B1-A · Auth, AturanService, PutaranService | — | B2-B, B3-B | ✅ merged (PR#8) |
| 3 | B1-B · SoalPicker + SoalResource + Randomizer | — | B1-E, B2-B, B2-C | ✅ merged (PR#10) |
| 4 | B2-A · Pre-test & pemetaan | B1-C (dikerjakan ulang) | NilaiUlangJob jenis pretest | ✅ merged (PR#14, PR#17) |
| 5 | B3-A · Simulasi & kelulusan | **B2-B** (SyaratSimulasiService) | B3-B bagian simulasi | ✅ merged (PR#25) |
| 6 | B3-C · PurgeAkunJob | — | — | ✅ merged (PR#11) |
| 7 | B4 · Test skenario penuh | Semua paket Orang 2 | — | ⬜ setelah perbaikan audit |

**Posisi sekarang.** Semua paket Batch 1–3 milikmu sudah merge ke `dev`. Yang tersisa
adalah me-review perbaikan audit pada paketmu (SoalPicker, pre-test, simulasi) dan B4.
Rinciannya ada di [AUDIT-BATCH-1-3.md](AUDIT-BATCH-1-3.md#status-perbaikan).

Keputusan #7 sudah tuntas di PR#20: `/api/users` dihapus dan menu siswa ada di
`/api/admin/siswa`.

**Bila B1-C belum siap saat mulai B2-A:** pakai `FakePerhitunganClient` / mock `PerhitunganClientInterface` dengan bentuk kontrak di [BATCH_PLAN.md § B1-C](BATCH_PLAN.md#b1-c--perhitunganclient--nilaiulangjob-be-11).

**Bila harus menunggu B2-B sebelum B3-A:** mulai B3-A dengan mock `SyaratSimulasiServiceInterface` (sudah ada), lalu ganti ke implementasi asli setelah B2-B merge.

## Aturan kerja

- Ikuti [Alur branch & PR](BATCH_PLAN.md#alur-branch--pr-wajib-setelah-insiden-pr2) dan [Konvensi](BATCH_PLAN.md#konvensi-yang-sudah-berlaku-di-kode) di BATCH_PLAN. Ringkasnya: branch dari `dev` terbaru, PR hanya ke `dev`, jangan buat ulang file yang sudah ada.
- Satu paket = satu branch `feat/<kode-paket>-<nama>` = satu PR ke `dev`. Orang 2 me-review PR-mu, dan sebaliknya.
- File bersama (`routes/api.php`, `RepositoryServiceProvider`, `RolesAndPermissionsSeeder`, `bootstrap/app.php`): tulis hanya di blok berlabel paketmu.
- Kontrak yang belum ada dibuat oleh pemilik paket yang pertama memakainya. Untukmu: `RandomizerInterface`, `SoalPickerServiceInterface` + DTO (B1-B), dan `KelulusanServiceInterface` (B3-A). Perubahan kontrak yang sudah ada harus disepakati berdua.
- Test memakai PostgreSQL. Python selalu di-fake (`FakePerhitunganClient` dari B0-B atau `Http::fake`).
- DoD tiap paket: Feature test (401/403/422/sukses + kode error bisnis), Unit test untuk service berlogika, `vendor/bin/pint --test` bersih, `composer test` hijau, tidak ada query di Controller.

---

## 1. B0-A · Postgres, migration, model, seeder

**Butuh:** — (dikerjakan paralel dengan B0-B milik Orang 2)

- Ganti service `mysql` di `compose.yaml` ke `postgres:16`; `DB_CONNECTION=pgsql` di `.env.example` dan `phpunit.xml` (database test terpisah).
- 19 migration sesuai Kode migration, berurutan (users → tingkat_seleksi → ... → view `riwayat_hasil` paling akhir):
  - Migration 1 menyesuaikan repo: `is_active` sudah ada (lewati via `hasColumn`), softDeletes, index parsial `users_email_aktif_unique`. Lihat keputusan #1 soal tabel `sessions`.
  - Check constraint dan index parsial lewat `DB::statement`; `down()` lengkap.
  - Bila keputusan #4 disetujui: migration ke-20 untuk kolom `id_sumber` di `materi` dan `soal` (dipakai B2-D milik Orang 2).
- Model untuk 20 tabel: `$table` eksplisit (nama tidak jamak), relasi, casts, `SoftDeletes` di `User` dan `Soal`.
- Enum PHP di `app/Enums`: `Level`, `Peruntukan`, `TipeSoal`, `StatusProgress`, `StatusKenaikan`, `JenisPengerjaan`.
- Model `RiwayatHasil` (baca saja, tanpa timestamps) di atas view.
- Factory untuk semua model konten dan pengerjaan.
- Seeder: `TingkatSeleksiSeeder`, `AturanPemetaanSeeder` (32 baris, `firstOrCreate`), `KontenContohSeeder` (lokal/test: 5 materi Kabupaten, 10 Provinsi, bank cukup untuk 3 putaran pre-test + 1 simulasi + 1 latihan per materi). Update `RolesAndPermissionsSeeder`: role `siswa` menggantikan `user` dan permission `{resource}.{ability}` untuk semua resource admin.

**Selesai bila:** `php artisan migrate:fresh --seed` sukses di Postgres; `migrate:rollback` sampai bawah juga sukses.

## 2. B1-A · Auth, AturanService, PutaranService (BE-01, BE-02)

**Butuh:** B0-A, B0-B

- Sesuaikan `AuthService` yang sudah ada: register (role `siswa`, 201 + token), login manual (tanpa `Auth::attempt`), `Hash::check`, cek `is_active` (403), pesan 401 yang sama untuk email/password salah, throttle per email+IP (429), token dengan masa berlaku, logout hanya token aktif, `me` mengembalikan `tingkat_aktif_id`.
- Nonaktif/soft delete mencabut semua token: sediakan method di `UserService` yang dipanggil admin siswa (B1-D, Orang 2).
- `AturanService`: baca `aturan_pemetaan` per tingkat menjadi DTO bertipe (cache per request).
- `PutaranService`: turunkan 8 nilai (tingkat_terbuka, sudah_lulus, pretest_berjalan, putaran_aktif, percobaan_terpakai, simulasi_berjalan, putaran_habis, boleh_pretest_baru) dan `TahapSiswa`. Tidak ada kolom status.
- `GET /api/tingkat` → dua tingkat + `tingkat_terbuka` + `tahap`.

**Test:** unit test `PutaranService` untuk ketujuh tahap; feature test auth lengkap.

## 3. B1-B · SoalPicker + SoalResource (BE-03)

**Butuh:** B0-A, B0-B. Branch dari `dev` setelah B0-B dan B1-A merge.

- Kontrak (sisa B0-B): `App\Contracts\Services\SoalPickerServiceInterface`, DTO `App\DTOs\PermintaanSoal` (tingkat, peruntukan, jumlah, persen level, batas materi, soal dikecualikan, soal dihindari) dan `App\DTOs\SoalTerpilih` (soal, urutan, bobot), `RandomizerInterface` + `SeededRandomizer` untuk test.
- Fungsi kuota level terpisah (misalnya `KuotaLevel::hitung(jumlah, persen[])`: floor + sisa ke pecahan terbesar; 30 → 15/9/6). **Dipakai ulang Orang 2 di B2-C.**
- Langkah 1–7: kandidat (tingkat, peruntukan, belum dihapus, minus dikecualikan), batas materi pre-test (2 per materi, level dengan sisa kuota terbanyak), isi sisa, prioritas non-dihindari untuk simulasi, `BankSoalTidakCukupException` dengan rincian, acak urutan, pasang bobot.
- Tidak menulis ke DB; Randomizer disuntikkan.
- `SoalResource` (tanpa kunci) dan `SoalReviewResource` (dengan kunci dan pembahasan), termasuk isi cerita bila ada `konteks_id` dan URL gambar. Dipakai juga oleh Orang 2 (latihan, admin soal).

**Test:** unit test deterministik dengan seed untuk Kabupaten (10+20) dan Provinsi (20+10), kasus kurang per level, kurang per materi, dan prioritas soal dihindari; `SoalResource` tidak pernah memuat `kunci_jawaban`.

## 4. B2-A · Pre-test & pemetaan (BE-04, BE-05)

**Butuh:** B1-A, B1-B, B1-C (Orang 2)

- `POST /api/pretest`, `GET /api/pretest/{id}`, `PUT .../jawaban`, `POST .../submit` sesuai langkah di dokumen (403/409, kembalikan yang berjalan, tangkap pelanggaran `pretest_berjalan_unique`, isi `tingkat_aktif_id`).
- Submit dua transaksi dengan Python di antaranya; `PretestService::selesaikanPenilaian(int $id)` idempoten (implementasi `PretestServiceInterface extends PenilaianServiceInterface` dari B1-C), menyimpan `status_benar`, nilai, `pemetaan_materi`, `rekomendasi_materi`.
- Akses milik orang lain → 404.

**Test:** alur penuh dengan Python palsu, Python gagal → 503 + job ter-dispatch, submit dua kali, soal pre-test kedua tidak mengulang soal pertama.

## 5. B3-A · Simulasi, kelulusan, penutupan otomatis (BE-09, BE-10)

**Butuh:** B2-A, B2-B (Orang 2)

- `GET /api/simulasi`, `POST /api/simulasi/{id}/mulai`, `GET/PUT/POST /api/hasil-simulasi/{id}[...]`, `GET .../review`.
- Mulai dalam satu transaksi dengan `lockForUpdate` pada pretest putaran aktif, urutan cek 1–9 sesuai dokumen; daftar dihindari diteruskan ke picker; `batas_pada` disimpan.
- Simpan jawaban: 409 `WAKTU_HABIS` setelah `batas_pada` + 30 detik.
- Buat `KelulusanServiceInterface` (sisa B0-B) dan implementasinya.
- Submit dua transaksi; `SimulasiService::selesaikanPenilaian(int $id)` (implementasi `SimulasiServiceInterface` dari B1-C) memanggil `KelulusanService` di transaksi ke-2 (lulus → `kenaikan_tingkat`; gagal ke-3 → hapus progress dan quiz_pengerjaan tingkat itu + `kenaikan_tingkat` tidak_lulus).
- Command `simulasi:tutup-kedaluwarsa` tiap menit, `withoutOverlapping`, didaftarkan di `routes/console.php`.

**Test:** lulus di percobaan 1; gagal 3 kali → data terhapus dan pre-test baru boleh; dua request mulai bersamaan; scheduler menutup simulasi kedaluwarsa; nilai via job tetap memicu kelulusan.

## 6. B3-C · PurgeAkunJob

**Butuh:** B0-A

- Job terjadwal: force delete user yang soft-deleted lebih lama dari masa retensi (cascade menghapus pengerjaan). Masa retensi: keputusan #5.

## 7. B4 · Test skenario penuh (bersama Orang 2)

**Butuh:** semua paket

- Satu feature test: register → pre-test → belajar + latihan → simulasi gagal 3x → pre-test baru → lulus Kabupaten → Provinsi terbuka.

---

## Keputusan terbuka yang kamu pegang

Semua sudah diputuskan per 2 Oktober 2026; rincian dan alasannya ada di [BATCH_PLAN.md](BATCH_PLAN.md#keputusan-terbuka-putuskan-sebelumselama-b0).

| # | Keputusan | Hasil |
| --- | --- | --- |
| 1 | Tabel `sessions` di-drop di migration 1, padahal Breeze web memakai session. | `SESSION_DRIVER=cookie`. Breeze tetap dipakai, tabel `sessions` tetap di-drop, dan `down()` migration 1 membuatnya kembali. |
| 2 | Role `user` → `siswa`. | Sudah diterapkan: seeder, `UserService::registerUser`, factory, dan test. Permission admin berubah dari `users.*` jadi `siswa.*` supaya cocok dengan daftar resource di Migration.pdf. ARCHITECTURE_RULES §8 masih perlu diperbarui — milik Orang 2. |
| 4 | Kolom `id_sumber` untuk impor. | Migration ke-20 `2026_10_02_090019_add_id_sumber_to_materi_and_soal.php`: `string(100) nullable unique` di `materi` dan `soal`. |
| 5 | Masa retensi akun terhapus. | 30 hari sejak `deleted_at`. Dipakai mulai B3-C. |
| 3 | Angka usulan milikmu. | Throttle login 5/menit per email+IP, toleransi simulasi 30 detik, backoff `[10, 30, 60, 120, 300]`, upload maks 2 MB, ambang peringatan bank 2 putaran, masa berlaku token 7 hari. |
