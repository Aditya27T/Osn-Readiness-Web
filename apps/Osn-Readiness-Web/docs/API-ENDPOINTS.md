# Daftar Endpoint API

Semua endpoint berada di bawah `/api`. Jumlah: 73 endpoint (75 baris di bawah, karena login dan tambah soal masing-masing punya dua contoh), dicocokkan dengan `php artisan route:list --path=api` pada 4 Oktober 2026 (setelah perbaikan audit, branch `docs/pasca-audit`).

Koleksi Postman yang sama isinya ada di [postman/OSN-Readiness.postman_collection.json](../postman/OSN-Readiness.postman_collection.json). Aturan bisnis tiap endpoint dijelaskan di [Logic per fitur.md](Logic%20per%20fitur.md).

## Cara pakai

- **Header:** `Accept: application/json` di semua request, dan `Authorization: Bearer <token>` untuk endpoint yang perlu login.
- **Token:** didapat dari `POST /api/auth/register` atau `POST /api/auth/login` (`data.token`), berlaku 7 hari.
- **Siswa:** endpoint siswa butuh token dan akun aktif.
- **Admin:** endpoint `/api/admin/*` butuh token akun ber-role `Super Admin`; selain itu 403.
- **PUT atau PATCH:** endpoint ubah pada resource admin menerima keduanya; tabel hanya menulis PUT.

## Bentuk error

| Status | Bentuk | Kapan |
| --- | --- | --- |
| 401 | `{message}` | Tanpa token, token salah, atau token kedaluwarsa |
| 403 | `{message}` atau `{message, kode, detail}` | Bukan Super Admin, akun nonaktif, atau `TINGKAT_TERKUNCI` |
| 404 | `{message}` | Data tidak ada, atau milik siswa lain |
| 409, 502, 503 | `{message, kode, detail}` | Kesalahan bisnis; `kode` dipakai frontend untuk membedakan kasus |
| 422 | `{message, errors}` | Gagal validasi; `errors` berisi pesan per kolom |
| 429 | `{message}` | Login lebih dari 5 kali per menit untuk email dan IP yang sama |

Kode bisnis yang dipakai: `TINGKAT_TERKUNCI`, `SUDAH_LULUS`, `PUTARAN_MASIH_BERJALAN`, `BELUM_PRETEST`, `SUDAH_DISUBMIT`, `SYARAT_SIMULASI_BELUM_TERPENUHI`, `KUOTA_SIMULASI_HABIS`, `SIMULASI_BELUM_DINILAI`, `WAKTU_HABIS`, `BANK_SOAL_TIDAK_CUKUP`, `HASIL_SEDANG_DIPROSES`, `LAYANAN_HITUNG_SALAH_KONFIGURASI`, `KOMPETENSI_MASIH_DIGUNAKAN`, `MATERI_MASIH_DIGUNAKAN`, `KONTEKS_SOAL_MASIH_DIGUNAKAN`, `LATIHAN_MASIH_DIGUNAKAN`, `SIMULASI_MASIH_DIGUNAKAN`.

## Auth

Akses: Tanpa token.

| Method | Endpoint | Query / body | Keterangan |
| --- | --- | --- | --- |
| POST | `/api/auth/register` | Body: `name`, `email`, `password`, `password_confirmation` | Register. Membuat akun siswa. 201 + token. Email disimpan huruf kecil. |
| POST | `/api/auth/login` | Body: `email`, `password` | Login siswa. 200 + token. 401 bila salah, 403 bila akun nonaktif, 429 setelah 5 kali per menit. |
| POST | `/api/auth/login` | Body: `email`, `password` | Login admin. Login Super Admin; token disimpan ke admin_token. |

## Auth (perlu token)

Akses: Token (siswa atau admin).

| Method | Endpoint | Query / body | Keterangan |
| --- | --- | --- | --- |
| GET | `/api/auth/me` | — | Me. Profil, roles, dan tingkat_aktif_id. |
| PUT | `/api/auth/profile` | Body: `name`, `email` | Ubah profil sendiri. Email disimpan huruf kecil, unik di antara akun aktif kecuali milik sendiri. |
| POST | `/api/auth/logout` | — | Logout. Mencabut token yang sedang dipakai saja. |

