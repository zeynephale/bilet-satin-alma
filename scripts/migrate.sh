#!/bin/sh
# Migration script to update database schema

echo "🔄 Running database migrations..."

# Docker container database path
DB_PATH="/var/www/data/app.sqlite"

# Run migration
sqlite3 "$DB_PATH" < /var/www/html/database/migrations/add_bus_type.sql

if [ $? -eq 0 ]; then
    echo "✅ Migration completed successfully!"
else
    echo "❌ Migration failed!"
    exit 1
fi




