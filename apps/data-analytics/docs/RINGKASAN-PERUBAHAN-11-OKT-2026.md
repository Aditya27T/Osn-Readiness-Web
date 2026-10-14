# Ringkasan Perubahan — 11 Oktober 2026

Catatan apa yang berubah di repo `data-analytics` (layanan hitung Python) pada
sesi 11 Oktober 2026.

---

## 1. Yang ter-commit

Pada sesi ini hanya `.gitignore` yang diubah:

```diff
+# Override Docker Compose lokal (port dev bentrok dengan stack Laravel)
+docker-compose.override.yml
```

Alasannya supaya override lokal di bagian 3 tidak ikut ter-commit.

---

## 2. Berkas untracked yang masih tertinggal

Satu-satunya perubahan **isi** repo ini pada 11 Oktober adalah folder `soal/`
yang baru dibuat. Folder tersebut **belum di-commit**:

```
?? docs/impor-bank-soal-laravel.md     panduan impor ke Laravel
?? docs/skema-pemisahan-bank-soal.md   draft skema normalisasi (draft)
?? scripts/susun_folder_impor.py       skrip penjembatan format
?? soal/                               8,7 MB -- paket impor terbaru
?? soal_osn/impor/                     7,8 MB -- paket impor sebelumnya
```

### Perbedaan `soal/` dan `soal_osn/impor/`

Keduanya hasil-packed yang sama, tapi `soal/` lebih lengkap — dua berkas
tambahan: `soal_kab_2008.json` dan `soal_prov_2021.json`.

| | `soal/impor/` | `soal_osn/impor/` |
| --- | --- | --- |
| Kabupaten lolos | **922** | 878 |
| Provinsi lolos | 734 | 734 |
| Total | 1.656 | 1.612 |

`docs/impor-bank-soal-laravel.md` sudah menulis path `soal/impor/` sebagai
sumber yang benar. Yang dipakai sesi ini juga `soal/impor/`.

### Kenapa ini penting

`soal_osn/impor/` masih jadi sumber **impor lama** yang sudah masuk database
backend dengan skema `id_sumber` berbeda (`kab-2012-036`), sedangkan impor
baru memakai `kab_soal*`. Keduanya tidak terlihat dobel bagi database, tapi
**678 soal ada dua kali** dengan teks identik. Detailnya di
`Osn-Readiness-Web/docs/RINGKASAN-PERUBAHAN-11-OKT-2026.md` bagian 2.

`soal_osn/soal_kabupaten_pembahasan/` yang jadi sumber mentah **sudah**
ter-commit (284 berkas). Tidak ada alasan menyimpan hasil turunannya sebagai
untracked.

---

## 3. Konfigurasi lokal (tidak ter-commit)

### `docker-compose.override.yml`

Dibuat karena `docker-compose.dev.yml` **bentrok** dengan stack Laravel:

| Service | Port di dev | Dipakai oleh | Konflik |
| --- | --- | --- | --- |
| `analytics-api` | 8000 | `APP_PORT` Laravel | ya |
| `analytics-db` | 5432 | `FORWARD_DB_PORT` Laravel | ya |

Isi override:

```yaml
services:
  analytics-api:
    ports: !override
      - "8001:8000"

  analytics-db:
    ports: !override
      - "5433:5432"

networks:
  app-network:
    external: true
```

`!override` wajib dipakai — Compose v5 **meny_append** daftar `ports`, bukan
menggantinya. Tanpa tag itu mapping dev ikut terbawa dan tetap bentrok.

Port 5433 juga dipakai `Makefile` untuk `analytics-test-db`, jadi jangan
nyalakan keduanya bersamaan.

### `app-network`

Dibuat manual sesuai instruksi `docker-compose.yml:7`:

```bash
docker network create app-network
```

Stack Laravel perlu bergabung ke network ini juga — diatur lewat
`docker-compose.override.yml` di repo backend.

### Cara menyalakan

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml \
               -f docker-compose.override.yml up -d --build
```

---

## 4. Hashtag jembatan ke backend

`PERHITUNGAN_URL` di backend **tidak** boleh `localhost:8001`.

Backend berjalan di container, jadi `localhost` di dalamnya berarti dirinya
sendiri, bukan host. Yang dipakai adalah DNS internal Docker:

```
PERHITUNGAN_URL=http://analytics-api:8000
```

Ini persis desain yang ditulis di komentar `docker-compose.yml:4`.

Verifikasi token di kedua sisi cocok:

| Lokasi | Kunci |
| --- | --- |
| `Osn-Readiness-Web/.env` → `PERHITUNGAN_TOKEN` | `3a29f11717cb...` |
| `data-analytics/.env` → `INTERNAL_API_TOKEN` | `3a29f11717cb...` |

Guard token terbukti aktif — request dengan token salah dibalas
`403 token internal tidak valid`.

---

## 5. Verifikasi

| Yang dicek | Hasil |
| --- | --- |
| `GET http://localhost:8001/health` | `{"status":"ok"}`, 200 |
| `POST /hitung/penilaian` token salah | 403 — guard hidup |
| Laravel → `analytics-api` → `hitungPenilaian` | `nilai: 100`, 3 jawaban benar |
| `analytics-api` container | healthy |
| `analytics-db` container | healthy |
| `.venv/bin/python -m pytest -q` | **109/109 lulus** |

---

## 6. Skema normalisasi (draft, belum dieksekusi)

`docs/skema-pemisahan-bank-soal.md` masih berstatus **draft** dan belum ada
DDL-nya. Empat pertanyaannya di bagian 10 masih terbuka:

- Bahasa skrip migrasi — SQL murni atau revision Alembic?
- Database tujuan — `analytics` atau `osn_readiness`?
- Feature flag — bertahap atau sekali jalan?
- 213 soal tanpa label di `label_materi_level.json` — dilewati atau dilabeli?

### Angka di draft yang tidak sinkron dengan `laporan.json`

| Pernyataan | Draft | Laporan |
| --- | --- | --- |
| "234 soal tanpa label" | 234 | **213** |
| "Perkiraan hasil akhir ≈ 920 soal" | ≈920 | **878** (folder lama) / **922** (folder baru) |

Perlu diselaraskan sebelum draft dipakai sebagai acuan.

### `peruntukan` direkayasa

`scripts/susun_folder_impor.py` menurunkan `peruntukan` dari tahun karena
sumber tidak punya kolom itu — <=2012 `pretest`, 2013–2019 `latihan`, 2020+
`simulasi`. Tapi draft skema justru memutuskan `peruntukan` **dihapus** dan
diganti `paket_tes`.

Selama migrasi belum jalan, database berisi klasifikasi yang direkayasa.
Jangan dipakai sebagai dasar keputusan kurikulum atau analitik.

---

## 7. Berkas lain

`test_bobot.py` ter-commit di root repo sejak sesi sebelumnya dan terlihat
seperti skrip percobaan. Sebaiknya dipindahkan ke `scripts/` atau dihapus.
Tidak disentuh pada sesi ini.

---

*Dokumen ini ditulis pada 11 Oktober 2026.*