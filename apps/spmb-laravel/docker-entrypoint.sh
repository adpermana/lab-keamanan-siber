#!/bin/bash
set -e

# Wait for database connection
until php -r "new PDO('mysql:host='.\$_ENV['DB_HOST'].';port='.\$_ENV['DB_PORT'].';dbname='.\$_ENV['DB_DATABASE'], \$_ENV['DB_USERNAME'], \$_ENV['DB_PASSWORD']);" 2>/dev/null; do
    echo "Waiting for database connection..."
    sleep 3
done

echo "Database connected. Running migrations..."
php artisan migrate --force

# Only seed if users table is empty
USER_COUNT=$(php -r "try { \$pdo = new PDO('mysql:host='.\$_ENV['DB_HOST'].';port='.\$_ENV['DB_PORT'].';dbname='.\$_ENV['DB_DATABASE'], \$_ENV['DB_USERNAME'], \$_ENV['DB_PASSWORD']); \$stmt = \$pdo->query('SELECT COUNT(*) FROM users'); echo \$stmt->fetchColumn(); } catch(Exception \$e) { echo '0'; }")
if [ "$USER_COUNT" = "0" ]; then
    echo "Running seeders..."
    php artisan db:seed --force
else
    echo "Database already seeded, skipping..."
fi

echo "Clearing cache..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

exec "$@"
