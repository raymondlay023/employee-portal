#!/bin/sh
set -e

# Wait for database connection to be ready before running migrations
if [ "$DB_CONNECTION" = "mysql" ]; then
    echo "Waiting for database connection..."
    until php -r "
    \$host = getenv('DB_HOST') ?: '127.0.0.1';
    \$port = getenv('DB_PORT') ?: '3306';
    \$db   = getenv('DB_DATABASE') ?: 'laravel';
    \$user = getenv('DB_USERNAME') ?: 'root';
    \$pass = getenv('DB_PASSWORD') ?: '';
    try {
        \$pdo = new PDO(\"mysql:host=\$host;port=\$port;dbname=\$db\", \$user, \$pass);
        exit(0);
    } catch (PDOException \$e) {
        exit(1);
    }
    "; do
        echo "Database not ready yet, retrying in 2 seconds..."
        sleep 2
    done
    echo "Database is ready!"
fi

# ... after the "Database is ready!" echo
if [ ! -f "/var/www/html/vendor/autoload.php" ]; then
    echo "Vendor folder not found! Something is wrong with the volume mount."
    exit 1
fi

# Run Laravel optimizations to cache runtime environment configuration
echo "Caching configuration and routes..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage symlink if not already present
echo "Ensuring storage symlink exists..."
# php artisan storage:link --force
rm -rf /var/www/html/public/storage
ln -s ../storage/app/public /var/www/html/public/storage

# Only run migrations in the main app container to avoid race conditions
if [ "$1" = "php-fpm" ]; then
    echo "Running database migrations..."
    php artisan migrate --force
fi

# Execute the container command (e.g. php-fpm or queue worker)
echo "Starting application command: $@"
exec "$@"
