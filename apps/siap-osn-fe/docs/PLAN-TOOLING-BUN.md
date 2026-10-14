# Plan Tooling: Bun untuk Frontend — SIAP OSN

Satu sumber kebenaran untuk semua hal Bun di `siap-osn-fe`.
Plan fitur (auth, modul soal, dst.) tidak mengulang detail tooling — cukup rujuk file ini.

## 1. Prasyarat

- Bun **1.4.2** sudah terinstall (mesin ini). Cek: `bun --version`.
- Node **24.14.1 tetap dipertahankan** sebagai fallback untuk:
  `create-vue` postinstall, `eslint`, dan khususnya `vitest`.
  Bun di sini berperan sebagai **package manager + runtime dev/build**,
  bukan pengganti Node 100%.
- Tidak perlu install pnpm / npm tambahan.

## 2. Scaffold (create-vue via Bun)

Perintah resmi dari Vue docs untuk Bun
(terverifikasi 2026-09-25: varian `bunx` non-interaktif, tanpa separator `--`):

```bash
cd /Volumes/PS50U
bunx create-vue@latest <nama-tmp> --router --pinia --vitest --eslint --prettier --bare
```

> Catatan: `bun create vue@latest <nama-tmp> -- --router ...`
> (dengan separator `--`) masuk ke mode interaktif di Bun 1.4.2,
> jadi pakai bentuk `bunx` di atas untuk hasil non-interaktif.

Catatan:

- Tolak TypeScript dan tolak opsi "Vue 3.6 RC" (tetap Vue 3.5, lihat plan auth).
- `--bare` menghilangkan contoh komponen bawaan.
- `create-vue` menolak scaffold ke folder non-empty.
  Karena `siap-osn-fe/` sudah berisi `docs/`, strateginya:
  scaffold ke folder `-tmp`, lalu `cp` isinya ke `siap-osn-fe/`, lalu hapus `-tmp`.
- Setelah scaffold: `bun install` (bukan `npm install` / `pnpm install`).

## 3. Install dependency

```bash
cd siap-osn-fe
bun install
bun add axios @vue/devtools-api
bun add primevue@4.5.5 @primeuix/themes@2.0.3 primeicons@7.0.0 tailwindcss-primeui
bun add -d tailwindcss @tailwindcss/vite
```

- Hasilkan `bun.lock` (binary). **`bun.lock` di-commit** sebagai pengganti `pnpm-lock.yaml`.
- Pin PrimeVue 4 karena alasan lisensi (v5 komersial). Lihat plan auth.
- Bila `bun install` menanyakan `trustedDependencies`
  (postinstall esbuild/vite diblok), jalankan sekali:
  `bun install --trust`.

## 4. Scripts `package.json` standar

```json
{
  "scripts": {
    "dev": "bunx --bun vite",
    "build": "bunx --bun vite build",
    "preview": "bunx --bun vite preview",
    "test:unit": "vitest run",
    "lint": "eslint . --fix",
    "format": "prettier --write src/"
  }
}
```

Aturan `--bun`:

- Pakai `bunx --bun vite` untuk `dev` / `build` / `preview`
  agar Vite jalan di **runtime Bun** (startup lebih cepat),
  bukan fallback ke Node via shebang. Ini rekomendasi resmi Bun docs untuk Vite.
- **Jangan** pakai `--bun` untuk `test:unit` dan `lint`.
  Keduanya jalan di Node (shebang default). Alasannya di §5.

Jalankan dengan:

```bash
bun run dev
bun run build
bun run test:unit
bunx eslint .
```

## 5. Test: tetap Vitest + jsdom (tanpa `bun test`)

- Tetap `vitest` + `@vue/test-utils` + `jsdom` dari `create-vue`.
- Jangan migrasi ke `bun test` pada tahap ini:
  `bun test` tidak mendukung penuh SFC `.vue` + `vi.mock`,
  dan `bunx --bun vitest run` diketahui hang (issue `oven-sh/bun#4145`,
  penyebab `tinypool` / `worker_threads` belum didukung Bun).
- `bun run test:unit` tetap valid: Bun hanya berperan sebagai
  runner script, runtime test-nya Node. Ini yang diinginkan.

## 6. Pin versi: `overrides` (bukan `pnpm.overrides`)

```json
{
  "overrides": {
    "primevue": "4.5.5",
    "@primeuix/themes": "2.0.3",
    "primeicons": "7.0.0"
  }
}
```

- Bun membaca `overrides` gaya npm di root `package.json`.
- Hanya top-level yang didukung; nested overrides tidak didukung Bun.
- Jangan pakai field `pnpm.overrides` — diabaikan oleh Bun.

## 7. `.gitignore` dan lockfile

- Commit: `bun.lock`.
- Ignore: `node_modules/`, `dist/`, `.env` (`.env.example` di-commit).
- Tidak ada `pnpm-lock.yaml` di repo ini.

## 8. CI

```yaml
- uses: oven-sh/setup-bun@v2
  with:
    bun-version: latest
- run: bun install --frozen-lockfile
- run: bun run test:unit
- run: bun run build
```

## 9. Troubleshooting

| Gejala | Penyebab | Solusi |
|---|---|---|
| `vitest` hang saat dijalankan | Dipaksa jalan di runtime Bun (`--bun`) | Jalankan `bun run test:unit` tanpa `--bun` (runtime Node) |
| Postinstall `esbuild` / Vite diblok | `trustedDependencies` belum diizinkan | `bun install --trust` sekali |
| Scaffold gagal "folder not empty" | `create-vue` menolak folder berisi `docs/` | Scaffold ke folder `-tmp` lalu `cp` |
| Versi PrimeVue naik ke 5.x | Pakai `latest` atau tanpa pin | Selalu pin `4.5.5 / 2.0.3 / 7.0.0` + `overrides` |
| Login gagal / cookie tidak terkirim | Origin bukan persis `http://localhost:5173` | `strictPort: true`, jangan buka via `127.0.0.1` atau port lain — tidak terkait Bun |
