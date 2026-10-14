# Plan: Setup Frontend Vue + Integrasi Auth — SIAP OSN

## Context

Backend `siap-osn-be` sudah punya module auth yang bekerja dengan **cookie httpOnly** (`siap_osn_token`), endpoint `register`, `login`, `logout`, `me`, CORS dengan credentials, dan penjaga CSRF berbasis header `Origin`. Belum ada frontend sama sekali.

Tim frontend baru beralih dari Laravel, jadi plan ini memilih jalur belajar paling landai: **Vue 3 + Vite, JavaScript (bukan TypeScript), Vue Router, Pinia, Axios, Tailwind CSS v4 + PrimeVue 4**. Tampilan cukup seadanya: halaman login, register, dashboard sesuai role, dan layout dasar. Fokusnya adalah fondasi project dan alur auth yang benar, bukan visual.

**Keputusan yang sudah disepakati user:**
- Project di **repo terpisah** `siap-osn-fe`, folder sejajar dengan `siap-osn-be` (`/Volumes/PS50U/siap-osn-fe`).
- **JavaScript**, migrasi ke TypeScript bisa menyusul.
- **Tailwind + PrimeVue** sejak awal.
- **Tooling: Bun** (package manager + runtime dev/build). Detail tooling di `docs/PLAN-TOOLING-BUN.md`.
- Saat development frontend memanggil **langsung `http://localhost:8080`** (CORS + `withCredentials`), bukan lewat proxy Vite. Ini meniru kondisi production.

**Prasyarat di backend (kerjakan dulu):**
- Perubahan cookie di `siap-osn-be` (`pkg/authcookie`, `OriginGuard`, endpoint `/auth/me`, env `CORS_ALLOW_ORIGINS` dkk.) **masih belum di-commit**. Commit dulu agar kontrak tidak bergeser.
- Backend berjalan dengan `CORS_ALLOW_ORIGINS=http://localhost:5173` (sudah default) dan `SUPER_ADMIN_*` diisi supaya dashboard admin bisa diuji.

---

## Temuan verifikasi (npm registry, 2026-09-25)

| Paket | Versi dipakai | Catatan |
|---|---|---|
| vue | ^3.5 | Jangan pilih opsi "Vue 3.6 RC" di scaffold. |
| vite + @vitejs/plugin-vue | ^8.2 / ^6.0 | Dari create-vue. |
| vue-router | ^5.3 | API `createRouter`, `beforeEach`, `meta` sama dengan v4. |
| pinia | ^4.0 | **ESM-only dan wajib install `@vue/devtools-api` manual** (peer dependency). |
| axios | ^1.20 | |
| tailwindcss + @tailwindcss/vite | ^4.3 | Cukup plugin Vite + `@import "tailwindcss"`, **tanpa `tailwind.config.js`**. |
| **primevue** | **4.5.5 (dist-tag `v4-stable`)** | **PrimeVue 5.x berlisensi komersial (PrimeUI License, tidak untuk universitas/instansi publik). Versi 4 tetap MIT selamanya. Jangan pakai `primevue@latest`.** |
| @primeuix/themes | **2.0.3** | Versi 3.x ikut lisensi komersial. Preset Aura. |
| primeicons | **7.0.0** | Versi 8.x ikut lisensi komersial. |
| tailwindcss-primeui | ^0.6 | MIT. Diaktifkan lewat `@import "tailwindcss-primeui"` di CSS. |
| vitest + @vue/test-utils + jsdom | dari create-vue | |
| eslint + eslint-plugin-vue + prettier | dari create-vue | |

- create-vue 3.24 via `bun create vue@latest`. Mesin ini: Bun 1.4.2 + Node 24.14.1 (fallback untuk vitest/eslint). OK.
- Validasi form: **manual saja** (tiga field). vee-validate + zod punya konflik peer dependency saat ini dan menambah konsep baru.
- KaTeX **belum** dipasang di tahap ini; masuk saat modul soal.

