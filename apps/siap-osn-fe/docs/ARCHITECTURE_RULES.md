# ARCHITECTURE RULES — siap-osn-fe

Aturan arsitektur + kontrak integrasi frontend ↔ backend.
Semua kode baru wajib mengikuti file ini.
Detail tooling Bun: `docs/PLAN-TOOLING-BUN.md`.
Rencana auth: `docs/PLAN-FE-VUE-AUTH.md`.

## 1. Lapisan arsitektur

```text
views/ / layouts/ / components/   (UI: render + validasi form + toast)
        ↓ memakai
router/ (routes, meta, guard)  +  stores/ (state, cache, dedupe request)
        ↓ memakai
services/<domain>.js  (satu fungsi = satu endpoint; unwrap envelope)
services/mappers/<entitas>.js  (respons backend -> bentuk FE, per entitas)
        ↓ memakai
lib/  = infrastruktur pendukung saja, bukan logika domain:
  lib/api.js        (satu-satunya axios instance: token, timeout, interceptor 401)
  lib/endpoints.js  (satu-satunya daftar path API)
  lib/errors.js     (satu-satunya cara membaca pesan error backend)
```

Aturan arah dependensi:

1. UI boleh memakai `stores/`, `services/`, `router/`, `lib/` (kecuali
   `lib/api.js`). Tidak boleh sebaliknya.
2. Hanya `services/` yang boleh import `lib/api.js` + `ENDPOINTS`.
   Store/view memanggil service, tidak pernah `api.get(...)` langsung.
   Dilarang `axios.create` / `fetch` langsung di luar `lib/api.js`.
3. Service menerima & mengembalikan **bentuk FE** (`nama`, `role`,
   camelCase): `unwrap()` envelope `{message, data}` dan mapping
   (`mapUser`, `mapDashboard` di `services/mappers/`) dilakukan di service,
   bukan di store/view. Mapper dinamai per entitas (`user`, `dashboard`)
   agar bisa dipakai ulang lintas service. Jangan menaruh mapper/logika
   domain di `lib/`.
4. Data yang dipakai lintas halaman disimpan di store, dengan:
   - dedupe request yang sedang berjalan (`inflight`),
   - cache singkat (lihat `SEGAR_MS` di `stores/progress.js`, `{ force: true }`
     untuk memaksa refresh),
   - `AbortController` di `$reset()` agar respons akun lama tidak masuk.
   Request sekali pakai (mis. tombol tes admin) boleh langsung memanggil
   service dari view.
5. `lib/api.js` **dilarang import statis** ke `router/` atau `stores/`
   (circular import). Interceptor memakai `await import()` lazy.
6. Satu konsep satu tempat: pemetaan role→dashboard hanya di
   `dashboardFor()` (`router/index.js`).
7. Halaman baru di `router/index.js` di-import lazy (`() => import(...)`)
   agar tidak menambah bundle awal; hanya `LandingPage` yang eager.

### 1.1 Tugas tiap lapisan

| Lapisan | Tugas | Dilarang |
|---|---|---|
| View / layout / component | Panggil store/service, render, toast error via `pesanError()` | `api.get(...)`, menulis path URL |
| Store | Data lintas halaman, dedupe request, cache, `$reset()` | Tahu bentuk respons backend |
| Service | Satu fungsi = satu endpoint, `unwrap()`, panggil mapper | Menyimpan state |
| Mapper | Respons backend → bentuk FE | Memanggil HTTP |
| `lib/` | Token, timeout, redirect 401, daftar path, baca error | Logika domain / mapper |

Prinsip: makin ke bawah makin "tahu backend", makin ke atas makin "tahu UI".
Backend ganti nama field → ubah mapper saja. Path berubah → ubah
`endpoints.js` saja. View dan store tidak ikut berubah.

### 1.2 Alur satu request

Contoh: siswa membuka Dashboard.

