# Laporan Kondisi Repo — Osn-Readiness-Web

Tujuan: agent lain bisa memverifikasi sendiri setiap klaim di dokumen ini, lalu memutuskan
langkah berikutnya tanpa perlu bertanya.

Semua SHA di bawah diverifikasi pada 3 Oktober 2026, `git fetch --all --prune` terakhir.

---

## 1. Peta branch

| Ref | SHA | Isi | Kondisi |
| --- | --- | --- | --- |
| `origin/dev` | `e860823` | B0-A, B0-B, B1-A, B1-B, B3-C, B1-C | ✅ hijau, branch kerja utama |
| `origin/main` | `f34ec73` | hanya hasil revert PR#2 | ⚠️ tertinggal **26 commit** dari `dev` |
| `origin/feat/b2a-pretest` | `4cc7975` | + B2-A (23 file, +1018) | 🔧 PR #14 open, menunggu review |
| `origin/feat/b0b-kontrak-error` | `dfef48f` | B0-B versi salah | ❌ sudah tidak dipakai (PR#4 closed) |
| `fork/dev` | `b255ef4` | kanal cadangan | ℹ️ |
| `fork/main` | `99db5b7` | kanal cadangan | ℹ️ |

Tidak ada lagi branch B0-B, B1-A, B1-B, B1-C, atau B3-C di remote — semuanya sudah dihapus
setelah merge.

## 2. Status PR

| PR | Base ← Head | Status |
| --- | --- | --- |
| #1 | `main` ← `dev` | merged |
| #2 | `main` ← `feat/b0b-kontrak-error` | merged — **menyebabkan `main` rusak** |
| #3 | `dev` ← `feat/b0a-skema-model` | merged |
| #4 | `dev` ← `feat/b0b-kontrak-error` | closed tanpa merge ✅ |
| #5 | `main` ← revert PR#2 | merged — `main` kembali bisa boot |
| #6 | `dev` ← `docs/kondisi-repo` | merged |
| #7 | `dev` ← `feat/b0b-kontrak` | merged |
| #8 | `dev` ← `feat/b1a-auth-putaran` | merged |
| #9 | `main` ← `dev` | **closed** — sync rilis yang tidak sempat dikerjakan |
| #10 | `dev` ← `feat/b1b-soal-picker` | merged |
| #11 | `dev` ← `feat/b3c-purge-akun` | merged |
| #12 | `dev` ← `feat/b1c-perhitungan-v2` | merged — B1-C dibangun ulang di atas B0-B |
| #13 | `dev` ← `feat/b1c-perhitungan` | closed — digantikan #12 |
| #14 | `dev` ← `feat/b2a-pretest` | **open** — B2-A |

---

## 3. `dev` — SEHAT (diverifikasi dari nol)

```
DB_CONNECTION=pgsql DB_HOST=127.0.0.1 DB_PORT=54329 DB_USERNAME=sail DB_PASSWORD=password
```

| Cek | Hasil di `dev` | Hasil di PR #14 |
| --- | --- | --- |
| `php artisan migrate:fresh --seed` | ✅ 26 migration | ✅ 26 migration, tanpa migration baru |
| `php artisan migrate:rollback --step=100` | ✅ 26 rollback | ✅ |
| `composer test` | ✅ **180/180**, 571 assertion | ✅ **203/203**, 828 assertion |
| `vendor/bin/pint --test` | ✅ PASS | ✅ PASS |
| Query di Controller | ✅ 0 | ✅ 0 |
| Isi DB setelah seed | 2 tingkat · 32 aturan_pemetaan · 15 materi · 2205 soal · 15 quiz · 2 simulasi · 2 role · 47 permission | sama |

B2-A menambah 23 test dan tidak menambah migration apa pun — seluruh skema sudah ada dari B0-A.

## 4. `main` — bisa boot, tapi tertinggal jauh

`main` sudah tidak rusak (PR#2 di-revert oleh PR#5). Namun isinya baru sampai batas
revert tersebut: **26 commit** seluruh `dev` belum ada di sana, termasuk seluruh B0-A.

Tidak ada PR rilis yang aktif (`dev` → `main`). Buka baru setelah satu blok paket selesai.

---

## 5. Paket yang sudah selesai

| Paket | Isi utama |
| --- | --- |
| B0-A | Postgres 16, 26 migration, model, enum, factory, seeder, view `riwayat_hasil` |
| B0-B | `BisnisException` + subclass, DTO aturan/status/syarat, kontrak typed |
| B1-A | Auth token, `AturanService`, `PutaranService`, `TingkatService`, skeleton `SyaratSimulasiService`, `GET /api/tingkat`, cabut token saat nonaktif |
| B1-B | `KuotaLevel`, `RandomizerInterface` + `SeededRandomizer`, `SoalPickerService`, `SoalResource`, `SoalReviewResource` |
| B1-C | `PerhitunganClient`, `NilaiUlangJob`, validasi balasan, `FakePerhitunganClient` |
| B3-C | `PurgeAkunJob` (retensi 30 hari), schedule 03:00 `withoutOverlapping` |
| B2-A | 4 endpoint pre-test, 3 repository baru, 3 DTO, `PretestService` |

## 6. Paket yang belum selesai dan siapa pemiliknya

| Paket | Pemilik | Butuh |
| --- | --- | --- |
| B1-D · Admin struktur konten & siswa | Orang 2 | B1-A ✅ |
| B1-E · Admin soal & konfigurasi | Orang 2 | B1-B ✅ |
| B2-B · Materi, progress, latihan, syarat simulasi | Orang 2 | B1-A ✅ B1-B ✅ B1-C ✅ |
| B2-C · Kecukupan bank soal & dashboard | Orang 2 | B1-D, B1-E |
| B2-D · Impor konten | Orang 2 | B1-D, B1-E |
| B3-A · Simulasi & kelulusan | **Orang 1** | B2-A (PR #14) + **B2-B** |
| B3-B · Dashboard & riwayat | Orang 2 | B2-B |
| B4 · Test skenario penuh | Orang 1 | semua paket |

**B3-A terblokir keras oleh B2-B, bukan hanya oleh urutan kerja:**
`SyaratSimulasiService::periksa()` masih kerangka yang selalu mengembalikan
`terpenuhi: false`, jadi pintu mulai simulasi tidak akan pernah terbuka sebelum B2-B
mengisi pemeriksaan progress dan nilai latihan yang sebenarnya. B3-A tidak bisa diuji
end-to-end tanpa itu.

---

## 7. Yang perlu diputuskan sebelum Orang 2 mulai B1-D

### 7.1 Rute admin siswa (keputusan #7)

Rekomendasi di `BATCH_PLAN.md`: modul users pindah dari `/api/users` ke
`/api/admin/siswa` dan rute lama dihapus, dikerjakan di B1-D.

**Risiko yang belum tercatat di plan:** `tests/Feature/Api/UserApiTest.php` (10 pemanggilan
JSON) semuanya memakai `/api/users`. Begitu rute lama dihapus, test itu ikut gagal —
dan karena file-nya sudah di `dev`, kegagalan itu kena ke siapa pun yang menjalankan
`composer test`.

Dua jalan keluar, pilih salah satu sebelum B1-D merge:

- **Orang 1/secara lebih awal** memindahkan rute dan memperbarui test-nya, lalu B1-D
  Orang 2 tidak perlu menyentuh file milik Orang 1.
- **Orang 2 memindahkan di B1-D** dan sekaligus memperbarui `UserApiTest.php`.

### 7.2 Pemilik grup rute `admin`

`BATCH_PLAN.md` § B1-E dan `BATCH_PLAN_ORANG_2.md` §3 sama-sama menyebut grup rute
`admin` (prefix `/api/admin`, middleware `auth:sanctum`, `active`, `role:Super Admin`)
"dibuat oleh paket admin yang merge duluan". Belum ada yang mengklaimnya, jadi B1-D dan
B1-E bisa sama-sama membuatnya dan saling menimpa. Perlu disepakati sebelum salah satu
dimulai.

Role yang tersedia hanya `Super Admin` dan `siswa` — jangan pakai `role:admin`.

---

## 8. Keputusan lain yang masih terbuka

| # | Keputusan | Status |
| --- | --- | --- |
| 6 | Kontrak final layanan Python `/hitung/penilaian` dan `/hitung/pretest` ada di luar repo ini. Sementara bentuk array di kontrak B1-C jadi acuan; kalau berubah, sesuaikan di dalam client saja. | **Terbuka** |
| 7 | Rute admin siswa, lihat §7.1 | **Perlu disepakati** |
| 8 | `KuotaLevel::hitung()` di B1-B, `TahapSiswa` di `app/Enums/` | Selesai |

---

## 9. Konvensi yang harus dijaga

- Enum di `app/Enums/`, DTO di `app/DTOs/`.
- Tabel tidak jamak → setiap model wajib `#[Table('...')]`, dan `constrained()` selalu
  menyebut nama tabelnya.
- Query hanya di Repository. Controller memanggil Interface, `Gate::authorize`,
  balikan lewat API Resource.
- Arsitektur Service-Repository: Controller → Service → Repository → Model.
- `DB::transaction` di Service. `Hash::make` tidak boleh ada di Service.
- Kolom `text` di `aturan_pemetaan` dikonversi ke tipe oleh service, bukan pemanggil.
- `StatusPutaran` menyimpan id (`pretestBerjalanId`, `putaranAktifId`,
  `simulasiBerjalanId`) selain boolean-nya, supaya pemanggil tidak query ulang.
- `kunci_jawaban` tidak boleh masuk `SoalResource`.
- Partial unique index harus lewat `DB::statement`; Laravel tidak punya index parsial.
- `riwayat_hasil` adalah SQL view, migration-nya wajib jalan paling akhir.

---

## 10. Peringatan teknis (bikin salah judgment kalau dilupakan)

- **PostgreSQL 16 only.** Skema memakai index unik parsial, `jsonb`, dan check
  constraint. SQLite dan MySQL tidak bisa dipakai, termasuk untuk test.
- **Database test terpisah** `osn_readiness_testing` (lihat `phpunit.xml`).
  Jangan pernah pakai database pengembangan untuk test.
- **Jangan pakai symlink `vendor` untuk verifikasi lewat worktree.** Autoloader `App\`
  akan menunjuk repo utama dan hasil test jadi menyesatkan — ini sudah terjadi
  dua kali. Verifikasi `dev`/`main` dengan checkout langsung di repo utama.
- **`.env` hasil salinan tidak boleh dipakai apa adanya.** Repo Laravel memakai env
  repository *immutable*, jadi baris pertama yang mendefinisikan suatu kunci menang.
  Kalau menyalin `.env`, pastikan baris pertama tiap kunci sudah benar, atau export
  env eksplisit di shell.
- `SESSION_DRIVER=cookie` (tabel `sessions` di-drop migration 1, Breeze web tetap dipakai).
- Role default adalah `siswa`, bukan `user`.

---

## 11. Perintah verifikasi cepat

```bash
git fetch --all --prune

# dev sehat?
git switch dev && git merge --ff-only origin/dev
DB_CONNECTION=pgsql DB_HOST=127.0.0.1 DB_PORT=54329 DB_USERNAME=sail DB_PASSWORD=password \
  sh -c 'php artisan config:clear && composer test && vendor/bin/pint --test'

# cek kandidat PR sebelum merge
git switch feat/b2a-pretest && git merge --ff-only origin/feat/b2a-pretest
# env sama, lalu composer test && vendor/bin/pint --test
```

## 12. Saran urutan merge

1. Review lalu merge PR #14 (B2-A) ke `dev`.
2. Tunggu B1-D, B1-E, B2-B, B2-C, B2-D dari Orang 2.
3. Setelah B2-B masuk, mulai B3-A dari `dev` terbaru dengan
   `SyaratSimulasiServiceInterface` yang sudah ada (mock di test, lalu ganti ke
   implementasi asli).
4. Buka PR rilis `dev` → `main` setelah satu blok paket selesai. `main` sudah tertinggal
   26 commit.