**Cookie lintas port sudah dipastikan aman:** `localhost:5173` dan `localhost:8080` adalah origin berbeda tetapi **site yang sama** (port diabaikan), jadi `SameSite=Lax` tetap mengirim cookie pada request XHR. Syaratnya: setiap request pakai `withCredentials: true`, frontend berjalan tepat di `http://localhost:5173` (bukan `127.0.0.1`, bukan port lain), dan backend sudah menjawab `Access-Control-Allow-Origin` spesifik + `Access-Control-Allow-Credentials: true` (sudah ada).

---

## Struktur project `siap-osn-fe`

```text
siap-osn-fe/
├── .env.example               # VITE_API_BASE_URL=http://localhost:8080/api/v1
├── .env                       # tidak di-commit
├── index.html
├── vite.config.js             # plugin vue + tailwind, alias @, port 5173 strictPort
├── eslint.config.js, .prettierrc.json   # dari create-vue
├── vitest.config.js
├── package.json
├── README.md
└── src/
    ├── main.js                # createApp, Pinia, Router, PrimeVue(Aura), ToastService
    ├── App.vue                # <Toast /> + <RouterView />
    ├── assets/main.css        # @import tailwind, tailwindcss-primeui, primeicons
    ├── lib/
    │   ├── api.js             # instance axios (baseURL, withCredentials, interceptor 401)
    │   └── errors.js          # ambil pesan Bahasa Indonesia dari envelope error
    ├── stores/
    │   ├── auth.js            # user, initialized, isAuthenticated, fetchMe/login/register/logout
    │   └── auth.spec.js
    ├── router/
    │   ├── index.js           # routes + meta.requiresAuth / meta.role + guard
    │   └── guard.spec.js
    ├── layouts/
    │   ├── AuthLayout.vue     # kartu di tengah untuk login/register
    │   └── AppLayout.vue      # header (nama user, role, tombol logout) + slot konten
    ├── views/
    │   ├── LoginView.vue
    │   ├── RegisterView.vue
    │   ├── siswa/DashboardView.vue
    │   ├── admin/DashboardView.vue
    │   ├── ForbiddenView.vue
    │   └── NotFoundView.vue
    └── components/
        └── FormField.vue      # label + input + pesan error (dipakai login/register)
```

Analogi untuk tim Laravel: `router/index.js` ≈ `routes/web.php`, guard `beforeEach` ≈ middleware `auth` dan cek role, `stores/auth.js` ≈ `Auth::user()` di sisi browser, `lib/api.js` ≈ `Http::withOptions([...])` yang dipakai bersama, `views/` ≈ Blade view, `layouts/` ≈ `layouts/app.blade.php`.

---

## Langkah implementasi (urut)

### 1. Scaffold & dependency (Bun)

```bash
cd /Volumes/PS50U
bunx create-vue@latest siap-osn-fe-tmp --router --pinia --vitest --eslint --prettier --bare
cp -R siap-osn-fe-tmp/. siap-osn-fe/   # docs/ tetap, file scaffold menyusul
rm -rf siap-osn-fe-tmp
cd siap-osn-fe
bun install
bun add axios @vue/devtools-api
bun add primevue@4.5.5 @primeuix/themes@2.0.3 primeicons@7.0.0 tailwindcss-primeui
bun add -d tailwindcss @tailwindcss/vite
git init && git add -A && git commit -m "chore: scaffold vue project with bun"
```

`--bare` menghilangkan contoh komponen bawaan. Jangan pilih TypeScript. Jika prompt interaktif tetap muncul, jawab sesuai flag di atas dan tolak "Vue 3.6 RC". Scaffold ke folder `-tmp` dulu karena `siap-osn-fe/` sudah berisi `docs/` (`create-vue` menolak folder non-empty). Hasilkan `bun.lock` (di-commit). Bila postinstall diblok, jalankan sekali `bun install --trust`. Detail tooling lihat `docs/PLAN-TOOLING-BUN.md`.

`scripts` + `overrides` di `package.json`:

```json
{
  "scripts": {
    "dev": "bunx --bun vite",
    "build": "bunx --bun vite build",
    "preview": "bunx --bun vite preview",
    "test:unit": "vitest run",
    "lint": "eslint . --fix",
    "format": "prettier --write src/"
  },
  "overrides": {
    "primevue": "4.5.5",
    "@primeuix/themes": "2.0.3",
    "primeicons": "7.0.0"
  }
}
```

