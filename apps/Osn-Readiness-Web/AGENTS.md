# OSN Readiness Web

Laravel 13 web app for olympiad (OSN) prep — soal, try-out, nilai, and kelas modules with Spatie RBAC.

## Stack

- PHP 8.3, Laravel 13 (`laravel/framework ^13.17`), Sanctum (`laravel/sanctum ^4.3`), Breeze (`laravel/breeze ^2.4`)
- Spatie Permission (`spatie/laravel-permission ^8.3`) for RBAC
- Testing & Style: PHPUnit (`phpunit/phpunit ^12.5.12`), Laravel Pint (`laravel/pint ^1.27`)
- Frontend: Vite (`vite ^8.0.0`), Tailwind CSS (`tailwindcss ^3.1.0`), Alpine.js (`alpinejs ^3.4.2`)
- Package managers: Composer (`composer.json`), npm (`package.json`, `package-lock.json`)

## Commands

- Setup: `composer setup` (installs deps, copies .env, generates key, migrates, builds frontend)
- Dev server: `composer dev` (PHP server + Vite)
- Frontend dev: `npm run dev`
- Frontend build: `npm run build`
- Run all tests: `composer test` (clears config cache, then runs test suite)
- Run single test: `php artisan test tests/Feature/Api/UserApiTest.php`
- Lint check: `vendor/bin/pint --test`
- Lint fix: `vendor/bin/pint`
- Sail / Docker: `./vendor/bin/sail up -d`, `./vendor/bin/sail artisan migrate`

## Layout

- `app/Contracts/Repositories/` & `app/Contracts/Services/` — Interface contracts
- `app/Enums/` — `Level`, `Peruntukan`, `TipeSoal`, `StatusProgress`, `StatusKenaikan`, `JenisPengerjaan`
- `app/Http/Controllers/Api/` — Thin controllers (`Gate::authorize` + Resource return)
- `app/Http/Middleware/CheckUserStatus.php` — Inactive user check (`403 JSON`)
- `app/Http/Requests/` & `app/Http/Resources/` — FormRequest validation and API resource transformers
- `app/Models/` & `app/Policies/` — Eloquent models and Spatie policies
- `app/Repositories/Eloquent/` — Concrete Eloquent repositories (queries, eager loads)
- `app/Services/` — Concrete services (business logic, `DB::transaction`)
- `app/Providers/RepositoryServiceProvider.php` — Interface-to-implementation DI bindings
- `bootstrap/app.php` & `bootstrap/providers.php` — Routing, middleware aliases, and providers
- `routes/api.php` & `routes/web.php` — API and web route definitions
- `tests/Feature/` & `tests/Unit/` — Feature tests (DB) and unit tests (Mockery)
- `docs/ARCHITECTURE_RULES.md`, `docs/PRD.md`, `docs/IMPLEMENTATION_PLAN.md` — Architecture rules & specs
- `docs/BATCH_PLAN.md`, `docs/BATCH_PLAN_ORANG_1.md`, `docs/BATCH_PLAN_ORANG_2.md` — Backend work split per person
- `docs/Logic per fitur.md`, `docs/Migration.pdf`, `docs/Kode migration.pdf` — Feature logic & schema source

## Conventions

- Architecture: Service-Repository pattern — Controller -> Service -> Repository -> Model.
- Controllers call Services; Services call Repositories; no direct Eloquent queries in controllers.
- Interfaces: Every service and repository has an interface bound in `RepositoryServiceProvider`.
- Transactions & Hashing: `DB::transaction` in Services. No `Hash::make` in Services.
- Auth & Policies: `Gate::authorize()` in controllers; Super Admin bypass in `UserPolicy::before()`.
- API endpoints: Dedicated `FormRequest` validation; output via API Resource transformers.
- Testing: Feature tests use `RefreshDatabase` + `RolesAndPermissionsSeeder` in `setUp()`.

## Gotchas

- Inactive users: `CheckUserStatus` middleware returns 403 JSON; aliased as `active` in `bootstrap/app.php`.
- DI bindings: Every new service and repository must be bound in `RepositoryServiceProvider`.
- Super Admin: Never remove the `UserPolicy::before()` bypass guard for the Super Admin role.
- Database: PostgreSQL 16 only (`compose.yaml` service `pgsql`); SQLite/MySQL tidak didukung karena index parsial, `jsonb`, dan check constraint. Test memakai database terpisah `osn_readiness_testing` (lihat `phpunit.xml`).
- Tabel tidak jamak: setiap model wajib `#[Table('...')]`, dan `constrained()` selalu menyebut nama tabelnya.
- `Soal` memakai `SoftDeletes`; soal yang dipakai pengerjaan tetap bisa diambil untuk review.
- Partial unique index dijaga lewat `DB::statement` di dalam migration (Laravel tidak punya index parsial): `users_email_aktif_unique`, `pretest_berjalan_unique`, `hasil_simulasi_berjalan_unique`, `kenaikan_lulus_unique`.
- `riwayat_hasil` adalah SQL view, bukan tabel: model `RiwayatHasil` hanya baca, tanpa timestamps, dan migration-nya wajib jalan paling akhir.
- Role default adalah `siswa` (bukan `user`); permission admin memakai `siswa.*`, `materi.*`, `soal.*`, dst.
- Tailwind dual version: `package.json` lists `@tailwindcss/vite ^4.0.0` and `tailwindcss ^3.1.0`.
- Git workflow: Working branch is `dev`; remotes: `fork` and `origin`.