## Siswa · Tingkat & dashboard

Akses: Token siswa.

| Method | Endpoint | Query / body | Keterangan |
| --- | --- | --- | --- |
| GET | `/api/tingkat` | — | Daftar tingkat. Dua tingkat beserta tingkat_terbuka dan tahap. |
| GET | `/api/dashboard` | — | Dashboard. Per tingkat: tahap, sisa kuota simulasi, syarat simulasi, hasil simulasi terakhir. |
| GET | `/api/riwayat` | Query: `jenis` (pretest, latihan, atau simulasi (opsional)), `tingkat_id` (opsional), `per_page` (1–100, opsional) | Riwayat. Riwayat hasil milik siswa, terbaru dulu, paginated. |

## Siswa · Pre-test

Akses: Token siswa.

| Method | Endpoint | Query / body | Keterangan |
| --- | --- | --- | --- |
| POST | `/api/pretest` | Body: `tingkat_id` | Mulai pre-test. 201 pre-test baru, 200 bila melanjutkan yang berjalan. 403 TINGKAT_TERKUNCI, 409 SUDAH_LULUS / PUTARAN_MASIH_BERJALAN, 503 BANK_SOAL_TIDAK_CUKUP. |
| GET | `/api/pretest/{pretest_id}` | — | Lihat pre-test. Soal dan jawaban tersimpan bila berjalan; hasil dan pemetaan bila selesai. Milik siswa lain 404. |
| PUT | `/api/pretest/{pretest_id}/jawaban` | Body: `soal_id`, `jawaban_user` | Simpan jawaban pre-test. 409 SUDAH_DISUBMIT setelah submit; 422 bila soal bukan bagian pre-test itu. |
| POST | `/api/pretest/{pretest_id}/submit` | — | Submit pre-test. Menilai dan memetakan materi. 503 HASIL_SEDANG_DIPROSES bila layanan hitung gagal (dinilai ulang lewat job). |

## Siswa · Materi & latihan

Akses: Token siswa.

| Method | Endpoint | Query / body | Keterangan |
| --- | --- | --- | --- |
| GET | `/api/materi` | Query: `tingkat_id` (tanpa ini memakai tingkat aktif siswa) | Daftar materi. Materi wajib lebih dulu (urut prioritas), dengan progress dan nilai latihan terbaik. Tiap baris memuat `quiz_id` (null bila latihan belum tersedia) untuk langsung bisa memanggil `POST /api/quiz/{quiz_id}/mulai`. 403 TINGKAT_TERKUNCI. |
| GET | `/api/materi/{materi_id}` | — | Detail materi. Isi materi. |
| PUT | `/api/materi/{materi_id}/progress` | Body: `status` | Perbarui progress. status: belajar atau selesai. 409 BELUM_PRETEST tanpa putaran aktif. |
| POST | `/api/quiz/{quiz_id}/mulai` | — | Mulai latihan. 201 baru, 200 melanjutkan. 409 BELUM_PRETEST, 503 BANK_SOAL_TIDAK_CUKUP. |
| GET | `/api/quiz-pengerjaan/{pengerjaan_id}` | — | Lihat pengerjaan latihan. Bentuk bersoal (`data.soal`) selama belum selesai dinilai; bentuk hasil (nilai + jawaban) bila `selesai_pada` terisi. |
| PUT | `/api/quiz-pengerjaan/{pengerjaan_id}/jawaban` | Body: `soal_id`, `jawaban_user` | Simpan jawaban latihan. 409 SUDAH_DISUBMIT setelah submit. |
| POST | `/api/quiz-pengerjaan/{pengerjaan_id}/submit` | — | Submit latihan. Menilai latihan. 503 HASIL_SEDANG_DIPROSES bila layanan hitung gagal. |

## Siswa · Simulasi

Akses: Token siswa.

