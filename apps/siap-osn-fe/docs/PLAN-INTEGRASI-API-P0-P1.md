# PLAN INTEGRASI API — P0 + P1

> **Semua fase sudah selesai (6 Oktober 2026).** P0–P3 ketiganya sudah
> terpasang dan P4 sudah dikerjakan. Untuk pekerjaan lanjutan (perbaikan
> stabilitas, integrasi simulasi, halaman progress, admin, dan keputusan
> yang masih terbuka), rujuk `PLAN-INTEGRASI-LANJUTAN.md` di root workspace
> `bigdata/` (file itu berada di luar ketiga repo, jadi tidak bisa
> ditautkan dari sini).
>
> Status akhir per fase:
>
> | Fase | Isi | Status |
> | --- | --- | --- |
> | P0 | Fondasi: path dashboard, updateProfile | Selesai |
> | P1 | Pre-test + daftar tingkat | Selesai |
> | P2 | Dashboard penuh + riwayat | Selesai |
> | P3 | Materi + latihan | Selesai |
> | P4 | Simulasi penuh (syarat → mulai → submit → review) | Selesai |

Rencana integrasi frontend ↔ backend Laravel (`Osn-Readiness-Web`,
`http://localhost:8000/api`). Status riset: 2026-10-05.
Aturan arsitektur yang mengikat: `docs/ARCHITECTURE_RULES.md`
(lapisan view → store → service → mapper → `lib/`).

Keputusan pemilik produk (2026-10-05):
`updateProfile` ditambah di backend (FE tidak disentuh) · timer pre-test
90 menit dipertahankan · hasil submit tampil di `/siswa/pemetaan` ·
eksekusi P0 dulu, P1 setelah P0 merge · `tingkat_id` dipilih manual.

## 0. Fakta backend (hasil bedah repo, kontrak final)

- Prefix `/api` otomatis; auth Sanctum Bearer + middleware `active`.
- Envelope sukses ganda: `{message, data}` manual, atau bare `{data}`
  untuk endpoint yang me-return Resource langsung (`simulasi/syarat`,
  `materi*`, `riwayat` + paginasi `meta`). `kunci_jawaban` tidak pernah
  dikirim kecuali `hasil-simulasi/{id}/review`.
- Route model binding integer `id`; milik orang lain → 404 (bukan 403).
- Error bisnis: `{message, kode, detail}` — kode yang relevan:
  `TINGKAT_TERKUNCI`, `SUDAH_LULUS`, `PUTARAN_MASIH_BERJALAN`,
  `BANK_SOAL_TIDAK_CUKUP`, `SUDAH_DISUBMIT`, `HASIL_SEDANG_DIPROSES`,
  `SYARAT_SIMULASI_BELUM_TERPENUHI`, `KUOTA_SIMULASI_HABIS`, `WAKTU_HABIS`.
- Soal hanya 2 tipe: `pilihan_ganda` (satu opsi, `jawaban_user` string)
  dan `isian` (string ≤255, `null` = kosongkan). Plus `konteks` dan
  `gambar` opsional. Tidak ada tipe multi-select.
- Pre-test tidak punya batas waktu di backend; `GET pretest/{id}`
  bercabang dua (belum selesai → soal+`jawaban_user`, sudah selesai →
  hasil `pemetaan[]` + `materi_wajib[]`); `POST submit` body kosong,
  idempoten.
- Dashboard: `GET /api/dashboard` → `{tingkat_aktif_id, tingkat_aktif,
  tingkat: [{tingkat_id, nama_tingkat, urutan, tingkat_terbuka, tahap,
  sudah_lulus, sisa_kuota_simulasi, syarat_simulasi,
  hasil_simulasi_terakhir}]}`. `tahap`: BELUM_PRETEST | PRETEST_BERJALAN
  | BELAJAR | SIAP_SIMULASI | SIMULASI_BERJALAN | PUTARAN_HABIS | LULUS.
- Tidak ada endpoint update profil di API (hanya web `PATCH /profile`).

## 1. P0 — betulkan fondasi

### P0-1: Path dashboard salah (PR `fix/dashboard-endpoint`)

1. `lib/endpoints.js`: `siswa.dashboard` `'/siswa/dashboard'` → `'/dashboard'`.
2. Tulis ulang `services/mappers/dashboard.js` ke bentuk backend §0;
   turunkan flag selesai-pre-test dari `tahap` (selesai bila tahap ∈
   {BELAJAR, SIAP_SIMULASI, SIMULASI_BERJALAN, PUTARAN_HABIS, LULUS});
   simpan juga `tingkatAktifId` + daftar `tingkat` (dibutuhkan P1).
