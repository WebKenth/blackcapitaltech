#!/bin/bash

# Laravel Devcontainer Setup Script
# This script sets up the Laravel development environment

set -e

echo "🚀 Setting up Laravel development environment..."

# Install additional PHP extensions that Laravel might need
echo "📦 Installing additional PHP extensions..."
sudo apt-get update -qq
sudo apt-get install -y -qq \
    php8.3-sqlite3 \
    php8.3-gd \
    php8.3-curl \
    php8.3-zip \
    php8.3-xml \
    php8.3-mbstring \
    php8.3-bcmath \
    php8.3-intl \
    php8.3-redis \
    sqlite3

# Install Chromium browser for Lighthouse audits
echo "🌐 Installing Chromium browser for Lighthouse PHP..."
sudo apt-get install -y -qq chromium-browser

# Install Composer dependencies
echo "🎼 Installing Composer dependencies..."
composer install --no-interaction --optimize-autoloader

# Install Node.js dependencies  
echo "📦 Installing Node.js dependencies..."
yarn install

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "🔧 Creating .env file..."
    cp .env.example .env
fi

# Generate application key
echo "🔑 Generating application key..."
php artisan key:generate --no-interaction

# Create SQLite database if it doesn't exist
echo "🗃️  Setting up SQLite database..."
touch database/database.sqlite

# Run database migrations
echo "🔄 Running database migrations..."
php artisan migrate --no-interaction

# Clear and cache configuration
echo "⚡ Optimizing Laravel..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Set proper permissions
echo "🔐 Setting permissions..."
chmod -R 775 storage bootstrap/cache
sudo chown -R vscode:vscode storage bootstrap/cache

echo "✅ Laravel development environment setup complete!"
echo ""
echo "🎯 Quick commands:"
echo "  - Start dev server: php artisan serve"
echo "  - Start Vite dev server: yarn dev" 
echo "  - Run tests: php artisan test"
echo "  - Access Filament admin: /admin"
echo "  - Run Lighthouse audits: Available via spatie/lighthouse-php"
echo ""