| Method | Endpoint | Query / body | Keterangan |
| --- | --- | --- | --- |
| GET | `/api/simulasi/syarat` | Query: `tingkat_id` (wajib) | Syarat simulasi. terpenuhi, alasan (belum_pretest atau null), dan rincian per materi wajib. 403 TINGKAT_TERKUNCI. |
| GET | `/api/simulasi` | Query: `tingkat_id` (tanpa ini memakai tingkat aktif siswa) | Daftar simulasi. Simulasi aktif dan sisa kuota percobaan. |
| POST | `/api/simulasi/{simulasi_id}/mulai` | — | Mulai simulasi. 201 baru, 200 melanjutkan. 409 SYARAT_SIMULASI_BELUM_TERPENUHI (rincian di detail), SUDAH_LULUS, KUOTA_SIMULASI_HABIS, SIMULASI_BELUM_DINILAI. |
| GET | `/api/hasil-simulasi/{hasil_id}` | — | Lihat percobaan simulasi. Soal bila berjalan, hasil bila selesai. |
| PUT | `/api/hasil-simulasi/{hasil_id}/jawaban` | Body: `soal_id`, `jawaban_user` | Simpan jawaban simulasi. 409 WAKTU_HABIS setelah batas + 30 detik; 409 SUDAH_DISUBMIT setelah submit. |
| POST | `/api/hasil-simulasi/{hasil_id}/submit` | — | Submit simulasi. Menilai dan menerapkan aturan kelulusan. |
| GET | `/api/hasil-simulasi/{hasil_id}/review` | — | Review simulasi. Jawaban, kunci, dan pembahasan. 409 SIMULASI_BELUM_DINILAI bila belum dinilai. |

## Admin · Dashboard & bank soal

Akses: Token Super Admin.

| Method | Endpoint | Query / body | Keterangan |
| --- | --- | --- | --- |
| GET | `/api/admin/dashboard` | — | Dashboard admin. Siswa aktif, pengerjaan per jenis, siswa per tingkat aktif, rata-rata nilai per jenis. |
| GET | `/api/admin/bank-soal/kecukupan` | Query: `tingkat_id` (wajib) | Kecukupan bank soal. Lima bagian laporan kecukupan bank soal. |

## Admin · Tingkat & aturan pemetaan

Akses: Token Super Admin.

| Method | Endpoint | Query / body | Keterangan |
| --- | --- | --- | --- |
| GET | `/api/admin/tingkat` | — | Daftar tingkat. |
| GET | `/api/admin/tingkat/{tingkat_id}` | — | Detail tingkat. |
| PUT | `/api/admin/tingkat/{tingkat_id}` | Body: `nama_tingkat`, `deskripsi` | Ubah tingkat. Hanya nama dan deskripsi. Tingkat tidak bisa ditambah atau dihapus. |
| GET | `/api/admin/tingkat/{tingkat_id}/aturan-pemetaan` | — | Lihat aturan pemetaan. 16 parameter aturan satu tingkat. |
| PUT | `/api/admin/tingkat/{tingkat_id}/aturan-pemetaan` | Body: `bobot_mudah`, `bobot_sedang`, `bobot_sulit`, `pretest_jumlah_soal`, `pretest_persen_mudah`, `pretest_persen_sedang`, `pretest_persen_sulit`, `pretest_min_soal_per_materi`, `jumlah_materi_wajib`, `latihan_min_soal`, `latihan_min_nilai`, `simulasi_persen_mudah`, `simulasi_persen_sedang`, `simulasi_persen_sulit`, `simulasi_maks_percobaan`, `passing_grade` | Ubah aturan pemetaan. Semua parameter wajib dikirim. Tiga persen level pre-test dan simulasi masing-masing berjumlah 100. |

## Admin · Kompetensi

Akses: Token Super Admin.

| Method | Endpoint | Query / body | Keterangan |
| --- | --- | --- | --- |
| GET | `/api/admin/kompetensi` | — | Daftar kompetensi. |
| POST | `/api/admin/kompetensi` | Body: `tingkat_id`, `nama_kompetensi`, `deskripsi` | Tambah kompetensi. Nama unik per tingkat. |
| GET | `/api/admin/kompetensi/{kompetensi_id}` | — | Detail kompetensi. |
| PUT | `/api/admin/kompetensi/{kompetensi_id}` | Body: `nama_kompetensi` | Ubah kompetensi. |
| DELETE | `/api/admin/kompetensi/{kompetensi_id}` | — | Hapus kompetensi. 409 KOMPETENSI_MASIH_DIGUNAKAN bila masih punya materi. |

