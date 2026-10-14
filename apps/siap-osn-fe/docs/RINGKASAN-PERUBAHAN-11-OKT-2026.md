# Ringkasan Perubahan — 11 Oktober 2026

Catatan apa yang berubah di repo `siap-osn-fe` (frontend Vue 3) pada sesi
11 Oktober 2026.

---

## 1. Yang ter-commit

**Tidak ada.** Branch `dev` sudah sinkron dengan `origin/dev`, tidak ada commit
baru yang dibuat pada sesi ini.

Perubahan kode yang ada di working tree berasal dari sesi sebelumnya dan
belum DecideBAcommit:

```
 M src/views/siswa/PretestView.vue   (+9 baris)
```

Menambah computed `waktuTampil` untuk format durasi `mm:ss`. Perubahan ini
sudah lengkap — dipakai di template baris 312 — jadi tinggal commit, bukan
pekerjaan setengah. Belum ada test yang menyertainya.

---

## 2. Perbaikan yang paling penting: `.env`

Ini penyebab utama "Tidak dapat terhubung ke server. Periksa koneksi internet."

Isi `.env` **sebelum** perbaikan:

```
# VITE_API_BASE_URL=http://127.0.0.1:8000/api
VITE_API_BASE_URL=localhost:8000/api        ← aktif, skema http:// hilang
```

Isi **sesudah** perbaikan:

```
# VITE_API_BASE_URL=http://127.0.0.1:8000/api
VITE_API_BASE_URL=http://localhost:8000/api
```

`localhost:8000/api` tanpa skema `http://` diperlakukan axios sebagai **path
relatif**, bukan URL absolut. Request tidak pernah keluar ke jaringan, hanya
mencoba fetch ke origin Vite sendiri.

Karena request-nya tidak pernah sampai ke backend, **tidak ada apa pun yang
muncul di log Laravel**. Itu sebabnya gejalanya tidak bisa didiagnosis dari sisi
server — perlu Dicek `.env` frontend.

`.env` ini di-gitignore, jadi perbaikannya tidak ikut ter-commit. Kalau repo
ini disalin ke mesin lain, masalah yang sama akan muncul lagi.

### Setelah mengubah `.env`

Vite hanya membaca env saat start. Restart wajib:

```bash
pkill -f vite
npm run dev
```

---

## 3. Verifikasi

| Yang dicek | Hasil |
| --- | --- |
| `GET http://localhost:5173/` | 200 |
| `POST /api/auth/login` ke `localhost:8000` | 200, token terbit |
| Preflight CORS dari origin FE | 204, `Access-Control-Allow-Origin` benar |
| `npx vitest run` | **169/169 lulus**, 22 file |
| `npx oxlint .` + `npx eslint .` | bersih |

---

## 4. Vite hanya bind ke IPv6

```
bun  TCP [::1]:5173 (LISTEN)
```

Hanya `[::1]` — artinya **hanya** `http://localhost:5173` yang tercapai.

Tidak akan sampai:

- `http://127.0.0.1:5173`
- alamat IP LAN, mis. `http://192.168.x.x:5173` (mis. untuk buka di HP)

Kalau perlu diakses perangkat lain:

```bash
npm run dev -- --host
```

---

## 5. Port 5173 sempat dikunci container

Vite gagal start dengan:

```
Port 5173 is in use on a wildcard address, but localhost:5173 is available.
```

Penyebabnya bukan proses lain, tapi container `laravel.test` milik backend
sendiri: `compose.yaml` memetakan `${VITE_PORT:-5173}:5173` ke service itu
padahal Sail tidak menjalankan Vite di dalam container. Sudah diperbaiki di
repo backend lewat `docker-compose.override.yml` — lihat
`Osn-Readiness-Web/docs/RINGKASAN-PERUBAHAN-11-OKT-2026.md` bagian 3.

---

## 6. Berkas untracked

Tidak ikut dikommit pada sesi ini:

| Berkas | Catatan |
| --- | --- |
| `docs/image.png` | 116 KB, kemungkinan tangkapan layar |
| `test-results/` | artefak Playwright (`last-run.json`) |

Keduanya lebih baik masuk `.gitignore` daripada dikommit. `test-results/`
sudah masuk `.gitignore` di repo backend, tapi belum di sini.

---

## 7. Warning autofocus (belum ditangani)

Browser sometimes logged:

```
Autofocus processing was blocked because a document already has a focused element.
```

Sudah ditelusuri: **nol** kemunculan `autofocus` di `src/`. Sumbernya
kemungkinan besar `FocusTrap` milik PrimeVue `Dialog` yang dipakai di empat
halaman admin:

- `src/views/admin/UjianView.vue`
- `src/views/admin/KontenView.vue`
- `src/views/admin/SiswaView.vue`
- `src/views/admin/SoalView.vue`

Saat dialog dibuka, `FocusTrap` memindahkan fokus ke elemen fokus pertama
s sementara tombol pemicu masih memegang fokus. Browser menolak, dialog tetap
normal. Tidak merusak apa pun.

Kalau ingin dibersihkan, cara resmi PrimeVue:

```vue
<Dialog :focusOnShow="false" ...>
```

Tidak diterapkan sebelum tahu halaman mana yang benar-benar bermasalah,
karena memindahkan fokus ke dalam dialog justru perilaku yang baik untuk
aksesibilitas, dan mengubahnya ada risikonya.

---

## 8. Belum dikerjakan

- **`dev` dan `main` bercabang.** Titik divergensi `992521c`; `dev` punya 3
  commit unik, `main` punya 25. Tidak ada yang superset satu sama lain.
  `main` punya suite e2e Playwright, `dev` punya `src/lib/pretest.js` beserta
  spec-nya. Perlu diputuskan garis mana yang menang sebelum merge.
- **`PretestView.vue`** lokal belum di-commit (bagian 1).
- Warning autofocus belum diputuskan (bagian 7).

---

*Dokumen ini ditulis pada 11 Oktober 2026.*