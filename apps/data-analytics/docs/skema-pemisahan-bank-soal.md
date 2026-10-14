# Pemisahan Bank Soal: `soal_osn` JSON → Skema Normalisasi

Status: **draft** — menunggu persetujuan tim data sebelum dieksekusi.
Tanggal: 6 Oktober 2026.
Referensi: [`distribusi-bank-soal.md`](distribusi-bank-soal.md), ADR
[`0004`](adr/0004-layanan-memiliki-bank-konten.md).

Dokumen ini menjelaskan cara memecah satu file JSON bank soal menjadi enam tabel
PostgreSQL. Belum ada DDL — ini murni pemetaan dan alasannya.

---

## 1. Titik awal

Folder `soal_osn/soal_kabupaten_pembahasan/` berisi 21 file JSON, total **1.044
baris** (2006–2026). Tiap baris adalah satu soal dengan struktur berikut:

```json
{
  "soal": "Jika kode di atas dijalankan dengan n = 3, ...",
  "pilihan": {"a": "5", "b": "4", "c": "3"},
  "kode": "var x: longint; begin ... end.",
  "deskripsi_soal": "Pak Dengklek bermain puzzle klasik ...",
  "gambar_lokal": "../gambar_kabupaten/soal_1_kab_2024.png",
  "gambar_url": "https://...png",
  "jawaban_benar": "b",
  "pembahasan": "Untuk $n = 3$, total baris adalah ..."
}
```

Satu baris ini sebenarnyabringing empat jenis informasi berbeda: teks soal, aset
yang menempel padanya, jawabannya, dan klasifikasi. Semuanya sekarang di jammed ke
satu tabel `soal`.

---

## 2. Keadaan sekarang

```
soal_kabupaten_pembahasan/soal_kab_2012.json
        │  (1044 baris)
        ▼
┌────────────────────────────────┐   ┌─────────────────────┐
│ soal                           │   │ pembahasan          │
│   pertanyaan            text   │   │  isi_pembahasan    │
│   pilihan_jawaban     jsonb    │   └─────────────────────┘
│   kunci_jawaban     varchar    │        ▲
│   gambar          1 varchar    │        │ 1:1
│   level / peruntukan / tipe    │────────┘
└────────────────────────────────┘
```

Masalahnya:

| Masalah | Akibat |
|---|---|
| `kunci_jawaban varchar(255)` menunjuk label di dalam `jsonb` | tidak bisa jadi foreign key, tidak bisa ditegakkan |
| `kunci_jawaban` dipakai dua kali: label untuk PG, teks untuk isian | satu kolom, dua tipe data |
| `gambar varchar` hanya bisa 1 gambar | tidak bisa 2 gambar / diagram + tabel |
| Tidak ada tempat untuk `kode` | 218 soal kehilangan cuplikan programnya |
| `peruntukan varchar` menjawab "dipakai untuk apa", bukan "soal ini apa" | satu soal terkunci ke satu jenis tes selamanya |
| `konteks_soal` di-dedup dengan cocok teks persis | whitespace beda sedikit → konteks kembar |

---

## 3. Skema target

```
                    ⟨DALAM SATU BARIS JSON⟩
                              │
  ┌───────────────────────────┼────────────────────────────┐
  │                           │                            │
  ▼                           ▼                            ▼
soal                     ⟨Nol..N⟩                    ⟨Satu⟩

pertanyaan ─────┐   ┌──────────────┐          ┌──────────────┐
level           │   │ soal_pilihan │          │  soal_aset   │
id_sumber  ◄────┼───┤  label  A..E │          │  jenis       │
kunci_pilihan_id─┘  │  teks        │          │  path        │
  (FK, PG saja)    │  urutan      │          │  urutan      │
jawaban_isan       └──────────────┘          └──────────────┘
  (isian saja)          ▲                        ▲
                       │                        │
                       │ konteks_id (FK)        │ gambar_lokal
                       ▼                        │ (125 soal)
              ┌──────────────────┐              │
              │ konteks_soal     │──────────────┘
              │  isi_konteks  ◄──┼─ deskripsi_soal   (272 soal)
              │  kode         ◄──┼─ kode            (233 soal)
              │  hash_konten     │
              └──────────────────┘
                       ▲ 1 konteks : N soal
                       │
              dari ⟨FILE LAIN⟩, bukan JSON ini:
              id_sumber  ← <tahun> + nomor urut
              tingkat_id ← label di nama folder
              materi_id  ← label_materi_level.json
              level      ← label_materi_level.json
```

---

## 4. Pemetaan field

| JSON key | Non-null (kab) | → Target |
|---|---|---|
| `soal` | 1.043 | `soal.pertanyaan` |
| `pilihan` | 775 | `soal_pilihan` → `(soal_id, label, teks, urutan)` |
| `jawaban_benar` | 963 | PG → `soal.kunci_pilihan_id` (FK)<br>isian → `soal.jawaban_isan` |
| `kode` | 233 | `konteks_soal.kode` |
| `deskripsi_soal` | 272 | `konteks_soal.isi_konteks` |
| `gambar_lokal` | 125 | `soal_aset` → `(soal_id, jenis='gambar', path, urutan)` |
| `gambar_url` | 37 | fallback untuk unduhan, **tidak** disimpan sebagai URL |
| `pembahasan` | 981 | `pembahasan.isi_pembahasan` |
| — | 1.044 | `soal.id_sumber` = `kab-<tahun>-<urutan>` |