## Admin · Materi

Akses: Token Super Admin.

| Method | Endpoint | Query / body | Keterangan |
| --- | --- | --- | --- |
| GET | `/api/admin/materi` | Query: `kompetensi_id` (opsional) | Daftar materi. |
| POST | `/api/admin/materi` | Body: `tingkat_id`, `kompetensi_id`, `urutan`, `judul`, `deskripsi`, `isi_materi` | Tambah materi. Kompetensi harus setingkat; urutan unik per tingkat. |
| POST | `/api/admin/materi/gambar` | Form-data: `gambar` (file) | Unggah gambar materi. multipart/form-data, field gambar (png/jpg/webp, maks 2 MB). Membalas data.path tanpa domain untuk ditaruh di isi_materi. |
| GET | `/api/admin/materi/{materi_id}` | — | Detail materi. |
| PUT | `/api/admin/materi/{materi_id}` | Body: `judul` | Ubah materi. Boleh mengirim sebagian kolom. |
| DELETE | `/api/admin/materi/{materi_id}` | — | Hapus materi. 409 MATERI_MASIH_DIGUNAKAN bila punya soal, latihan, atau dirujuk hasil siswa. |

## Admin · Cerita soal (konteks)

Akses: Token Super Admin.

| Method | Endpoint | Query / body | Keterangan |
| --- | --- | --- | --- |
| GET | `/api/admin/konteks-soal` | — | Daftar cerita soal. |
| POST | `/api/admin/konteks-soal` | Body: `tingkat_id`, `judul`, `isi_konteks` | Tambah cerita soal. |
| GET | `/api/admin/konteks-soal/{konteks_id}` | — | Detail cerita soal. |
| PUT | `/api/admin/konteks-soal/{konteks_id}` | Body: `judul` | Ubah cerita soal. |
| DELETE | `/api/admin/konteks-soal/{konteks_id}` | — | Hapus cerita soal. 409 KONTEKS_SOAL_MASIH_DIGUNAKAN bila masih dipakai soal. |

## Admin · Soal & pembahasan

Akses: Token Super Admin.

| Method | Endpoint | Query / body | Keterangan |
| --- | --- | --- | --- |
| GET | `/api/admin/soal` | Query: `per_page` (opsional) | Daftar soal. Paginated, memuat kunci jawaban. |
| POST | `/api/admin/soal` | Body: `tingkat_id`, `materi_id`, `konteks_id`, `level`, `peruntukan`, `tipe_soal`, `pertanyaan`, `pilihan_jawaban`, `kunci_jawaban` | Tambah soal pilihan ganda. Pilihan berkunci huruf, kunci berupa hurufnya. Materi dan cerita harus setingkat. Gambar opsional lewat multipart. |
| POST | `/api/admin/soal` | Body: `tingkat_id`, `materi_id`, `level`, `peruntukan`, `tipe_soal`, `pertanyaan`, `kunci_jawaban` | Tambah soal isian. Soal isian tidak punya pilihan_jawaban. |
| GET | `/api/admin/soal/{soal_id}` | — | Detail soal. |
| PUT | `/api/admin/soal/{soal_id}` | Body: `pertanyaan` | Ubah soal. Boleh sebagian kolom. Soal yang sudah dipakai pengerjaan menolak perubahan kunci, level, peruntukan, dan materi (422); teks boleh. |
| DELETE | `/api/admin/soal/{soal_id}` | — | Hapus soal. Soft delete. |
| GET | `/api/admin/soal/{soal_id}/pembahasan` | — | Lihat pembahasan. 404 bila belum ada. |
| POST | `/api/admin/soal/{soal_id}/pembahasan` | Body: `isi_pembahasan` | Simpan pembahasan. Buat atau perbarui; satu soal satu pembahasan. |
| PUT | `/api/admin/soal/{soal_id}/pembahasan` | Body: `isi_pembahasan` | Ubah pembahasan. 404 bila belum ada. |
| DELETE | `/api/admin/soal/{soal_id}/pembahasan` | — | Hapus pembahasan. |

