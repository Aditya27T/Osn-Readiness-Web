# PRD — Architecture Foundation & User Module

| | |
|---|---|
| Proyek | OSN Readiness Web |
| Versi | 1.0 (draft) |
| Tanggal | 2026-09-22 |
| Status | Menunggu persetujuan |
| Dokumen terkait | [ARCHITECTURE_RULES.md](ARCHITECTURE_RULES.md) · [IMPLEMENTATION_PLAN.md](IMPLEMENTATION_PLAN.md) |

---

## 1. Latar Belakang & Tujuan

Repo saat ini adalah skeleton Laravel 13 bersih (Sail + MySQL). Sebelum fitur produk OSN Readiness (bank soal, try-out, laporan, dsb.) dibangun, diperlukan **fondasi arsitektur yang konsisten** agar:

1. Setiap modul baru mengikuti pola yang sama (Controller → Request/Policy → Service → Repository → Model → Resource).
2. Tim dan AI assistant memiliki satu acuan (`docs/ARCHITECTURE_RULES.md`) dan satu contoh nyata (modul **User**) yang bisa ditiru.
3. Autentikasi (web + API) dan otorisasi berbasis role/permission sudah tersedia sejak awal.

**Tujuan PRD ini:** mendefinisikan kebutuhan fondasi arsitektur beserta modul User sebagai *reference implementation*.

**Bukan tujuan PRD ini:** mendefinisikan fitur produk OSN Readiness. Itu akan ditulis di PRD terpisah setelah fondasi selesai.

---

## 2. Ruang Lingkup

### In-scope

- Instalasi dan konfigurasi paket: Laravel Boost (dev), Spatie Permission, Sanctum, Breeze.
- Scaffolding layer: Contracts, BaseRepository, Repositories, Services, Policies, Form Requests, Resources, `RepositoryServiceProvider`.
- Autentikasi web (session, Breeze) dan API (token, Sanctum).
- RBAC dengan role `Super Admin` dan `user`, permission `users.*`, seeder idempotent.
- Modul User: CRUD API lengkap (`/api/users`) sebagai reference implementation.
- Middleware `CheckUserStatus` (akun nonaktif ditolak).
- Feature & Unit test untuk semua yang di atas.
- Dokumentasi di `docs/` dan tautan dari `README.md` serta `AGENTS.md`/`CLAUDE.md`.

### Out-of-scope

- Fitur produk OSN Readiness (soal, try-out, nilai, kelas, dsb.).
- UI frontend selain halaman auth bawaan Breeze.
- Email verification, reset password via API, 2FA.
- Manajemen role/permission via UI atau API (hanya via seeder).
- Deployment/CI pipeline.

---

## 3. Persona

| Persona | Deskripsi | Hak akses |
|---|---|---|
| **Super Admin** | Pengelola sistem. Dibuat via seeder dari env. | Bypass semua Policy (`before()` mengembalikan `true`). |
| **user** | Akun biasa. Role default untuk semua registrasi. | Tidak punya permission `users.*` secara default. Hanya bisa akses `auth/me`, `auth/logout`, dan halaman web setelah login. |

---

## 4. Functional Requirements

### FR-01 — Paket & Setup

Aplikasi terpasang dengan: `laravel/boost` (dev), `spatie/laravel-permission`, `laravel/sanctum` (via `php artisan install:api`), `laravel/breeze` (dev, Blade stack).

**Acceptance criteria**

- `composer.json` memuat keempat paket.
- Migration Spatie, `personal_access_tokens`, dan Breeze berjalan tanpa error (`php artisan migrate`).
- `AGENTS.md`/`CLAUDE.md` hasil Boost memuat pointer ke `docs/ARCHITECTURE_RULES.md`.

### FR-02 — Layer Scaffolding & Binding

Struktur direktori sesuai `ARCHITECTURE_RULES.md` §2 tersedia, dengan `BaseRepositoryInterface`, `BaseRepository`, dan `RepositoryServiceProvider` terdaftar di `bootstrap/providers.php`.

**Acceptance criteria**

- `app(UserServiceInterface::class)` dan `app(UserRepositoryInterface::class)` menghasilkan instance konkret tanpa error.
- Tidak ada `new UserService()`/`new UserRepository()` di seluruh `app/`.

### FR-03 — Autentikasi Web (Breeze)

Pengguna dapat register, login, dan logout via halaman web Breeze.

**Acceptance criteria**

