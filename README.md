# OSN Monorepo

Penggabungan tiga repo proyek OSN ke dalam satu repo, **tanpa menghapus riwayat commit masing-masing**.

## Struktur

| Path | Asal repo | Branch yang digabung | Isi |
|---|---|---|---|
| `apps/Osn-Readiness-Web/` | `Aditya27T/Osn-Readiness-Web` | `siap-integrasi` | Backend Laravel + API |
| `apps/data-analytics/` | `PTI-A-PROJECT/data-analytics` | `integrasi` | Backend Python (FastAPI + Alembic) |
| `apps/siap-osn-fe/` | `PTI-A-PROJECT/Siap-osn-Fe` | `dev` | Frontend Vue (Vite) |

## Menjalankan dengan Docker Compose

Seluruh 3 aplikasi (Frontend, Backend, Layanan Hitung) serta 2 databasenya dapat dijalankan bersamaan dengan Docker:

```bash
# 1. Salin environment variable (opsional bila ingin kustomisasi port)
cp .env.example .env

# 2. Build dan jalankan seluruh container
docker compose up --build
```

Setelah berjalan:
- **Frontend (Vue/Vite):** http://localhost:5173
- **Backend API (Laravel):** http://localhost:8000
- **Layanan Hitung (FastAPI):** http://localhost:8001 (internal: http://analytics-api:8000)
- **Database Laravel (Postgres):** `localhost:5432`
- **Database Analytics (Postgres + pgvector):** `localhost:5433`

Untuk menghentikan:
```bash
docker compose down
```

## Riwayat commit tetap utuh

Setiap repo digabung dengan `git subtree add` tanpa `--squash`, jadi seluruh commit asli
masih ada di repo ini. Hanya lokasi file yang berubah mengikuti struktur folder di atas.

Melihat seluruh riwayat commit (author, tanggal, pesan) sesuai urutan waktu:

```bash
git log --oneline --graph
git log --oneline --author=<nama>
```

Melihat isi sebuah commit tertentu:

```bash
git show <sha>
```

### Catatan: `git log <path>` terbatas

Karena tiap repo digabung lewat merge commit, path file berubah mengikuti prefix folder
(`src/...` menjadi `apps/siap-osn-fe/src/...`). Git tidak bisa menelusuri mundur melewati
merge untuk path lama, jadi `git log -- apps/siap-osn-fe/src/...` hanya menampilkan
commit penggabungan, bukan seluruh riwayat file tersebut.

Semua commit lama tetap ada dan bisa dibaca lewat `git log --graph` atau `git show <sha>`.
Cara ini dipilih agar SHA commit tetap identik dengan repo GitHub asalnya.

Melihat commit dari repo tertentu saja:

## Repo asal tetap utuh

Ketiga repo asal tidak diubah oleh proses penggabungan ini (hanya ada satu commit
penutup untuk file yang sebelumnya belum di-commit). Keduanya bisa dipakai berdampingan.