## Admin · Latihan

Akses: Token Super Admin.

| Method | Endpoint | Query / body | Keterangan |
| --- | --- | --- | --- |
| GET | `/api/admin/latihan` | Query: `per_page` (opsional) | Daftar latihan. |
| POST | `/api/admin/latihan` | Body: `materi_id`, `nama_quiz`, `deskripsi`, `jumlah_soal` | Tambah latihan. Satu latihan per materi; jumlah_soal minimal latihan_min_soal. |
| GET | `/api/admin/latihan/{quiz_id}` | — | Detail latihan. |
| PUT | `/api/admin/latihan/{quiz_id}` | Body: `nama_quiz`, `jumlah_soal` | Ubah latihan. |
| DELETE | `/api/admin/latihan/{quiz_id}` | — | Hapus latihan. 409 LATIHAN_MASIH_DIGUNAKAN bila sudah punya pengerjaan. |

## Admin · Simulasi

Akses: Token Super Admin.

| Method | Endpoint | Query / body | Keterangan |
| --- | --- | --- | --- |
| GET | `/api/admin/simulasi` | Query: `per_page` (opsional) | Daftar simulasi. Termasuk yang nonaktif. |
| POST | `/api/admin/simulasi` | Body: `tingkat_id`, `nama_simulasi`, `deskripsi`, `jumlah_soal`, `durasi_menit`, `is_aktif` | Tambah simulasi. is_aktif true hanya lolos bila bank soal cukup untuk satu percobaan (422 pada is_aktif). |
| GET | `/api/admin/simulasi/{simulasi_id}` | — | Detail simulasi. |
| PUT | `/api/admin/simulasi/{simulasi_id}` | Body: `nama_simulasi`, `jumlah_soal`, `durasi_menit`, `is_aktif` | Ubah simulasi. |
| DELETE | `/api/admin/simulasi/{simulasi_id}` | — | Hapus simulasi. 409 SIMULASI_MASIH_DIGUNAKAN bila sudah punya hasil. |

## Admin · Siswa

Akses: Token Super Admin.

| Method | Endpoint | Query / body | Keterangan |
| --- | --- | --- | --- |
| GET | `/api/admin/siswa` | Query: `per_page` (opsional) | Daftar siswa. Hanya akun ber-role siswa, aktif maupun nonaktif. |
| GET | `/api/admin/siswa/{siswa_id}` | — | Detail siswa. Akun admin dibalas 404. |
| PUT | `/api/admin/siswa/{siswa_id}` | Body: `name` | Ubah siswa. name, email, password (+ password_confirmation); semuanya opsional. |
| POST | `/api/admin/siswa/{siswa_id}/deactivate` | — | Nonaktifkan siswa. Mencabut semua token siswa itu. |
| DELETE | `/api/admin/siswa/{siswa_id}` | — | Hapus siswa. Soft delete dan mencabut semua token. |

## Contoh body soal

Pilihan ganda (pilihan berkunci huruf, kunci berupa hurufnya):

```json
{
  "tingkat_id": 1,
  "materi_id": 1,
  "level": "mudah",
  "peruntukan": "pretest",
  "tipe_soal": "pilihan_ganda",
  "pertanyaan": "Berapa hasil 1 + 1?",
  "pilihan_jawaban": {
    "A": "1",
    "B": "2",
    "C": "3",
    "D": "4"
  },
  "kunci_jawaban": "B"
}
```

Isian (tanpa `pilihan_jawaban`):

```json
{
  "tingkat_id": 1,
  "materi_id": 1,
  "level": "sedang",
  "peruntukan": "latihan",
  "tipe_soal": "isian",
  "pertanyaan": "Berapa hasil 2 + 2?",
  "kunci_jawaban": "4"
}
```

Nilai yang sah: `level` = mudah, sedang, sulit; `peruntukan` = pretest, latihan, simulasi; `tipe_soal` = pilihan_ganda, isian.
