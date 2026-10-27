# Panduan Deploy Monorepo ke Satu Server

Panduan ini menjelaskan rancangan deploy **Frontend Vue, Backend Laravel, dan
layanan hitung FastAPI dalam satu VPS**. Domain publik diarahkan ke reverse
proxy; aplikasi dan database berjalan sebagai service terpisah di server yang
sama.

> **Penting:** `docker-compose.yml` di root repo saat ini adalah konfigurasi
> development, bukan konfigurasi production. Jangan menjalankannya langsung
> di server production. Alasan dan prasyarat production dijelaskan di bagian
> [Kondisi konfigurasi repo saat ini](#kondisi-konfigurasi-repo-saat-ini).

## 1. Arsitektur yang dituju

Contoh berikut memakai `siap-osn.id`. Ganti dengan domain milik deployment:

| Hostname | Tujuan |
| --- | --- |
| `siap-osn.id` | Frontend Vue |
| `www.siap-osn.id` | Redirect ke `siap-osn.id` |
| `api.siap-osn.id` | API Laravel |
| Tidak ada domain publik | FastAPI dan PostgreSQL |

Semua hostname publik menunjuk ke IP VPS yang sama. Nginx (atau reverse proxy
setara) di VPS menerima HTTPS dan meneruskan request ke service yang sesuai.

```text
Browser
  ├── https://siap-osn.id       ──> reverse proxy ──> file hasil build Vue
  └── https://api.siap-osn.id   ──> reverse proxy ──> Laravel API
                                                        └── analytics-api:8000
                                                            (Docker network privat)
                                                            └── analytics-db
                                                                (privat)

Laravel ──> app-db (PostgreSQL, privat)
```

Port publik yang dibutuhkan hanya **80 dan 443**. Jangan publikasikan port
PostgreSQL, FastAPI, atau port internal Laravel/frontend.

## 2. DNS dan firewall

1. Buat record DNS `A` untuk `siap-osn.id`, `www.siap-osn.id`, dan
   `api.siap-osn.id` ke IP publik VPS.
2. Pastikan DNS sudah mengarah ke VPS sebelum meminta sertifikat TLS.
3. Izinkan inbound TCP `80` dan `443` untuk web. Batasi SSH ke IP/admin yang
   diperlukan.
4. Jangan membuka port `5432`, `8000`, `8001`, atau port development Vite
   (`5173`) ke internet.
5. Gunakan TLS di reverse proxy. Redirect HTTP ke HTTPS setelah sertifikat
   aktif.

Contoh record:

```text
A  siap-osn.id       <IP-VPS>
A  www.siap-osn.id   <IP-VPS>
A  api.siap-osn.id   <IP-VPS>
```

## 3. Prasyarat konfigurasi production

Sebelum menjalankan aplikasi, siapkan konfigurasi production terpisah, misalnya
`docker-compose.prod.yml`, dan konfigurasi reverse proxy. Konfigurasi tersebut
perlu memastikan bahwa:

- Frontend dibuild sebagai file statis, bukan dijalankan dengan Vite dev server.
- Laravel berjalan dengan runtime production, bukan server development
  `php artisan serve`.
- Laravel, dua database, dan FastAPI berada di jaringan Docker privat.
- Port service tidak di-bind ke interface publik.
- Environment production diberikan dari environment server atau file rahasia
  yang tidak masuk Git.
- Migrasi dijalankan sebagai langkah deployment yang terlihat hasilnya, bukan
  gagal secara diam-diam atau otomatis pada setiap restart.
- Persistent volume disiapkan untuk database dan file Laravel yang memang harus
  bertahan setelah container diganti.

Panduan backend yang lebih rinci tersedia di
[PANDUAN-DEPLOY.md](../apps/Osn-Readiness-Web/docs/PANDUAN-DEPLOY.md).
Petunjuk deployment pilot FastAPI ada di
[README data-analytics](../apps/data-analytics/README.md).

## 4. Environment production

Nilai berikut adalah pola konfigurasi. Simpan nilai rahasia di server, jangan
commit ke repo.

### Frontend

`VITE_API_BASE_URL` ditanam ke bundle ketika frontend dibuild:

```env
VITE_API_BASE_URL=https://api.siap-osn.id/api
```

Jika URL API diubah, frontend perlu dibuild ulang.

### Laravel

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.siap-osn.id
FRONTEND_URL=https://siap-osn.id,https://www.siap-osn.id

DB_CONNECTION=pgsql
DB_HOST=app-db
DB_PORT=5432
DB_DATABASE=<nama-database>
DB_USERNAME=<user-database>
DB_PASSWORD=<password-kuat>

PERHITUNGAN_URL=http://analytics-api:8000
PERHITUNGAN_TOKEN=<secret-internal>
```

Gunakan `APP_KEY` production yang tetap dan rahasia, serta konfigurasi email,
queue worker, cache, dan session sesuai kebutuhan aplikasi. Jangan membuat
`APP_KEY` baru ketika container dimulai ulang.

### FastAPI

```env
INTERNAL_API_TOKEN=<secret-internal-yang-sama-dengan-Laravel>
```

`INTERNAL_API_TOKEN` harus sama persis dengan `PERHITUNGAN_TOKEN`. Jangan
menampilkan nilainya di log atau menyimpan token di Git.

## 5. Routing reverse proxy

Buat dua virtual host HTTPS:

- `siap-osn.id`: sajikan direktori bundle statis hasil build Vue. Semua path
  aplikasi frontend yang bukan file statis harus fallback ke `index.html` agar
  Vue Router dapat memprosesnya.
- `api.siap-osn.id`: teruskan request ke service Laravel pada port internalnya.
  Pertahankan header `Host`, `X-Forwarded-For`, dan
  `X-Forwarded-Proto` supaya Laravel mengenali request HTTPS di belakang proxy.
- `www.siap-osn.id`: redirect permanen ke `https://siap-osn.id`.

Pastikan ukuran body dan timeout proxy memadai untuk endpoint aplikasi. Terapkan
rate limit pada endpoint sensitif di proxy atau aplikasi sesuai kebijakan
operasional.

## 6. Urutan deployment

Langkah berikut merupakan urutan deployment setelah konfigurasi production di
bagian 3 tersedia.

1. Masuk ke VPS melalui SSH dan pasang Docker Engine beserta Docker Compose
   Plugin sesuai dokumentasi resmi distribusi server.
2. Clone repo ke direktori deployment, misalnya `/opt/osn-monorepo`, lalu
   pastikan branch/tag yang akan dirilis sudah dipilih.
3. Siapkan environment production di server. Atur permission supaya hanya
   pengguna deployment yang berwenang dapat membacanya.
4. Pastikan record DNS sudah mengarah ke VPS dan firewall hanya membuka port
   yang diperlukan.
5. Build dan jalankan service dari konfigurasi production:

   ```bash
   cd /opt/osn-monorepo
   docker compose -f docker-compose.prod.yml build
   docker compose -f docker-compose.prod.yml up -d
   ```

6. Jalankan migrasi Laravel secara eksplisit, lalu periksa hasilnya. Sesuaikan
   nama service dengan konfigurasi production:

   ```bash
   docker compose -f docker-compose.prod.yml exec backend \
     php artisan migrate --force
   ```

7. Aktifkan reverse proxy dan sertifikat TLS untuk ketiga hostname publik.
8. Periksa status container, log, dan endpoint health sebelum mengalihkan
   trafik:

   ```bash
   docker compose -f docker-compose.prod.yml ps
   docker compose -f docker-compose.prod.yml logs --tail=100
   ```

   Uji `https://siap-osn.id`, `https://api.siap-osn.id`, serta health check
   FastAPI dari dalam jaringan container. FastAPI tidak perlu dapat diakses
   langsung dari internet.

9. Pastikan queue worker Laravel berjalan jika queue digunakan.
10. Pastikan backup database terjadwal dan pernah diuji proses restore-nya.

> Nama service `backend` dan file `docker-compose.prod.yml` pada contoh harus
> cocok dengan konfigurasi production yang dibuat. File tersebut belum tersedia
> pada root repo saat panduan ini ditulis.

## 7. Kondisi konfigurasi repo saat ini

Konfigurasi root yang tersedia sekarang ditujukan untuk development dan belum
aman dipakai langsung sebagai deployment production:

- Root `docker-compose.yml` menetapkan `APP_ENV=local` dan `APP_DEBUG=true`.
- Frontend dipasang sebagai source volume dan dijalankan lewat Vite pada port
  `5173`.
- Container Laravel memakai `php artisan serve`, memetakan source code, dan
  entrypoint mengubah permission `storage`/`bootstrap/cache` menjadi `777`.
- Port backend dan database dipetakan ke host. Nilai fallback database juga
  memakai kredensial development.
- Container FastAPI di root Compose dijalankan dengan `--reload` dan port-nya
  dipublikasikan.
- Compose FastAPI di `apps/data-analytics` adalah stack pilot terpisah yang
  mengharapkan external network `app-network`. Jangan menyalakan stack itu
  bersamaan dengan layanan `analytics-api` dari root Compose tanpa sengaja
  merancang dua stack/network tersebut.

Sebelum production, buat dan uji konfigurasi Compose khusus production serta
runtime produksi frontend/backend. Setelah itu, perbarui bagian urutan
deployment di dokumen ini agar nama file, service, health check, dan lokasi
environment sesuai konfigurasi final.

## 8. Checklist sebelum membuka akses publik

- [ ] DNS seluruh hostname mengarah ke VPS yang benar.
- [ ] Port publik hanya mencakup SSH yang dibatasi, HTTP, dan HTTPS.
- [ ] TLS aktif dan HTTP diarahkan ke HTTPS.
- [ ] `APP_ENV=production` dan `APP_DEBUG=false`.
- [ ] Frontend memakai `https://api.siap-osn.id/api`, bukan URL localhost.
- [ ] CORS Laravel hanya mengizinkan origin frontend yang diperlukan.
- [ ] `PERHITUNGAN_URL` memakai DNS internal Docker, bukan domain publik atau
  `localhost`.
- [ ] Token Laravel dan FastAPI cocok dan disimpan sebagai secret di server.
- [ ] Port database, FastAPI, dan port internal aplikasi tidak terbuka ke publik.
- [ ] Migrasi berhasil, queue worker aktif bila diperlukan, dan log dapat
  diperiksa.
- [ ] Backup database terjadwal dan prosedur restore sudah diuji.