- `POST /register` membuat user melalui `UserServiceInterface::registerUser()` (bukan langsung `User::create()` di controller Breeze).
- User hasil registrasi otomatis memiliki role `user`.
- Halaman yang membutuhkan auth memakai middleware `auth` + `active`.

### FR-04 — Autentikasi API (Sanctum)

| Endpoint | Auth | Deskripsi |
|---|---|---|
| `POST /api/auth/login` | publik | Validasi `email`+`password`; mengembalikan token Sanctum + `UserResource`. |
| `POST /api/auth/logout` | `auth:sanctum` | Mencabut token yang sedang dipakai. |
| `GET /api/auth/me` | `auth:sanctum` | Mengembalikan `UserResource` user saat ini beserta roles. |

**Acceptance criteria**

- Kredensial salah → `422` dengan pesan validasi pada field `email`.
- Logika verifikasi kredensial dan pembuatan token berada di `AuthService`, bukan di controller.
- Token tidak pernah muncul di `UserResource`.

### FR-05 — RBAC (Spatie Permission)

**Acceptance criteria**

- `RolesAndPermissionsSeeder` membuat permission `users.viewAny`, `users.view`, `users.create`, `users.update`, `users.delete` dan role `Super Admin`, `user`. Seeder dapat dijalankan berulang tanpa duplikasi.
- `SuperAdminSeeder` membuat akun dari `SUPER_ADMIN_EMAIL` & `SUPER_ADMIN_PASSWORD` (`.env.example` diperbarui) dan meng-assign role `Super Admin`.
- Seeder memanggil `forgetCachedPermissions()`.
- Model `User` memakai trait `HasRoles` dan `HasApiTokens`.

### FR-06 — User CRUD API (Reference Implementation)

Semua endpoint di balik `auth:sanctum` + `active`.

| Method | Endpoint | Policy ability | Request | Response |
|---|---|---|---|---|
| `GET` | `/api/users` | `viewAny` | `?per_page=15` | `200` — `UserResource::collection` (paginated) |
| `GET` | `/api/users/{user}` | `view` | — | `200` — `{message, data}` |
| `POST` | `/api/users` | `create` | `StoreUserRequest` | `201` — `{message, data}` |
| `PUT/PATCH` | `/api/users/{user}` | `update` | `UpdateUserRequest` | `200` — `{message, data}` |
| `DELETE` | `/api/users/{user}` | `delete` | — | `200` — `{message}` |

**Aturan validasi**

- `StoreUserRequest`: `name` required string max 255; `email` required email unique; `password` required min 8 confirmed; `is_active` boolean optional.
- `UpdateUserRequest`: sama, semua `sometimes`; `email` unique dengan ignore user saat ini; `password` opsional.

**Acceptance criteria**

- Controller tidak berisi query database maupun logika bisnis; hanya `Gate::authorize`, panggil Service, kembalikan Resource.
- `UserResource` memuat `id, name, email, is_active, roles[], created_at, updated_at`; tidak memuat `password`/`remember_token`.
- Super Admin dapat mengakses semua endpoint; `user` tanpa permission mendapat `403`; tanpa token mendapat `401`.
- `registerUser()` dibungkus `DB::transaction` dan meng-assign role `user`.
- Password tidak di-hash dua kali (mengandalkan cast `hashed` di model).

### FR-07 — Middleware `CheckUserStatus`

**Acceptance criteria**

- Kolom `users.is_active` (boolean, default `true`) ditambahkan via migration baru.
- Middleware dengan alias `active` terdaftar di `bootstrap/app.php`.
- User dengan `is_active = false`: request API → `403` JSON `{message: "Akun tidak aktif"}`; request web → logout + redirect ke login dengan pesan error.

### FR-08 — Testing

**Acceptance criteria**

- `tests/Feature/Api/AuthTest.php`: login sukses, login gagal, `me`, logout, akun nonaktif → 403.
- `tests/Feature/Api/UserApiTest.php`: matriks otorisasi (401/403/200/201), validasi 422, password tidak bocor, pagination.
- `tests/Unit/Services/UserServiceTest.php`: Repository di-mock; memastikan `create` dipanggil dan role `user` di-assign.
- `composer test` hijau; `vendor/bin/pint --test` tanpa pelanggaran.

### FR-09 — Dokumentasi

**Acceptance criteria**

- `docs/ARCHITECTURE_RULES.md`, `docs/PRD.md`, `docs/IMPLEMENTATION_PLAN.md` ada di repo.
- `README.md` Quick Navigation menautkan ke ketiga dokumen.
- `AGENTS.md`/`CLAUDE.md` memuat instruksi: "Ikuti `docs/ARCHITECTURE_RULES.md` untuk semua kode baru."

