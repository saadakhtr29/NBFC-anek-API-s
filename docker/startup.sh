#!/bin/bash
set -e

# Debug: Print environment variables
echo "Environment variables:"
env

# Debug: Print current directory and its contents
echo "Current directory: $(pwd)"
echo "Directory contents:"
ls -la

# Debug: Print Laravel log
echo "Laravel log contents:"
if [ -f storage/logs/laravel.log ]; then
  cat storage/logs/laravel.log
else
  echo "No laravel.log file found."
fi

# Clear Laravel config
if ! php artisan config:clear; then
  echo "[ERROR] config:clear failed"
fi
if ! php artisan cache:clear; then
  echo "[ERROR] cache:clear failed"
fi

# Debug: Print database configuration
echo "Database configuration:"
if ! php artisan tinker --execute="print_r(config('database.connections.mysql'));"; then
  echo "[ERROR] Could not print DB config"
fi

# Run migrations
echo "Running migrations..."
if ! php artisan migrate --force; then
  echo "[ERROR] Migrations failed"
fi

# Start PHP server
echo "Starting PHP server..."
exec php -S 0.0.0.0:8080 -t public 