```text
REQUEST (turun)
DashboardView.vue    onMounted → progress.fetchDashboard()
stores/progress.js   request sedang jalan? → pakai yang sama
                     data masih segar (< SEGAR_MS)? → selesai, tanpa request
                     selain itu → siswaService.dashboard({ signal })
services/siswa.js    api.get(ENDPOINTS.siswa.dashboard)
lib/api.js           pasang Bearer token + Accept JSON + timeout → HTTP
                          ↓
                      Laravel GET /dashboard

RESPONSE (naik)
lib/api.js           401 sesi habis → reset auth + redirect /login
                     error lain → diteruskan ke atas
services/siswa.js    unwrap() → buang { message }, ambil `data`
                     mapDashboard() → snake_case jadi bentuk UI
stores/progress.js   sukses → simpan `data`, loaded = true
                     gagal  → error = true (data lama tetap tampil)
DashboardView.vue    render ulang otomatis (reaktif), sidebar ikut terbarui
```

Variasi:

- **Aksi sekali pakai** (submit form, tombol): store dilewati.
  `View → service → lib/api → backend → service → View (toast)`.
- **Aplikasi dibuka**: router guard → `auth.fetchMe()` →
  `authService.me()` → `GET /auth/me` → `mapUser()` → guard memutuskan
  lanjut / ke login / ke forbidden (lihat §3).

### 1.3 Menambah endpoint baru

Contoh: `GET /siswa/materi` untuk halaman Materi.

1. **Path** di `lib/endpoints.js`. Path berparameter memakai fungsi:

   ```js
   siswa: {
     materi: '/siswa/materi',
     materiDetail: (id) => `/siswa/materi/${id}`,
   },
   ```

2. **Mapper** di `services/mappers/<entitas>.js` (lewati bila bentuk
   backend sudah sama dengan yang dipakai UI):

   ```js
   export function mapMateri(r) {
     return { id: r.id, judul: r.judul ?? '', selesai: Boolean(r.is_selesai) }
   }
   ```

3. **Fungsi service** di `services/<domain>.js` (domain baru → file baru):

   ```js
   async materi({ signal } = {}) {
     const list = unwrap(await api.get(ENDPOINTS.siswa.materi, { signal }))
     return (list ?? []).map(mapMateri)
   },
   ```

4. **Pemanggil**:
   - Aksi sekali pakai → panggil service langsung dari view, error via
     `pesanError(err)` + toast.
   - Data dipakai banyak halaman → buat store dengan pola
     `stores/progress.js` (`inflight`, `SEGAR_MS`, `AbortController`).
     Bila datanya per user, panggil `$reset()`-nya di `login`, `logout`,
     dan `$reset` pada `stores/auth.js`.

5. **Test**: store → mock `@/services/<domain>`; service → mock
   `@/lib/api`. Lalu jalankan quality gate (§5).

Catatan: `unwrap()` hanya mengambil `res.data.data`. Untuk respons paginasi
Laravel (`{ data, meta, links }`) ambil `res.data` langsung di service agar
`meta` tidak hilang, mis. `{ items: data.map(mapX), total: meta.total }`.

Token, timeout, redirect 401, dan pesan error Indonesia sudah otomatis —
tidak perlu ditulis ulang di endpoint baru.

## 2. Kontrak integrasi dengan siap-osn-be (terverifikasi 2026-09-25)

Base URL dev: `http://localhost:8000/api` (Laravel `Osn-Readiness-Web`, langsung tanpa proxy Vite).

| Method + path | Auth | Sukses | Data |
|---|---|---|---|
| `POST /auth/register` | — | 201 "Registrasi berhasil" | `{user, token}` |
| `POST /auth/login` | — | 200 "Login berhasil" | `{user, token}` |
| `POST /auth/logout` | Bearer | 200 "Logout berhasil" | token dihapus |
| `GET /auth/me` | Bearer | 200 "OK" | user |
| `GET /admin/ping` | cookie + `super_admin` | 200, `data.message: "pong"` | — |

Aturan kontrak:

