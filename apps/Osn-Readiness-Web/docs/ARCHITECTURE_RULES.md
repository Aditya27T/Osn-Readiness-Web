# Laravel Architecture Rules & Coding Standards

Dokumen ini berisi panduan dan standar pengembangan aplikasi **OSN Readiness Web** menggunakan **Service-Repository Pattern**, **Interface Contracts**, **Spatie Permission**, **Policies**, **Form Requests**, dan **API Resources**.

Dokumen ini adalah acuan tunggal bagi tim dan AI assistant (Claude Code, Cursor, Copilot). Setiap kode baru wajib mengikuti aturan di sini.

Dokumen terkait: [PRD.md](PRD.md) · [IMPLEMENTATION_PLAN.md](IMPLEMENTATION_PLAN.md)

---

## 1. Stack & Package Standard

| Komponen | Pilihan |
|---|---|
| Framework | Laravel 13+ (PHP 8.3+) |
| Auth Web (session) | Laravel Breeze (Blade stack) |
| Auth API (token) | Laravel Sanctum |
| Authorization | Spatie Laravel-Permission (`spatie/laravel-permission`) |
| Code style | Laravel Pint (`vendor/bin/pint`) |
| Testing | PHPUnit (`composer test`) |
| AI tooling | Laravel Boost (`laravel/boost`, dev) |

**Alur request:**

```
Route → Middleware → Form Request → Controller → Policy (Gate) → Service → Repository → Model
                                                                                  ↓
                                                Response ← API Resource ←─────────┘
```

---

## 2. Struktur Direktori Proyek

```text
app/
├── Contracts/
│   ├── Repositories/
│   │   ├── BaseRepositoryInterface.php
│   │   └── UserRepositoryInterface.php
│   └── Services/
│       ├── AuthServiceInterface.php
│       └── UserServiceInterface.php
├── Repositories/
│   ├── BaseRepository.php
│   └── Eloquent/
│       └── UserRepository.php
├── Services/
│   ├── AuthService.php
│   └── UserService.php
├── Policies/
│   └── UserPolicy.php
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── AuthController.php
│   │   │   └── UserController.php
│   │   └── Auth/                  # Breeze (web)
│   ├── Middleware/
│   │   └── CheckUserStatus.php
│   ├── Requests/
│   │   ├── Auth/
│   │   │   └── ApiLoginRequest.php
│   │   └── User/
│   │       ├── StoreUserRequest.php
│   │       └── UpdateUserRequest.php
│   └── Resources/
│       └── UserResource.php
├── Models/
│   └── User.php
└── Providers/
    ├── AppServiceProvider.php
    └── RepositoryServiceProvider.php
```

Konvensi penamaan:

- Interface: `{Nama}RepositoryInterface`, `{Nama}ServiceInterface`.
- Implementasi Repository di `app/Repositories/Eloquent/{Nama}Repository.php`.
- Form Request dikelompokkan per resource: `app/Http/Requests/{Resource}/{Store|Update}{Resource}Request.php`.
- Controller API di namespace `App\Http\Controllers\Api`.

---

## 3. Aturan Tanggung Jawab Per Layer

### 3.1 Route & Middleware Layer

**Tugas:** Menangani routing awal dan penyaringan HTTP global/spesifik (cek token auth, status akun aktif/nonaktif).

**Aturan:**

- Dilarang menulis closure function atau logika bisnis di `routes/api.php` maupun `routes/web.php`. Semua route mengarah ke Controller.
- Gunakan custom Middleware hanya untuk kriteria HTTP umum (contoh: `CheckUserStatus`). Otorisasi role/permission **bukan** tugas middleware — arahkan ke Policy.
- Middleware kustom didaftarkan sebagai alias di `bootstrap/app.php` (`withMiddleware`), bukan di Kernel.

```php
// routes/api.php
Route::prefix('auth')->group(function (): void {
    Route::post('login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

Route::middleware(['auth:sanctum', 'active'])->group(function (): void {
    Route::apiResource('users', UserController::class);
});
```

### 3.2 Form Request Layer (`app/Http/Requests`)

**Tugas:** Validasi dan sanitasi data input dari client.

**Aturan:**

- Setiap endpoint `POST`/`PUT`/`PATCH` wajib menggunakan file Form Request terpisah.
- `authorize()` mengembalikan `true` — otorisasi ditangani oleh Policy.
- Selalu gunakan `$request->validated()` saat melempar data dari Controller ke Service. Jangan gunakan `$request->all()`.
- Pesan/atribut kustom (jika perlu) ditulis di method `messages()`/`attributes()` di Form Request yang sama.

### 3.3 Policy Layer (`app/Policies`)

**Tugas:** Otorisasi hak akses (Role & Permission Spatie).

**Aturan:**

- Pengecekan Spatie (`$user->hasPermissionTo(...)`, `$user->hasRole(...)`) **wajib** di Policy, bukan di Controller, Route, atau Service.
- Nama permission mengikuti pola `{resource}.{ability}` dan berpasangan 1:1 dengan method Policy: `users.viewAny`, `users.view`, `users.create`, `users.update`, `users.delete`.
- Gunakan `before()` untuk bypass otomatis bagi `Super Admin`.
- Policy ditemukan otomatis oleh Laravel (konvensi `App\Policies\{Model}Policy`); tidak perlu registrasi manual.