`--bun` hanya untuk `dev`/`build`/`preview` (runtime Bun, lebih cepat). `test:unit`/`lint` tanpa `--bun` (runtime Node).

### 2. Konfigurasi dasar

- **`vite.config.js`**: tambah `tailwindcss()` dari `@tailwindcss/vite` ke `plugins`, dan `server: { port: 5173, strictPort: true }` supaya Vite gagal (bukan pindah ke 5174) kalau port terpakai. Origin lain tidak lolos CORS backend.
- **`src/assets/main.css`** (ganti isi bawaan):
  ```css
  @import "tailwindcss";
  @import "tailwindcss-primeui";
  @import "primeicons/primeicons.css";
  ```
- **`.env.example`** dan `.env`: `VITE_API_BASE_URL=http://localhost:8080/api/v1`. Tambah `.env` ke `.gitignore` (create-vue sudah mengabaikan `.env.local`; pastikan `.env` juga). Commit `bun.lock`, ignore `node_modules/` dan `dist/`.
- **`src/main.js`**:
  ```js
  import PrimeVue from 'primevue/config'
  import Aura from '@primeuix/themes/aura'
  import ToastService from 'primevue/toastservice'
  app.use(createPinia()).use(router)
  app.use(PrimeVue, { theme: { preset: Aura } })
  app.use(ToastService)
  ```
- **`src/App.vue`**: `<Toast />` (import dari `primevue/toast`) + `<RouterView />`.

### 3. Klien API — `src/lib/api.js` dan `src/lib/errors.js`

```js
// api.js
export const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  withCredentials: true,           // wajib: kirim & terima cookie lintas origin
})
api.interceptors.response.use(
  (res) => res,
  (err) => {
    const status = err.response?.status
    const url = err.config?.url ?? ''
    const onLoginPage = router.currentRoute.value.name === 'login'
    if (status === 401 && !url.endsWith('/auth/me') && !onLoginPage) {
      useAuthStore().$reset?.() ?? (useAuthStore().user = null)
      router.push({ name: 'login', query: { redirect: router.currentRoute.value.fullPath } })
    }
    return Promise.reject(err)
  },
)
```
Dua pengecualian penting: 401 dari `/auth/me` saat aplikasi dibuka oleh pengunjung anonim tidak boleh memicu redirect, dan 401 "Email atau password salah" di halaman login tidak boleh memicu redirect ke halaman yang sama. Import `router` dan store dilakukan secara lazy di dalam interceptor (atau `api.js` diimport oleh router, bukan sebaliknya) untuk menghindari circular import.

```js
// errors.js
export function pesanError(err, fallback = 'Terjadi kesalahan, coba lagi') {
  return err?.response?.data?.message ?? fallback   // pesan Bahasa Indonesia dari backend
}
```

### 4. Store auth — `src/stores/auth.js` (Pinia setup store)

State: `user` (null | {id, nama, email, role, created_at}), `initialized` (false sampai `fetchMe` pertama selesai).
Getter: `isAuthenticated`, `isSuperAdmin`, `isSiswa`.
Actions:
- `fetchMe()`: `GET /auth/me`; sukses → `user = data.data`; gagal apa pun → `user = null`; `finally initialized = true`. Dipanggil sekali oleh router guard.
- `login({email, password})`: `POST /auth/login`; `user = data.data.user`. Cookie diurus browser, **tidak ada token yang disimpan** di store atau localStorage.
- `register({nama, email, password})`: `POST /auth/register`; tidak otomatis login, kembalikan `data.data`.
- `logout()`: `POST /auth/logout` lalu `user = null` (tetap null-kan meski request gagal).
- Kembalikan semua state, getter, dan action (aturan Pinia setup store).

### 5. Router — `src/router/index.js`

Routes:

| path | name | view | meta |
|---|---|---|---|
| `/login` | login | LoginView (AuthLayout) | `guestOnly` |
| `/register` | register | RegisterView (AuthLayout) | `guestOnly` |
| `/` | home | redirect berdasar role | `requiresAuth` |
| `/siswa` | siswa.dashboard | siswa/DashboardView (AppLayout) | `requiresAuth`, `role: 'siswa'` |
| `/admin` | admin.dashboard | admin/DashboardView (AppLayout) | `requiresAuth`, `role: 'super_admin'` |
| `/forbidden` | forbidden | ForbiddenView | |
| `/:pathMatch(.*)*` | not-found | NotFoundView | |

