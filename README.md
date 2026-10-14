# OSN Monorepo

Penggabungan tiga repo proyek OSN ke dalam satu repo, **tanpa menghapus riwayat commit masing-masing**.

## Struktur

| Path | Asal repo | Branch yang digabung | Isi |
|---|---|---|---|
| `apps/Osn-Readiness-Web/` | `Aditya27T/Osn-Readiness-Web` | `siap-integrasi` | Backend Laravel + API |
| `apps/data-analytics/` | `PTI-A-PROJECT/data-analytics` | `integrasi` | Backend Python (FastAPI + Alembic) |
| `apps/siap-osn-fe/` | `PTI-A-PROJECT/Siap-osn-Fe` | `dev` | Frontend Vue (Vite) |

## Riwayat commit tetap utuh

Setiap repo digabung dengan `git subtree add` tanpa `--squash`, jadi seluruh commit asli
masih ada di repo ini. Hanya lokasi file yang berubah mengikuti struktur folder di atas.

Melihat riwayat commit sebuah app:

```bash
git log --oneline apps/siap-osn-fe
git log --follow apps/siap-osn-fe/src/views/siswa/PretestView.vue
```

Melihat commit dari repo tertentu saja:

## Repo asal tetap utuh

Ketiga repo asal tidak diubah oleh proses penggabungan ini (hanya ada satu commit
penutup untuk file yang sebelumnya belum di-commit). Keduanya bisa dipakai berdampingan.