```php
public function before(User $user, string $ability): ?bool
{
    if ($user->hasRole('Super Admin')) {
        return true;
    }

    return null;
}

public function viewAny(User $user): bool
{
    return $user->hasPermissionTo('users.viewAny');
}
```

### 3.4 Controller Layer (`app/Http/Controllers`)

**Tugas:** Orchestrator HTTP Request → Response. Controller harus tipis.

**Aturan:**

- **Dilarang keras** melakukan query database di Controller (`User::find()`, `DB::table()`, `->where()`, dsb.).
- **Dilarang** menulis logika bisnis di Controller.
- Lakukan otorisasi via `Gate::authorize('ability', Model::class)` atau `Gate::authorize('ability', $model)`.
- Hanya memanggil Service melalui **Service Interface** yang di-inject di constructor.
- Mengembalikan response menggunakan **API Resource**.
- Format response JSON konsisten:
  - Single: `{ "message": "...", "data": { ... } }`
  - List: gunakan `Resource::collection($paginator)` (struktur `data`, `links`, `meta` bawaan Laravel).

### 3.5 Service Layer (`app/Services` & `app/Contracts/Services`)

**Tugas:** Tempat utama eksekusi logika bisnis.

**Aturan:**

- Setiap Service wajib memiliki Interface Contract di `app/Contracts/Services/`.
- Bertanggung jawab atas operasi kompleks: assign role, pengiriman email/notifikasi, integrasi pihak ketiga, pembuatan token, dan manajemen **Database Transaction** (`DB::transaction`).
- Mengakses data hanya melalui **Repository Interface**.
- Tidak menyentuh `Request`/`Response` — Service menerima `array`/DTO dan mengembalikan Model/Collection/primitif.
- **Catatan password:** model `User` sudah memiliki cast `'password' => 'hashed'`, sehingga Service **tidak perlu** memanggil `Hash::make()`. Memanggil `Hash::make()` akan menyebabkan *double-hash*.

### 3.6 Repository Layer (`app/Repositories` & `app/Contracts/Repositories`)

**Tugas:** Mengisolasi query dan manipulasi data ke database.

**Aturan:**

- Setiap Repository wajib memiliki Interface Contract di `app/Contracts/Repositories/`.
- Hanya berisi query Eloquent/Query Builder (`get()`, `findOrFail()`, `where()`, `create()`, dsb.).
- **Dilarang** memasukkan logika bisnis (kirim email, manipulasi token, hashing, transaction) di Repository.
- Repository baru meng-extend `BaseRepository` dan mengimplementasikan interface spesifiknya (lihat §7).

### 3.7 Resource Layer (`app/Http/Resources`)

**Tugas:** Memformat struktur JSON output ke client.

**Aturan:**

- Semua response API yang mengembalikan data model wajib dibungkus `JsonResource` atau `ResourceCollection`.
- Menyembunyikan atribut sensitif (`password`, `remember_token`, token).
- Relasi hanya disertakan jika sudah di-load (`$this->whenLoaded('roles')`) untuk menghindari N+1.

---

## 4. Standar Dependency Injection & Binding

Setiap Service dan Repository baru wajib didaftarkan di `app/Providers/RepositoryServiceProvider.php`, dan provider tersebut terdaftar di `bootstrap/providers.php`.

```php
<?php

namespace App\Providers;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Services\AuthServiceInterface;
use App\Contracts\Services\UserServiceInterface;
use App\Repositories\Eloquent\UserRepository;
use App\Services\AuthService;
use App\Services\UserService;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);

        // Service bindings
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(AuthServiceInterface::class, AuthService::class);
    }
}
```

---

## 5. Contoh Alur Kode (Method `store`)

**Controller**

```php
public function __construct(
    private readonly UserServiceInterface $userService,
) {}

public function store(StoreUserRequest $request): JsonResponse
{
    Gate::authorize('create', User::class);

    $user = $this->userService->registerUser($request->validated());

    return response()->json([
        'message' => 'User berhasil ditambahkan',
        'data' => new UserResource($user),
    ], 201);
}
```

**Service**

```php
public function registerUser(array $data): User
{
    // Tidak perlu Hash::make() — cast 'hashed' di model User sudah menanganinya.
    return DB::transaction(function () use ($data): User {
        $user = $this->userRepository->create($data);
        $user->assignRole('user');

        return $user;
    });
}
```

**Repository**

```php
public function create(array $data): User
{
    return $this->model->create($data);
}
```

---

## 6. Do's and Don'ts