Guard `router.beforeEach(async (to) => {...})`:
1. `if (!auth.initialized) await auth.fetchMe()` (hanya sekali per load).
2. `to.meta.requiresAuth && !auth.isAuthenticated` → `{ name: 'login', query: { redirect: to.fullPath } }`.
3. `to.meta.guestOnly && auth.isAuthenticated` → ke dashboard sesuai role.
4. `to.meta.role && auth.user.role !== to.meta.role` → `{ name: 'forbidden' }`.
5. `name === 'home'` → redirect ke `siswa.dashboard` atau `admin.dashboard` sesuai role.

Taruh fungsi `dashboardFor(role)` di satu tempat (`router/index.js`) supaya dipakai guard dan LoginView.

Layout dipilih lewat nested routes (route induk dengan `component: AppLayout` dan `children`), cara paling dekat dengan `@extends('layouts.app')`.

### 6. Views (seadanya, PrimeVue + Tailwind)

- **`components/FormField.vue`**: props `label`, `error`, `modelValue`; render `<label>`, `<InputText>` atau `<Password :feedback="false" toggleMask>`, dan `<small class="text-red-500">` untuk error.
- **`LoginView.vue`**: form email + password, tombol `<Button label="Masuk" :loading="loading" />`. Validasi manual: email wajib & format sederhana, password wajib. Submit → `auth.login` → `router.push(route.query.redirect ?? dashboardFor(role))`. Gagal → `toast.add({ severity: 'error', summary: 'Login gagal', detail: pesanError(err), life: 4000 })`. Link ke `/register`.
- **`RegisterView.vue`**: nama (min 3), email, password (min 8), konfirmasi password (sama). Sukses → toast "Registrasi berhasil" + redirect ke `/login`. Error 400 "Email sudah terdaftar" ditampilkan di bawah field email; 400 lain tampil sebagai toast.
- **`AppLayout.vue`**: header dengan teks "SIAP OSN", nama user + badge role (`<Tag>`), tombol Keluar (`auth.logout()` lalu `router.push('/login')`), lalu `<RouterView />` di area konten dengan `max-w-5xl mx-auto p-4`.
- **`siswa/DashboardView.vue`**: kartu "Halo, {nama}" + placeholder menu (Pretest, Materi, Simulasi) sebagai `<Card>` tanpa fungsi.
- **`admin/DashboardView.vue`**: kartu sambutan + tombol "Tes akses admin" yang memanggil `GET /admin/ping` dan menampilkan hasil di toast. Ini bukti role-guard backend bekerja dari browser.
- **`ForbiddenView.vue` / `NotFoundView.vue`**: teks + link kembali.

Semua teks UI dalam Bahasa Indonesia, konsisten dengan pesan backend.

### 7. Test (Vitest + jsdom, tanpa backend, dijalankan via Bun)

- **`stores/auth.spec.js`**: mock `api` dengan `vi.mock('@/lib/api')`; uji `fetchMe` sukses/401 → `initialized` true dan `user` benar; `login` mengisi `user`; `logout` mengosongkan `user` meski request gagal.
- **`router/guard.spec.js`**: buat router dengan `createMemoryHistory`, set store manual (`initialized = true`, `user = ...`), lalu `await router.push('/admin')` dan cek `router.currentRoute.value.name` untuk kasus: tamu → `login`, siswa → `forbidden`, super_admin → `admin.dashboard`, user login buka `/login` → dashboard.
- Tetap `jsdom` (bukan `happy-dom`), tetap Vitest (jangan migrasi ke `bun test` — tidak support SFC `.vue` + `vi.mock` penuh).
- Script `package.json`: `dev` (`bunx --bun vite`), `build`, `preview`, `test:unit` (`vitest run`, runtime Node tanpa `--bun`), `lint`, `format`. Jalankan: `bun run test:unit`.

### 8. Dokumentasi FE