---

## 5. Non-Functional Requirements

| ID | Kebutuhan |
|---|---|
| NFR-01 | Semua class baru memakai type hint argumen & return type secara ketat. |
| NFR-02 | Kode lolos Laravel Pint (preset `laravel`). |
| NFR-03 | Tidak ada query database di layer Controller (diverifikasi via code review / grep). |
| NFR-04 | Format response JSON konsisten: `{message, data}` untuk single, paginator standar Laravel untuk list. |
| NFR-05 | Error pada `api/*` selalu JSON (memanfaatkan `shouldRenderJsonWhen` yang sudah ada di `bootstrap/app.php`). |
| NFR-06 | Seeder idempotent — aman dijalankan berulang di semua environment. |
| NFR-07 | Tidak ada N+1 pada list user (roles di-eager-load di Repository). |

---

## 6. Kontrak API — Ringkasan

Base URL: `/api`. Header: `Accept: application/json`, `Authorization: Bearer {token}` untuk endpoint terproteksi.

| Endpoint | Auth | Permission | Status sukses | Status gagal |
|---|---|---|---|---|
| `POST /auth/login` | – | – | 200 | 422 |
| `POST /auth/logout` | token | – | 200 | 401 |
| `GET /auth/me` | token | – | 200 | 401, 403 (nonaktif) |
| `GET /users` | token | `users.viewAny` | 200 | 401, 403 |
| `GET /users/{id}` | token | `users.view` | 200 | 401, 403, 404 |
| `POST /users` | token | `users.create` | 201 | 401, 403, 422 |
| `PUT /users/{id}` | token | `users.update` | 200 | 401, 403, 404, 422 |
| `DELETE /users/{id}` | token | `users.delete` | 200 | 401, 403, 404 |

Contoh response `GET /auth/me`:

```json
{
  "message": "OK",
  "data": {
    "id": 1,
    "name": "Super Admin",
    "email": "admin@example.com",
    "is_active": true,
    "roles": ["Super Admin"],
    "created_at": "2026-09-22T00:00:00.000000Z",
    "updated_at": "2026-09-22T00:00:00.000000Z"
  }
}
```

---

## 7. Skema Data

| Tabel | Perubahan |
|---|---|
| `users` | + `is_active` BOOLEAN NOT NULL DEFAULT 1 |
| `personal_access_tokens` | dari migration Sanctum |
| `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` | dari migration Spatie |

---

## 8. Definition of Done

- [ ] Semua FR-01 … FR-09 memenuhi acceptance criteria.
- [ ] `composer test` dan `vendor/bin/pint --test` hijau.
- [ ] `php artisan migrate:fresh --seed` berjalan tanpa error dan menghasilkan akun Super Admin.
- [ ] `php artisan route:list --path=api` menampilkan semua endpoint di §6 dengan middleware yang benar.
- [ ] Smoke test manual (§Verifikasi di IMPLEMENTATION_PLAN.md) lulus.
- [ ] PR di-review; tidak ada pelanggaran `ARCHITECTURE_RULES.md`.

---

## 9. Risiko & Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| `laravel/breeze` belum mendukung Laravel 13 saat instalasi | FR-03 terblokir | Fallback ke `laravel/fortify` (headless, tetap session auth) atau Livewire starter kit; keputusan dicatat di sini. |
| Double-hash password | Login gagal | Jangan `Hash::make()` di Service; andalkan cast `hashed`. Dicek oleh `AuthTest`. |
| Cache permission Spatie basi setelah seeding | 403 palsu | Seeder & test memanggil `forgetCachedPermissions()`. |
| `install:api` mengubah `bootstrap/app.php` | Konflik konfigurasi | Verifikasi `withRouting(api: ...)` setelah perintah dijalankan. |
| Controller Breeze bawaan tidak lewat Service | Melanggar aturan arsitektur | Modifikasi `RegisteredUserController` agar memanggil `UserServiceInterface`. |

---

## 10. Pertanyaan Terbuka

- Apakah `user` perlu permission `users.view` untuk profil sendiri (`/api/users/{id}` dengan id sendiri)? *Asumsi saat ini: tidak; gunakan `/api/auth/me`.*
- Apakah rate limiting login API diperlukan sejak awal? *Asumsi: pakai throttle default `api` (60/menit); throttle khusus login menyusul.*