| Do's (Lakukan) | Don'ts (Jangan Lakukan) |
|---|---|
| Inject **Interface** di constructor. | Menginstansiasi class langsung dengan `new UserService()`. |
| Gunakan strict type hinting (`string`, `int`, `array`, `JsonResponse`) di argumen & return. | Mengabaikan type hint pada argumen dan return value. |
| Bungkus operasi multi-insert/update dengan `DB::transaction()` di **Service**. | Menjalankan transaction di Controller atau Repository. |
| Taruh otorisasi Spatie di **Policy**. | Menulis `$user->hasRole()` di Controller/Route/Service. |
| Kembalikan data lewat **API Resource**. | Mengembalikan Model mentah (`return $user;`) dari Controller. |
| Gunakan `$request->validated()`. | Menggunakan `$request->all()`. |
| Jalankan `vendor/bin/pint` sebelum commit. | Commit kode yang belum diformat. |
| Tulis Feature test untuk tiap endpoint & Unit test untuk Service. | Merge tanpa test. |

---

## 7. BaseRepository

Untuk mempersingkat kode CRUD, semua Repository meng-extend `BaseRepository`.

**Interface** — `app/Contracts/Repositories/BaseRepositoryInterface.php`

```php
<?php

namespace App\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface BaseRepositoryInterface
{
    /** @param array<int, string> $columns */
    public function all(array $columns = ['*']): Collection;

    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function find(int|string $id): ?Model;

    public function findOrFail(int|string $id): Model;

    /** @param array<string, mixed> $data */
    public function create(array $data): Model;

    /** @param array<string, mixed> $data */
    public function update(Model $model, array $data): Model;

    public function delete(Model $model): bool;
}
```

**Abstract class** — `app/Repositories/BaseRepository.php`

```php
<?php

namespace App\Repositories;

use App\Contracts\Repositories\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository implements BaseRepositoryInterface
{
    public function __construct(
        protected readonly Model $model,
    ) {}

    public function all(array $columns = ['*']): Collection
    {
        return $this->model->newQuery()->get($columns);
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()->latest()->paginate($perPage);
    }

    public function find(int|string $id): ?Model
    {
        return $this->model->newQuery()->find($id);
    }

    public function findOrFail(int|string $id): Model
    {
        return $this->model->newQuery()->findOrFail($id);
    }

    public function create(array $data): Model
    {
        return $this->model->newQuery()->create($data);
    }

    public function update(Model $model, array $data): Model
    {
        $model->fill($data)->save();

        return $model->refresh();
    }

    public function delete(Model $model): bool
    {
        return (bool) $model->delete();
    }
}
```

**Implementasi spesifik** — `app/Repositories/Eloquent/UserRepository.php`

```php
<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;
use App\Repositories\BaseRepository;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->model->newQuery()->where('email', $email)->first();
    }
}
```

`UserRepositoryInterface` cukup `extends BaseRepositoryInterface` dan menambahkan method khusus (`findByEmail`).

---

## 8. Roles & Permissions

| Role | Deskripsi |
|---|---|
| `Super Admin` | Bypass semua Policy via `before()`. Dibuat oleh seeder dari env `SUPER_ADMIN_EMAIL` / `SUPER_ADMIN_PASSWORD`. |
| `user` | Role default untuk setiap akun baru (registrasi web maupun API). |

- Permission dibuat di `RolesAndPermissionsSeeder` dengan `firstOrCreate` (idempotent) dan diakhiri `app()[PermissionRegistrar::class]->forgetCachedPermissions()`.
- Penambahan permission untuk modul baru dilakukan **hanya** lewat seeder tersebut, mengikuti pola `{resource}.{ability}`.

---

## 9. Testing

- **Feature test** (`tests/Feature/Api/...`): satu file per resource. Wajib mencakup matriks otorisasi: unauthenticated → 401, role tanpa permission → 403, Super Admin → 200/201, validasi gagal → 422, atribut sensitif tidak bocor di response.
- **Unit test** (`tests/Unit/Services/...`): Service diuji dengan Repository Interface di-mock (Mockery). Tidak menyentuh database.
- Gunakan `RefreshDatabase` dan jalankan `RolesAndPermissionsSeeder` di `setUp()` untuk Feature test yang butuh role.
- Jalankan: `composer test` dan `vendor/bin/pint --test`.

---

## 10. Checklist Membuat Modul Baru

Contoh untuk resource `Post`:

1. Model + migration + factory (`php artisan make:model Post -mf`).
2. `app/Contracts/Repositories/PostRepositoryInterface.php` (extends `BaseRepositoryInterface`).
3. `app/Repositories/Eloquent/PostRepository.php` (extends `BaseRepository`).
4. `app/Contracts/Services/PostServiceInterface.php` + `app/Services/PostService.php`.
5. Daftarkan kedua binding di `RepositoryServiceProvider`.
6. `app/Policies/PostPolicy.php` + tambahkan permission `posts.*` di `RolesAndPermissionsSeeder`.
7. `app/Http/Requests/Post/StorePostRequest.php` & `UpdatePostRequest.php`.
8. `app/Http/Resources/PostResource.php`.
9. `app/Http/Controllers/Api/PostController.php` (inject `PostServiceInterface`, `Gate::authorize` per method).
10. Route `apiResource('posts', ...)` di `routes/api.php` di balik `auth:sanctum` + `active`.
11. Feature test `tests/Feature/Api/PostApiTest.php` + Unit test `tests/Unit/Services/PostServiceTest.php`.
12. `vendor/bin/pint` → `composer test` → commit.