3. Sesuaikan `DashboardView.vue` (gating CTA pre-test, kartu ringkasan)
   ke bentuk FE baru; `markPreTestCompleted` tetap sebagai update
   optimistis + `fetchDashboard({ force: true })`.
4. Update `services/siswa.spec.js` + `stores/progress.spec.js`.
5. Gate: `bunx eslint . && bun run test:unit && bun run build`.

### P0-2: `updateProfile` — SELESAI TANPA UBAH FE (2026-10-05)

Backend ternyata sudah menyediakan `PUT|PATCH /api/auth/profile`
(`AuthController@updateProfile`, body `{name, email}`, balas
`{message: 'Profil berhasil diperbarui', data: UserResource}`) —
persis kontrak yang dipakai `services/auth.js`. Tidak ada perubahan FE.

## 2. P1 — integrasi pre-test + tingkat (setelah P0 merge)

Alur: pilih tingkat → `POST /api/pretest {tingkat_id}` (201 baru /
200 resume) → kerjakan + autosave `PUT .../jawaban` (debounce) →
kumpulkan `POST .../submit` → redirect `/siswa/pemetaan` (hasil).

1. **`lib/endpoints.js`**: `tingkat: '/tingkat'`,
   `pretestMulai: '/pretest'`, `pretest: (id) => ...`,
   `pretestJawaban: (id) => .../jawaban`, `pretestSubmit: (id) => .../submit`.
2. **`services/mappers/pretest.js`** (baru): `mapTingkat`, `mapSoal`
   (normalisasi `pilihan_jawaban` object → array opsi berurutan;
   teruskan `konteks`, `gambar`, `jawaban_user` untuk resume),
   `mapHasilPretest` (`nilai`, `pemetaan[]`, `materi_wajib[]`).
   Bentuk exact `pilihan_jawaban` dipastikan ke seeder/factory backend
   saat implementasi.
3. **`services/pretest.js`** (baru, domain baru): `mulai({ tingkatId })`,
   `lihat({ id })`, `simpanJawaban({ id, soalId, jawaban })`,
   `kumpulkan({ id })`. Tulis `services/pretest.spec.js` (mock `api`).
4. **`stores/pretest.js`** (baru, pola `progress.js`: `inflight`,
   `AbortController`, `$reset` di-wire ke login/logout/`$reset` auth):
   `status` idle/mengerjakan/mengumpulkan/selesai, `soal[]`, `hasil`;
   branching `GET show` (`data.soal` → mengerjakan, `data.pemetaan` →
   selesai); simpan `pretestId` di `localStorage` agar reload bisa
   resume. Tulis `stores/pretest.spec.js` (mock service).
5. **`PretestView.vue`** (lapisan data saja, styling dipertahankan):
   buang pool soal hardcoded → dari store; pemilih tingkat manual
   (`GET tingkat`, default tersorot `tingkat_aktif`, dari store
   progress); render `pilihan_ganda` (radio) + `isian` (input teks) +
   `konteks`/`gambar`; UI tipe `kompleks` dihapus (tidak ada di
   backend); autosave debounce ±800ms; timer 90 menit client-side
   tetap (auto-submit saat habis; backend tidak enforce);
   flag ragu-ragu tetap lokal; tombol kumpulkan → konfirmasi →
   `router.push({ name: 'siswa.pemetaan' })`.
6. **`PemetaanKompetensiView.vue`**: baca hasil dari `stores/pretest`
   (fallback `lihat({ id })` bila store kosong, mis. refresh langsung
   di URL); ganti angka hardcoded (`skor`, ringkasan, riwayat) dengan
   `nilai` + `pemetaan[]` + `materi_wajib[]`.
7. **`lib/errors.js`**: terjemahkan `kode` bisnis → Indonesia (daftar
   §0) tanpa ubah signature `pesanError`; tambah kasus `errors.spec.js`.
8. Gate §1.5 + PR `feat/pretest-integration` (checks CI hijau → merge).

## 3. Prasyarat coba langsung (dev)

Backend `php artisan serve` :8000 + DB seeded; `VITE_BYPASS_AUTH`
dimatikan (bypass tidak mengeluarkan token Sanctum); dev di
`http://localhost:5173` persis; `.env` tidak di-commit.

## 4. Di luar scope P0+P1 (berikutnya)

P2 dashboard penuh + riwayat (paginasi `meta`); P3 materi + quiz/latihan;
P4 alur simulasi full (syarat → mulai → kerjakan → submit → review).

**Semua empat sudah selesai** — lihat tabel status di bagian atas.