### Yang bukan dari JSON ini

| Perlu | Sumber |
|---|---|
| `id_sumber` | disintesis: `<tahun>` + nomor urut → `kab-2012-036` |
| `tingkat_id` | nama folder (`kabupaten`) |
| `materi_id` | `label_materi_level.json` → `materi.json` |
| `level` | `label_materi_level.json` |
| `peruntukan` | **tidak ada di sumber** → dihapus, diganti `paket_tes` |

---

## 5. Tiga keputusan yang sudah diambil

### 5.1 `kode` dan `deskripsi_soal` jadi satu konteks

233 soal punya `kode`; **104 di antaranya tidak punya `deskripsi_soal`** — jadi kode
adalah satu-satunya konteks yang mereka punya. Kalau dipisah dua tabel, 104 soal itu
jadi yatim dan tidak bisa dijawab. Keduanya masuk ke satu baris `konteks_soal`.

Dedup memakai `hash_konten` (sha256 dari `isi_konteks` + `kode`), bukan cocok teks
persis seperti `ImporKontenService.php:353`. Cara lama gagal kalau whitespace beda
sedikit, dan tidak ada unique index-nya sehingga impor paralel bisa membuat kembar.

### 5.2 101 soal punya kode tertanam di `pertanyaan`

Contoh `kab-2013-048`: `pertanyaan` berisi program Pascal mentah. Harus dipecah —
kode ke `konteks_soal.kode`, sisanya ke `pertanyaan`.

### 5.3 `kunci_jawaban` jadi foreign key

Satu kolom varchar sekarang menanggung dua bentuk. Setelah `pilihan` jadi tabel,
keduanya terpisah dan bisa ditegakkan lewat CHECK constraint.

---

## 6. Pembersihan saat migrasi

| Masalah | Angka (kab) | Aksi |
|---|---|---|
| `jawaban_benar` null | 63 | skip — tidak ada kunci |
| kunci tidak ada di `pilihan` | 54 | simpan ke `jawaban_isan` atau skip |
| `pilihan` < 2 | 5 | skip |
| `soal` kosong | 1 | skip |
| gambar lokal hilang | 16 | 10 punya `gambar_url` → unduh dulu<br>6 tanpa sumber → lewati aset |
| artefak `[cite: N]` | ±68% pembahasan | hapus saat insert |
| pseudo-tag `<X,Y>` | beberapa | escape → `&lt;X,Y&gt;` |

Perkiraan hasil akhir untuk kabupaten: **≈ 920 soal**, ~370 `soal_pilihan`,
~154 `konteks_soal`, ~109 `soal_aset`, ~920 `pembahasan`.

---

## 7. Yang **tidak** dipisah

### `tingkat_id` — denormalisasi yang disengaja

Kedua tim mengambil keputusan sama danindependen. `models.py:294` mengomentari
denormalisasi ini *on purpose* supaya filter `(tingkat, materi, level)` cukup satu
index. Di repo fullstack, `soal.tingkat_id` tidak pernah dibaca lewat join — selalu
jadi filter langsung yang dicakup dua index (`SoalRepository.php:26`,
`BankSoalService.php:36,41,69`). Menghilangkannya akan merusak picker dan kecukupan.

### `level` — satu kolom sudah benar

Level adalah properti intrinsik soal, dipakai sebagai kuota seleksi. Konsep
`level_target` vs `level_aktual` milik **siswa**, bukan soal — keduanya sudah benar
berada di `paket_tes_soal`, bukan di `soal`.

### `pertanyaan` — memang satu kolom

Toda pada satu baris; pemecahan hanya perlu jika nanti ada soal dengan beberapa
paragraf bernomor.

---

## 8. Tabel yang tidak berubah

| Tabel | Alasan |
|---|---|
| `materi` | sudah referenced lewat `id_sumber`, rapi |
| `kompetensi` | sudah terpisah rapi |
| `tingkat_seleksi` | 2 baris, sudah adequate |
| `pembahasan` | sudah 1:1 dengan `soal` — pemisahannya sudah benar |

---

## 9. Urutan pengerjaan

| # | Perubahan | Dampak aplikasi |
|---|---|---|
| 1 | `konteks_soal.kode` + `hash_konten`, isi 101 soal bermerek | kecil |
| 2 | Bersihkan `[cite: N]`, escape `<X,Y>` | nol |
| 3 | `soal_pilihan` + FK kunci | besar (frontend, service) |
| 4 | `paket_tes`, buang `soal.peruntukan` | **paling besar** |
| 5 | `soal_aset` | sedang |

Langkah 1–2 aman dan idempoten tanpa mengubah kontrak API, jadi cocok untuk skrip
pertama. Langkah 4 adalah yang termahal: `peruntukan` menyentuh delapan
service/controller plus `SoalGuard`, dan pemindahannya menyentuh picker, kecukupan,
dan seluruh tipe respons.

---

## 10. Yang belum diputuskan

- **Bahasa skrip migrasi** — SQL murni, atau revision Alembic di `data-analytics`?
- **Database tujuan** — `analytics` (SQLAlchemy) atau `osn_readiness` (Laravel)?
  Keduanya punya tabel `soal` dengan bentuk berbeda.
- **Feature flag** — apakah langkah 3–5 dijalankan bertahap dengan kolom lama
  dipertahankan, atau sekali jalan?
- **234 soal tanpa label** di `label_materi_level.json` — apakah tetap skipped atau
  perlu dilabeli manual lebih dulu?