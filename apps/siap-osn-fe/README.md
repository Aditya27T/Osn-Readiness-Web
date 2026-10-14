# SIAP OSN — Frontend

Frontend Vue untuk SIAP OSN. Backend: `siap-osn-be`
(folder sejajar, `http://localhost:8080`).

## Stack

Vue 3 + Vite (JavaScript, bukan TypeScript), Vue Router, Pinia,
Axios, Tailwind CSS v4 + PrimeVue 4 (preset Aura).

> **Kenapa PrimeVue 4 di-pin?** PrimeVue 5.x berlisensi komersial
> (PrimeUI License — tidak cocok untuk universitas/instansi publik),
> sedangkan versi 4 tetap MIT selamanya. Jangan pakai
> `primevue@latest`. Lihat `overrides` di `package.json`:
> `primevue@4.5.5`, `@primeuix/themes@2.0.3`, `primeicons@7.0.0`.

Package manager + runtime dev/build: **Bun**
(detail: `docs/PLAN-TOOLING-BUN.md`).
Test (`vitest`) dan lint berjalan di Node — jangan pakai `--bun`
untuk keduanya.

## Cara jalan

```sh
bun install
cp .env.example .env   # VITE_API_BASE_URL=http://localhost:8080/api/v1
```

Backend harus hidup di 8080 (lihat README `siap-osn-be`).

```sh
bun run dev        # harus tepat di http://localhost:5173 (CORS + cookie)
bun run test:unit
bunx eslint .
bun run build
```

## Struktur folder

```text
src/
├── main.js            # createApp, Pinia, Router, PrimeVue(Aura), ToastService
├── App.vue            # <Toast /> + <RouterView />
├── assets/main.css    # @import tailwind, tailwindcss-primeui, primeicons
├── lib/
│   ├── api.js         # instance axios (baseURL, withCredentials, interceptor 401)
│   └── errors.js      # pesan Bahasa Indonesia dari envelope error backend
├── stores/auth.js     # user, initialized, isAuthenticated, fetchMe/login/register/logout
├── router/index.js    # routes + meta.requiresAuth / meta.role + guard
├── layouts/           # AuthLayout (login/register), AppLayout (header + konten)
├── views/             # Login, Register, siswa/, admin/, Forbidden, NotFound
└── components/        # FormField (dipakai login/register)
```

Analogi untuk tim Laravel: `router/index.js` ≈ `routes/web.php`,
guard `beforeEach` ≈ middleware `auth` dan cek role,
`stores/auth.js` ≈ `Auth::user()` di sisi browser,
`lib/api.js` ≈ `Http::withOptions([...])` yang dipakai bersama,
`views/` ≈ Blade view, `layouts/` ≈ `layouts/app.blade.php`.

## Aturan singkat

1. Token **tidak pernah** disimpan di frontend (cookie httpOnly diurus browser).
2. Semua request HTTP lewat `lib/api.js` (sudah `withCredentials: true`).
3. Semua route terproteksi lewat `meta` (`requiresAuth`, `role`, `guestOnly`).