- **`README.md`** di `siap-osn-fe`: stack + alasan pin PrimeVue 4 (lisensi), cara jalan (`bun install`, `cp .env.example .env`, backend harus hidup di 8080, `bun run dev`), struktur folder, analogi Laravel di atas, dan aturan singkat: token tidak pernah disimpan di frontend, semua request lewat `lib/api.js`, semua route terproteksi lewat `meta`.
- Di repo backend, tambahkan satu baris di `README.md` yang menautkan repo frontend.

---

## Verifikasi end-to-end

Backend:
```bash
cd /Volumes/PS50U/siap-osn-be
SUPER_ADMIN_NAMA="Admin" SUPER_ADMIN_EMAIL=admin@example.com SUPER_ADMIN_PASSWORD=adminpass123 docker compose up -d --build
curl -s localhost:8080/health
```

Frontend:
```bash
cd /Volumes/PS50U/siap-osn-fe
bunx eslint . && bun run test:unit && bun run build
bun run dev        # harus tepat di http://localhost:5173
```

Skenario di browser (buka DevTools tab Network dan Application → Cookies):
1. Buka `http://localhost:5173/` sebagai tamu → satu request `GET /auth/me` berstatus 401, lalu diarahkan ke `/login` tanpa loop.
2. Register user baru → toast "Registrasi berhasil", pindah ke `/login`. Ulangi email yang sama → error "Email sudah terdaftar" muncul di field email.
3. Login salah → toast "Email atau password salah", tetap di `/login`.
4. Login benar sebagai siswa → response login memuat header `Set-Cookie`, cookie `siap_osn_token` tampil di Application → Cookies untuk `localhost` dengan flag HttpOnly, dan `document.cookie` di console **tidak** menampilkannya. Diarahkan ke `/siswa`.
5. Refresh halaman `/siswa` → tetap login (guard memanggil `/auth/me` dan dapat 200).
6. Buka `/admin` sebagai siswa → halaman Forbidden. Buka `/login` saat sudah login → dilempar ke dashboard.
7. Logout → response memuat `Set-Cookie` dengan expires 1970, cookie hilang, kembali ke `/login`. Tekan back ke `/siswa` → kembali ke `/login`.
8. Login sebagai `admin@example.com` → `/admin`, tombol "Tes akses admin" menampilkan "pong".
9. Uji CORS negatif: jalankan `bun run dev -- --port 5174` (tanpa strictPort) atau buka `http://127.0.0.1:5173` → login gagal karena origin tidak diizinkan / cookie tidak terkirim. Ini membuktikan konfigurasi origin memang dipakai.

---

## Risiko yang diwaspadai

- **`primevue@latest` menarik versi 5 berlisensi komersial.** Selalu pin `4.5.5`, `@primeuix/themes@2.0.3`, `primeicons@7.0.0`; gunakan top-level `overrides` di `package.json` (bukan `pnpm.overrides` — diabaikan Bun) agar tidak terangkat saat update.
- **Bun: jangan jalankan Vitest dengan `--bun`.** `bunx --bun vitest run` diketahui hang (`tinypool`/`worker_threads`). Jalankan `bun run test:unit` tanpa `--bun` (runtime Node).
- **Bun: postinstall diblok.** Bila `bun install` menanyakan `trustedDependencies` (esbuild/Vite), jalankan sekali `bun install --trust`.
- **Pinia 4 tanpa `@vue/devtools-api`** gagal saat dev. Pastikan terinstall.
- **Redirect loop 401**: interceptor harus mengecualikan `/auth/me` dan halaman login.
- **Circular import** antara `api.js`, `router`, dan store: import router/store secara lazy di dalam interceptor.
- **Origin harus persis `http://localhost:5173`**: `strictPort: true`, jangan buka lewat `127.0.0.1` atau IP LAN. Untuk uji dari HP di jaringan yang sama, tambahkan origin itu ke `CORS_ALLOW_ORIGINS` backend.
- **Production beda domain**: butuh `COOKIE_SAMESITE=None` + `COOKIE_SECURE=true` + HTTPS di backend. Sub-domain yang sama (`app.` dan `api.`) lebih sederhana dan tetap `Lax`.
