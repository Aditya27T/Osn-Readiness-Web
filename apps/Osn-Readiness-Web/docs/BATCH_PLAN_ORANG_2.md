# Batch Plan — Orang 2: Infrastruktur, Admin & Fitur Pendukung

Bagian dari [BATCH_PLAN.md](BATCH_PLAN.md). Pasangan kerja: [BATCH_PLAN_ORANG_1.md](BATCH_PLAN_ORANG_1.md). Kondisi repo terbaru: [HANDOFF-KONDISI.md](HANDOFF-KONDISI.md).

Sumber: [Logic per fitur.md](Logic%20per%20fitur.md), [Migration.pdf](Migration.pdf), [Kode migration.pdf](Kode%20migration.pdf). Semua kode mengikuti [ARCHITECTURE_RULES.md](ARCHITECTURE_RULES.md).

## Peran

Kamu memegang klien Python, seluruh menu admin, materi dan latihan siswa, syarat simulasi, dashboard, riwayat, dan impor konten. Jumlah paketmu lebih banyak, tetapi sebagian besar CRUD dan penyusunan data. Aturan bisnis yang berat ada di sisi Orang 1.

B0-B (kontrak dan error handling) sudah diambil alih Orang 1 di `feat/b0b-kontrak`. Versi di `feat/b0b-kontrak-error` **tidak dipakai lagi**: bentuk kontraknya menyimpang dari Logic per fitur, dan PR#2 ke `main` membuat aplikasi tidak bisa boot. Rinciannya ada di HANDOFF-KONDISI §4–§6.

## Wajib dibaca sebelum mulai

