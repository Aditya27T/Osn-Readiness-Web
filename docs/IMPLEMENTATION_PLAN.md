# Implementation Plan — Architecture Foundation

Roadmap bertahap untuk memenuhi [PRD.md](PRD.md) mengikuti [ARCHITECTURE_RULES.md](ARCHITECTURE_RULES.md). Setiap phase adalah satu PR (atau satu commit terpisah) agar mudah di-review.

Kondisi awal: skeleton Laravel 13 (Sail + MySQL), belum ada `routes/api.php`, Sanctum, Breeze, Spatie, Boost, maupun folder layer.

---

## Phase 0 — Setup paket

> Boost dipasang **pertama** sesuai `CLAUDE.md`; setelah `boost:install`, baca ulang `AGENTS.md` sebelum lanjut.

- [ ] `composer require laravel/boost --dev && php artisan boost:install`
- [ ] `composer require spatie/laravel-permission`
- [ ] `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"`
- [ ] `php artisan install:api` (Sanctum + `routes/api.php` + migration `personal_access_tokens`)
- [ ] `composer require laravel/breeze --dev && php artisan breeze:install blade`
  - Jika Breeze tidak kompatibel dengan Laravel 13 → fallback `laravel/fortify`; catat di PRD §9.
- [ ] `php artisan migrate`
- [ ] Verifikasi `bootstrap/app.php` memuat `api: __DIR__.'/../routes/api.php'`.

**Output:** semua paket terpasang, migrasi berjalan, `AGENTS.md` ter-regenerasi.

---

## Phase 1 — Model, migration, provider

- [ ] Migration `add_is_active_to_users_table` → `is_active` boolean default `true`.
- [ ] `app/Models/User.php`: trait `HasRoles`, `HasApiTokens`; `is_active` ke `#[Fillable]`; cast `'is_active' => 'boolean'`.
- [ ] `app/Providers/RepositoryServiceProvider.php` (kosong dulu, diisi di Phase 2).
- [ ] Daftarkan provider di `bootstrap/providers.php`.
- [ ] `.env.example`: tambah `SUPER_ADMIN_EMAIL`, `SUPER_ADMIN_PASSWORD`.

**Output:** `php artisan migrate` sukses; `User::first()->hasRole('x')` bisa dipanggil.

---

## Phase 2 — Contracts, BaseRepository, Repository, Service

- [ ] `app/Contracts/Repositories/BaseRepositoryInterface.php`
- [ ] `app/Contracts/Repositories/UserRepositoryInterface.php` (extends Base + `findByEmail`)
- [ ] `app/Repositories/BaseRepository.php` (abstract, lihat ARCHITECTURE_RULES §7)
- [ ] `app/Repositories/Eloquent/UserRepository.php` (eager-load `roles` di `paginate`)
- [ ] `app/Contracts/Services/UserServiceInterface.php`
- [ ] `app/Services/UserService.php`: `list(int $perPage)`, `registerUser(array)`, `updateUser(User, array)`, `deleteUser(User)`
  - `registerUser` → `DB::transaction` + `assignRole('user')`; **tanpa** `Hash::make`.
- [ ] `app/Contracts/Services/AuthServiceInterface.php` + `app/Services/AuthService.php`: `login(string $email, string $password, ?string $device)`, `logout(User)`.
- [ ] Isi binding di `RepositoryServiceProvider`.

**Output:** `php artisan tinker` → `app(UserServiceInterface::class)` mengembalikan `UserService`.

---

## Phase 3 — Authorization & HTTP layer

- [ ] `app/Policies/UserPolicy.php` (`before()` Super Admin; `viewAny/view/create/update/delete` → `users.*`)
- [ ] `app/Http/Requests/User/StoreUserRequest.php`, `UpdateUserRequest.php`
- [ ] `app/Http/Requests/Auth/ApiLoginRequest.php`
- [ ] `app/Http/Resources/UserResource.php`
- [ ] `app/Http/Controllers/Api/UserController.php` (inject `UserServiceInterface`)
- [ ] `app/Http/Controllers/Api/AuthController.php` (inject `AuthServiceInterface`)
- [ ] `app/Http/Middleware/CheckUserStatus.php` → alias `active` di `bootstrap/app.php`
- [ ] `routes/api.php`: group `auth/*` + `apiResource('users')` di balik `auth:sanctum`, `active`. Tanpa closure.
- [ ] `routes/web.php`: tambahkan `active` ke group `auth` milik Breeze.
- [ ] Ubah `app/Http/Controllers/Auth/RegisteredUserController.php` (Breeze) agar memanggil `UserServiceInterface::registerUser()`.

**Output:** `php artisan route:list --path=api` menampilkan 8 endpoint dengan middleware yang benar.

---

## Phase 4 — Seeder

- [ ] `database/seeders/RolesAndPermissionsSeeder.php` — permissions `users.{viewAny,view,create,update,delete}`, roles `Super Admin`, `user`; `firstOrCreate`; `forgetCachedPermissions()`.
- [ ] `database/seeders/SuperAdminSeeder.php` — dari env; `assignRole('Super Admin')`.
- [ ] `DatabaseSeeder` memanggil keduanya.

**Output:** `php artisan migrate:fresh --seed` menghasilkan akun Super Admin.

---

## Phase 5 — Tests & code style

- [ ] `tests/Feature/Api/AuthTest.php`
- [ ] `tests/Feature/Api/UserApiTest.php`
- [ ] `tests/Unit/Services/UserServiceTest.php`
- [ ] `vendor/bin/pint`
- [ ] `composer test` hijau

**Output:** semua test lulus, Pint bersih.

---

## Phase 6 — Dokumentasi & tautan

- [ ] `README.md` Quick Navigation → tautan ke `docs/ARCHITECTURE_RULES.md`, `docs/PRD.md`, `docs/IMPLEMENTATION_PLAN.md`.
- [ ] `AGENTS.md` / `CLAUDE.md` (hasil Boost): tambahkan "Ikuti `docs/ARCHITECTURE_RULES.md` untuk semua kode baru."
- [ ] Update PRD status → *Approved / Implemented*; catat keputusan Breeze vs Fortify di PRD §9.

---

## Verifikasi End-to-End

1. `composer test` — semua Feature/Unit test hijau.
2. `vendor/bin/pint --test` — tanpa pelanggaran.
3. `php artisan route:list --path=api` — endpoint `auth/*` dan `users` muncul dengan `auth:sanctum, active`.
4. `php artisan migrate:fresh --seed`, lalu smoke test:
   - `POST /api/auth/login` (Super Admin) → token; `GET /api/users` → 200 paginated.
   - Register via web `/register` → user memiliki role `user`; login API dengan akun itu → `GET /api/users` → 403.
   - Set `is_active = false` pada akun → `GET /api/auth/me` → 403.
5. `grep -rE "DB::|::find\(|::where\(" app/Http/Controllers` → harus kosong.

---

## File yang disentuh

**Existing:** `app/Models/User.php`, `bootstrap/app.php`, `bootstrap/providers.php`, `routes/web.php`, `database/seeders/DatabaseSeeder.php`, `.env.example`, `README.md`, `AGENTS.md`/`CLAUDE.md`, `app/Http/Controllers/Auth/RegisteredUserController.php` (Breeze).

**Baru:** seluruh path di Phase 1–5, mengikuti struktur direktori ARCHITECTURE_RULES §2.
