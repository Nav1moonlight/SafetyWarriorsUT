#!/bin/sh
set -e

echo "=== Safety Warrior Startup ==="

# Mount point: /data (persistent volume di Fly.io)
# Simpan SQLite di sana agar tidak hilang saat redeploy
DATA_DIR="/data"
DB_FILE="$DATA_DIR/database.sqlite"

# Pastikan direktori /data ada dan bisa ditulis
mkdir -p "$DATA_DIR"

# Symlink database ke persistent volume
if [ ! -f "$DB_FILE" ]; then
    echo "Membuat database SQLite baru di $DB_FILE"
    touch "$DB_FILE"
fi

# Hapus file lama dan symlink ke volume
rm -f /var/www/html/database/database.sqlite
ln -sf "$DB_FILE" /var/www/html/database/database.sqlite

# Storage symlink & permissions
mkdir -p "$DATA_DIR/storage/app/public/drive_cache"
rm -rf /var/www/html/storage/app/public
ln -sf "$DATA_DIR/storage/app/public" /var/www/html/storage/app/public

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
chown -R www-data:www-data "$DATA_DIR"

# Laravel setup
echo "Menjalankan migrasi..."
php /var/www/html/artisan migrate --force --no-interaction

echo "Optimizing..."
php /var/www/html/artisan config:cache
php /var/www/html/artisan route:cache
php /var/www/html/artisan view:cache

# Log dirs untuk supervisor
mkdir -p /var/log/supervisor

echo "=== Semua siap. Menjalankan supervisor... ==="
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
