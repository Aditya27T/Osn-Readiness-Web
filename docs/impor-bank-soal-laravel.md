# Impor Bank Soal ke Laravel

Sumber konten dibagi dengan sengaja:

- `soal/` adalah bank soal terbaru dari branch Fakhri;
- `Materi/` adalah dokumen DOCX materi dari branch `integrasi`;
- `scripts/susun_folder_impor.py` menggabungkan keduanya menjadi paket yang
  dipahami backend Laravel.

Jangan menjalankan `seed_dev.py` untuk bank OSN. Skrip itu data contoh layanan
Python, bukan sumber konten Laravel.

## Buat paket impor

Dari direktori `data-analytics`:

```bash
.venv/bin/python scripts/susun_folder_impor.py
```

Keluaran ada di `soal/impor/` dan memuat Markdown materi, JSON soal, gambar,
serta `laporan.json`. Skrip memetakan dokumen Materi ke katalog soal lewat
pasangan `(tingkat, topik)`, bukan lewat ID lama. Ia juga meratakan soal cerita
menjadi satu baris per subsoal.

`laporan.json` wajib ditinjau. Butir dengan gambar yang tidak tersedia dan soal
pemrograman yang jawaban benarnya berupa source code dikecualikan; aplikasi
Laravel belum dapat menilai jawaban kode secara aman.

## Validasi dan impor

Dari direktori `Osn-Readiness-Web`:

```bash
php artisan impor:konten ../data-analytics/soal/impor --dry-run
php artisan impor:konten ../data-analytics/soal/impor --tingkat=kabupaten
php artisan impor:konten ../data-analytics/soal/impor --tingkat=provinsi
```

Selalu jalankan `--dry-run` lebih dahulu dan pastikan jumlah soal ditolak nol.
Perintah impor memakai `id_sumber` sehingga dapat dijalankan ulang: data yang
belum dipakai akan diperbarui, sedangkan perubahan metadata soal yang sudah
dipakai akan dilaporkan dan tidak ditimpa.

## Kebijakan sementara `peruntukan`

Sumber soal tidak memuat kategori `pretest`, `latihan`, atau `simulasi`.
Transformer saat ini memakai tahun sebagai kebijakan transisi:

- sampai 2012: `pretest`;
- 2013–2019: `latihan`;
- 2020 ke atas: `simulasi`.

Ini bukan klasifikasi intrinsik soal. Ganti dengan manifest per soal sebelum
menggunakan kategori tersebut sebagai keputusan kurikulum atau analitik.
