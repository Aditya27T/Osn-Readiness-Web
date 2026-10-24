#!/bin/bash
set -e

# Pastikan folder penyimpanan dan cache ada serta memiliki permission yang tepat
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chmod -R 777 storage bootstrap/cache 2>/dev/null || true

# Salin .env.example jika .env belum ada
if [ ! -f ".env" ]; then
    if [ -f ".env.example" ]; then
        echo "[backend] Menyiapkan .env dari .env.example..."
        cp .env.example .env
    fi
fi

# Jalankan composer install jika vendor belum ada
if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
    echo "[backend] Direktori vendor belum ada, menjalankan composer install..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Generate APP_KEY jika belum ada
if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
    echo "[backend] Menghasilkan APP_KEY baru..."
    php artisan key:generate --force
fi

# Tunggu database PostgreSQL siap
if [ -n "$DB_HOST" ]; then
    echo "[backend] Menunggu koneksi database di $DB_HOST:${DB_PORT:-5432}..."
    while ! nc -z "$DB_HOST" "${DB_PORT:-5432}"; do
        sleep 1
    done
    echo "[backend] Database siap terhubung!"
fi

# Jalankan migrasi jika diaktifkan (default: true)
if [ "${AUTO_MIGRATE:-true}" = "true" ]; then
    echo "[backend] Menjalankan migrasi database..."
    php artisan migrate --force || echo "[backend] Peringatan: migrasi dilewati atau sudah termigrasi."
fi

exec "$@"