1. [Alur branch & PR](BATCH_PLAN.md#alur-branch--pr-wajib-setelah-insiden-pr2): branch dari `dev` terbaru, **PR hanya ke `dev`**, jangan pernah PR fitur ke `main`.
2. [Konvensi yang sudah berlaku](BATCH_PLAN.md#konvensi-yang-sudah-berlaku-di-kode): lokasi enum/DTO/kontrak, `#[Table]`, subclass `BisnisException`, bentuk `SyaratSimulasi`.
3. Sebelum menulis kelas baru, cek dulu di `dev` apakah file itu sudah ada. Jangan membuat ulang migration, model, enum, exception, atau kontrak.

## Urutan kerja

| # | Paket | Butuh dari Orang 1 | Ditunggu Orang 1 untuk | Status 4 Okt |
| --- | --- | --- | --- | --- |
| 0 | Tutup branch lama | — | — | `feat/b0b-kontrak-error` dan `feat/b1c-perhitungan` tidak di-merge |
| 1 | B1-C · PerhitunganClient & NilaiUlangJob (ulang) | B0-B merge ke `dev` | **B2-A** | ✅ merged (PR#12) |
| 2 | Revisi ARCHITECTURE_RULES §3.5 & §8 | — | — | ⬜ belum; ikut B4 |
| 3 | B1-E · Admin: soal & konfigurasi | B1-B (SoalResource) | — | ✅ merged (PR#19) |
| 4 | B1-D · Admin: struktur konten & siswa | B1-A | — | ✅ merged (PR#20) |
| 5 | B2-B · Materi, latihan, syarat simulasi | B1-A, B1-B | **B3-A (prioritaskan)** | ✅ merged (PR#21) |
| 6 | B2-C · Kecukupan bank soal & dashboard admin | B1-B (fungsi kuota) | — | ✅ merged (PR#22) |
| 7 | B2-D · Impor konten | — (`id_sumber` sudah ada di `dev`) | — | ✅ merged (PR#23) |
| 8 | B3-B · Dashboard & riwayat siswa | B1-A; B3-A untuk bagian simulasi | — | ✅ merged (PR#26) |
| 9 | B4 · Route check & dokumentasi | Semua paket Orang 1 | — | ⬜ setelah perbaikan audit |

Semua paket Batch 1–3 milikmu sudah merge ke `dev`. Yang tersisa adalah me-review perbaikan audit pada paketmu (admin, latihan, importer, bank soal) dan B4. Rinciannya ada di [AUDIT-BATCH-1-3.md](AUDIT-BATCH-1-3.md#status-perbaikan).

## Aturan kerja

- Satu paket = satu branch `feat/<kode-paket>-<nama>` dari `dev` terbaru = satu PR ke `dev`. Orang 1 me-review PR-mu, dan sebaliknya.
- File bersama (`routes/api.php`, `RepositoryServiceProvider`, `RolesAndPermissionsSeeder`, `bootstrap/app.php`): blok `// [Bx-y]` per paket sudah ada di `routes/api.php` (dari B1-A). Tulis hanya di blok paketmu.
- Kontrak yang belum ada dibuat oleh pemilik paket yang pertama memakainya. Untukmu: `PerhitunganClientInterface`, `PenilaianServiceInterface` beserta tiga turunannya (B1-C), dan grup rute `admin`. Perubahan kontrak yang sudah ada harus disepakati berdua.
- Test memakai PostgreSQL (`osn_readiness_testing`). Python selalu di-fake.
- DoD tiap paket: Feature test (401/403/422/sukses + kode error bisnis), Unit test untuk service berlogika, `vendor/bin/pint --test` bersih, `composer test` hijau, `php artisan route:list --path=api` jalan, tidak ada query di Controller.

---

## 0. Tutup branch lama

- `feat/b0b-kontrak-error`: PR#4 sudah closed. Jangan dibuka ulang dan jangan di-rebase.
- `feat/b1c-perhitungan` (`c1fbe11`): jangan di-merge. Ambil bagian yang masih berguna (lihat di bawah) ke branch baru.

## 1. B1-C · PerhitunganClient & NilaiUlangJob (BE-11) — dikerjakan ulang

**Butuh:** `feat/b0b-kontrak` sudah merge ke `dev`. Branch baru: `feat/b1c-perhitungan-v2` dari `dev`.

**Kenapa versi lama tidak bisa dipakai:**

- `PerhitunganClient` meng-*implement* `App\Contracts\Clients\PerhitunganClientInterface` dan memakai `App\DTOs\PermintaanSoal`, padahal keduanya tidak ada. Kelas gagal di-load.
- Method-nya `hitungNilai`, `hitungRanking`, `matchSoalToSiswa`, dan `validateHasil`. Logic per fitur hanya punya `hitungPenilaian` dan `hitungPretest`. Pemilihan soal adalah tugas SoalPicker (PHP, Orang 1), dan peringkat sudah termasuk balasan `hitungPretest`.
- Client menunggu backoff 10–300 detik **di dalam request HTTP**. Backoff panjang itu milik job; client cukup retry 2 kali dengan jeda pendek.
- `PenilaianService` tunggal dengan stub per jenis bertabrakan dengan `PretestService`, `LatihanService`, dan `SimulasiService`.
- Kode error baru `PERHITUNGAN_TIDAK_TERSEDIA`, padahal `HasilSedangDiprosesException` (503) dan `LayananHitungSalahKonfigurasiException` (502) sudah ada.

**Yang boleh diambil dari branch lama:** blok `perhitungan` di `config/services.php`, env `PERHITUNGAN_*` di `.env.example`, header `X-Internal-Token`, dan kerangka test `Http::fake`.

**Kontrak (bentuk final, jangan diubah tanpa sepakat berdua):**

```php
namespace App\Contracts\Clients;

interface PerhitunganClientInterface
{
    /**
     * @param  list<array{soal_id:int, tipe_soal:string, bobot:int, jawaban_user:?string, kunci_jawaban:string}>  $soal
     * @return array{nilai:float, jawaban:list<array{soal_id:int, status_benar:bool}>}
     */
    public function hitungPenilaian(array $soal): array;

    /**
     * @param  list<array{soal_id:int, materi_id:int, tipe_soal:string, bobot:int, jawaban_user:?string, kunci_jawaban:string}>  $soal
     * @param  list<array{materi_id:int, urutan:int}>  $materi
     * @return array{nilai:float, jawaban:list<array{soal_id:int, status_benar:bool}>,
     *               pemetaan:list<array{materi_id:int, jumlah_soal:int, jumlah_benar:int, poin_didapat:int, poin_maksimal:int, persentase:float, peringkat:int}>,
     *               materi_wajib:list<array{materi_id:int, prioritas:int}>}
     */
    public function hitungPretest(array $soal, array $materi, int $jumlahMateriWajib): array;
}

namespace App\Contracts\Services;

interface PenilaianServiceInterface
{
    /** Idempoten: bila selesai_pada sudah terisi, berhenti tanpa menulis apa pun. */
    public function selesaikanPenilaian(int $id): void;
}

interface PretestServiceInterface extends PenilaianServiceInterface {}   // diisi Orang 1 di B2-A
interface LatihanServiceInterface extends PenilaianServiceInterface {}   // diisi olehmu di B2-B
interface SimulasiServiceInterface extends PenilaianServiceInterface {}  // diisi Orang 1 di B3-A
```

Nama field balasan Python mengikuti keputusan #6 (masih terbuka). Bila kontrak Python final berbeda, sesuaikan di dalam client saja; bentuk array yang dikembalikan ke service tetap seperti di atas.

**Isi paket:**

- `App\Clients\PerhitunganClient implements PerhitunganClientInterface`, di-bind di blok `[B1-C]` `RepositoryServiceProvider`.
- `config/services.php` → `perhitungan.url/token/timeout(5)/retry(2)`.
- `Http::withHeaders(['X-Internal-Token' => ...])->timeout(5)->retry(2, 200, when: koneksi/timeout/5xx)`.
- `PerhitunganTidakTersediaException extends HasilSedangDiprosesException` (503 `HASIL_SEDANG_DIPROSES`) untuk timeout, koneksi gagal, atau 5xx setelah retry.
- `PerhitunganKonfigurasiException extends LayananHitungSalahKonfigurasiException` (502 `LAYANAN_HITUNG_SALAH_KONFIGURASI`, `Log::critical`) untuk 403/422 atau balasan tidak cocok.
- Validasi balasan: jumlah jawaban sama dengan yang dikirim, setiap materi punya pemetaan, jumlah materi wajib sama dengan `$jumlahMateriWajib`.
- `NilaiUlangJob(JenisPengerjaan $jenis, int $id)`: `match ($jenis)` → `PretestServiceInterface` / `LatihanServiceInterface` / `SimulasiServiceInterface`, lalu `selesaikanPenilaian($id)`. `tries = 5`, `backoff = [10,30,60,120,300]`, tidak retry untuk `PerhitunganKonfigurasiException`, `failed()` mencatat log untuk admin.
- `tests/Fakes/FakePerhitunganClient` untuk dipakai Orang 1 (B2-A, B3-A) dan B2-B.

**Test:** client dengan `Http::fake` (sukses, 5xx → retry lalu 503, 403/422 → 502, balasan tidak cocok → 502, header token terkirim); job dengan mock service per jenis (dipanggil sekali, konfigurasi error tidak di-retry); satu test kontrak contoh request/response.

## 2. Revisi ARCHITECTURE_RULES (PR dokumen)

- §3.5: Service boleh bergantung pada Client interface (`app/Contracts/Clients`).
- §8: role default `siswa` (bukan `user`), dan permission admin siswa `siswa.*` (bukan `users.*`).
- Tambah konvensi dari BATCH_PLAN: `app/Enums`, `app/DTOs`, `#[Table]`, subclass `BisnisException`.

## 3. B1-E · Admin: soal & konfigurasi (BE-17..20)

**Butuh:** B1-B untuk `SoalResource`/`SoalReviewResource`

- Grup rute `admin` (prefix `/api/admin`, middleware `auth:sanctum`, `active`, `role:Super Admin`) dibuat oleh paket admin yang merge duluan (B1-E atau B1-D). Role yang ada hanya `Super Admin` dan `siswa`; **jangan pakai `role:admin`**.
- **Soal**: materi dan cerita setingkat; pilihan ganda minimal 2 pilihan dan kunci ada di pilihan; isian tanpa pilihan; hapus = soft delete. Bila soal sudah dipakai pengerjaan, kunci/level/peruntukan/materi tidak boleh berubah (teks boleh). Taruh aturan ini di satu kelas (misalnya `SoalGuard`) karena **dipakai ulang di B2-D**.
- **Pembahasan**: satu per soal, upsert.
- **Latihan (quiz)**: satu per materi; `jumlah_soal >= latihan_min_soal`; hapus ditolak bila punya pengerjaan.
- **Simulasi**: `jumlah_soal`, `durasi_menit` > 0; hapus ditolak bila punya hasil. Guard `is_aktif` dipasang di B2-C.
- **Aturan pemetaan**: hanya ubah nilai; persen level berjumlah 100; bobot ≥ 1; passing_grade dan latihan_min_nilai 0–100; jumlah_materi_wajib ≤ jumlah materi; min_soal_per_materi × jumlah materi ≤ pretest_jumlah_soal.

## 4. B1-D · Admin: struktur konten & siswa (BE-12..14)

**Butuh:** B1-A

CRUD `/api/admin/*` per checklist ARCHITECTURE_RULES §10 untuk:

- **Tingkat**: hanya ubah nama/deskripsi; tidak ada store/destroy.
- **Kompetensi**: nama unik per tingkat; hapus ditolak bila punya materi (409).
- **Materi**: kompetensi harus dari tingkat yang sama; urutan unik per tingkat; hapus ditolak bila punya soal, latihan, atau dirujuk hasil siswa.
- **Cerita soal (konteks_soal)**: tingkat wajib; hapus ditolak bila dipakai soal.
- **Upload gambar materi**: `POST /api/admin/materi/gambar` (png/jpg/webp, maks 2 MB), balas path relatif tanpa domain.
- **Siswa**: pindahkan modul users lama dari `/api/users` ke `/api/admin/siswa` dan hapus rute lama (keputusan #7). Isinya list, nonaktifkan, dan soft delete. Pencabutan token sudah ada di `UserService` (B1-A); tinggal dipanggil.

## 5. B2-B · Materi, progress, latihan, syarat simulasi (BE-06, BE-07, BE-08)

**Butuh:** B1-A, B1-B, B1-C

- `GET /api/materi`, `GET /api/materi/{id}`, `PUT /api/materi/{id}/progress` (cek tingkat terbuka; tanpa putaran aktif → 409 `BELUM_PRETEST` lewat `BelumPretestException`; wajib dulu, urut prioritas).
- Latihan: mulai/lanjutkan, simpan jawaban, submit dengan pola dua transaksi; `LatihanService implements LatihanServiceInterface` dengan `selesaikanPenilaian(int $id)`; nilai terbaik = maksimum pengerjaan selesai.
- **Lengkapi `SyaratSimulasiService` yang kerangkanya sudah ada dari B1-A** (jangan buat kelas baru). Ikuti bentuk `SyaratSimulasiServiceInterface::periksa(User, TingkatSeleksi): SyaratSimulasi { terpenuhi, rincian }`. Tambah `GET /api/simulasi/syarat` dengan rincian per materi dan `latihan_belum_tersedia`.

**Test:** putaran aktif dibuat lewat factory, jadi tidak perlu menunggu B2-A milik Orang 1.

## 6. B2-C · Kecukupan bank soal & dashboard admin

**Butuh:** B1-B (fungsi kuota), B1-D, B1-E

- `BankSoalService` + `GET /api/admin/bank-soal/kecukupan?tingkat_id=` (5 bagian; ambang putaran pre-test < 2). Wajib memakai `KuotaLevel::hitung()` yang sama dengan SoalPicker.
- Guard `is_aktif` simulasi: hanya bisa dinyalakan bila bank cukup untuk satu percobaan.
- `GET /api/admin/dashboard`: siswa aktif, pengerjaan per jenis, siswa per tingkat aktif, rata-rata nilai per jenis.

## 7. B2-D · Impor konten (BE-21)

**Butuh:** B1-D, B1-E (`SoalGuard`). Kolom `id_sumber` (`string(100) nullable unique`) dan `soal.gambar` sudah ada di `dev`.

- `php artisan impor:konten {folder} --dry-run`: parse frontmatter Markdown materi, buat kompetensi bila belum ada, salin gambar materi dan soal ke storage publik, dedup konteks per tingkat, upsert lewat `id_sumber`, pembahasan.
- Validasi sesuai tabel pemeriksaan (id_sumber kembar → batal semua; lainnya → soal ditolak; perubahan terlarang pada soal terpakai → dilewati dan dilaporkan).
- Laporan: masuk, diperbarui, ditolak beserta alasan.

## 8. B3-B · Dashboard & riwayat siswa (BE-15, BE-16)

**Butuh:** B1-A, B2-B; bagian simulasi diisi setelah B3-A merge

- `DashboardService` menyusun data dari `PutaranService` + `SyaratSimulasiService` saja (tanpa hitungan sendiri).
- `GET /api/riwayat?jenis=&tingkat_id=` dari model `RiwayatHasil`, terbaru dulu, paginated.

## 9. B4 · Route check & dokumentasi (bersama Orang 1)

**Butuh:** semua paket

- `php artisan route:list --path=api`: semua rute siswa di balik `auth:sanctum` + `active`, rute admin di balik `role:Super Admin`/permission.
- `grep -rE "DB::|::where\(|::find\(" app/Http/Controllers` kosong.
- Update `PRD.md`, `IMPLEMENTATION_PLAN.md`, dan README (cara menjalankan Postgres, queue worker, scheduler).

---

## Keputusan yang kamu pegang

| # | Keputusan | Status |
| --- | --- | --- |
| 6 | Layanan Python (`/hitung/penilaian`, `/hitung/pretest`) di luar repo: siapa pemiliknya, dan contoh kontrak final untuk test kontrak. Sampai ada, bentuk array di kontrak B1-C menjadi acuan | Terbuka |
| 7 | Rute admin siswa: pindah `/api/users` → `/api/admin/siswa` di B1-D | Rekomendasi, sepakati dengan Orang 1 |
| 3 | Angka usulan: backoff job `[10,30,60,120,300]`, upload maks 2 MB, ambang peringatan bank 2 putaran | Selesai (lihat BATCH_PLAN) |