1. Envelope sukses Laravel: `{message, data}` (tanpa `success`).
   Error validasi: 422 + `{message, errors: {field: [...]}}` —
   dibaca via `pesanError()` (`lib/errors.js`), jangan parsing string manual.
2. Auth Laravel (Sanctum) memakai Bearer token (`data.token` saat login/
   register), disimpan di `localStorage` (`TOKEN_KEY` di `lib/api.js`)
   dan dikirim via interceptor. Bentuk user Laravel (`UserResource`:
   `name`, `roles[]`) dipetakan ke bentuk FE (`nama`, `role`) di
   `mapUser()` (`services/mappers/user.js`), dipanggil dari `services/auth.js`. Role: `siswa`, `Super Admin`→`super_admin`.
3. Setiap request wajib `withCredentials: true` (sudah default di `lib/api.js`).
4. Dev WAJIB di `http://localhost:5173` persis (bukan `127.0.0.1`, bukan port
   lain) — `strictPort: true`. Origin lain ditolak backend (403).
5. Role persis: `siswa`, `super_admin`. Role baru dari backend harus
   didaftarkan di `dashboardFor()` + `meta.role` sebelum dipakai.
6. Pesan yang ditempel ke UI: "Email sudah terdaftar" (400, di field email),
   "Email atau password salah" (401, toast), selebihnya via `pesanError()`.

## 3. Auth flow (jangan diubah tanpa alasan)

1. Guard: `fetchMe()` sekali per load → `requiresAuth` → `guestOnly` →
   cek `meta.role` → redirect `/` ke dashboard per role.
2. Interceptor 401: redirect ke `login` **kecuali** dari `/auth/me`,
   `/auth/login`, `/auth/logout`, dan kecuali posisi sudah di halaman login
   (anti-loop). Beberapa 401 bersamaan hanya memicu satu redirect.
3. `logout()` menelan error request — sesi lokal selalu dibersihkan
   agar user tetap kembali ke `/login`.
4. Register tidak auto-login — selalu redirect ke `/login` + toast sukses.

## 4. UI

1. PrimeVue **4.5.5** + `@primeuix/themes@2.0.3` + `primeicons@7.0.0`,
   dikunci via `overrides`. Dilarang `primevue@latest` (v5 komersial).
2. Styling: Tailwind v4 (`@import "tailwindcss"` di `assets/main.css`).
   Tanpa `tailwind.config.js`.
3. Semua teks UI Bahasa Indonesia.
4. Input form baru wajib pakai `components/FormField.vue`
   (label + error merah). Validasi manual di view, bukan vee-validate/zod.
5. Feedback error/sukses selalu via Toast (`life: 4000`), bukan `alert()`.

## 5. Tooling & quality gate

1. Package manager: Bun. `dev`/`build`/`preview` via `bunx --bun vite`.
   `test:unit` (`vitest run` + jsdom) dan lint jalan di Node — tanpa `--bun`.
2. Dilarang `bun test` untuk test komponen (tidak support SFC `.vue`).
3. Setiap perubahan wajib lolos sebelum commit:
   `bunx eslint . && bun run test:unit && bun run build`.
4. Test baru: logika store di `*.spec.js` (mock `@/lib/api` atau
   `@/services/<domain>`),
   aturan guard di `router/guard.spec.js` (tanpa backend).

## 6. Larangan ringkas

- Token di URL. ❌ (Token di `localStorage` + header `Authorization`
  diizinkan khusus untuk Bearer Sanctum — lihat §2.)
- `axios`/`fetch` di luar `lib/api.js`. ❌
- `api.get/post(...)` di store/view (wajib lewat `services/`). ❌
- Import statis `router`/`stores` dari `lib/`. ❌
- `primevue@latest`, `tailwind.config.js`, teks UI Inggris. ❌
- Route terproteksi tanpa `meta.requiresAuth`/`meta.role`. ❌
- `bunx --bun vitest`. ❌ (hang — lihat PLAN-TOOLING-BUN.md §5)
