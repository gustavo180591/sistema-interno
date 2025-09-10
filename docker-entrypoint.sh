#!/bin/sh

# Wait for database to be ready
echo "Waiting for database..."
max_retries=30
retry_count=0

while [ $retry_count -lt $max_retries ]; do
    if php bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1; then
        echo "Database is ready!"
        break
    fi
    retry_count=$((retry_count + 1))
    echo "Database is unavailable - sleeping (attempt $retry_count/$max_retries)"
    sleep 3
done

# Run migrations (ignore errors)
echo "Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction || echo "Migration errors ignored"

# Clear and warm cache (ignore errors)
echo "Clearing cache..."
php bin/console cache:clear || echo "Cache clear errors ignored"

# Create admin user if it doesn't exist
echo "Creating/updating admin user..."
php bin/console app:create-admin admin@sistema.com admin admin123 || echo "Admin user creation errors ignored"

# Update admin password to ensure it's properly hashed
echo "Ensuring admin password is properly hashed..."
php bin/console app:update-admin-password admin@sistema.com admin123 || echo "Password update errors ignored"

# Start PHP-FPM
echo "Starting PHP-FPM..."
php-fpm -F