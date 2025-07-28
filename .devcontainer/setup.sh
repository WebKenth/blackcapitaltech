#!/bin/bash

# Laravel Devcontainer Setup Script
# This script sets up the Laravel development environment

set -e

echo "🚀 Setting up Laravel development environment..."

# Install additional PHP extensions that Laravel might need
echo "📦 Installing additional PHP extensions..."
sudo apt-get update -qq

# Install system dependencies for PHP extensions
sudo apt-get install -y -qq \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    sqlite3

# Install PHP extensions using docker-php-ext-install
echo "🔧 Creating PHP configuration directory..."
sudo mkdir -p /usr/local/etc/php/conf.d

echo "🔧 Installing PHP extensions..."
sudo docker-php-ext-configure gd --with-freetype --with-jpeg
sudo docker-php-ext-install -j$(nproc) \
    intl \
    zip \
    bcmath \
    gd

# Disable and remove Xdebug to avoid connection errors and improve performance
echo "🔧 Disabling Xdebug..."
sudo rm -f /usr/local/etc/php/conf.d/xdebug.ini
sudo rm -f /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Install Chromium browser for Lighthouse audits
echo "🌐 Installing Chromium browser for Lighthouse PHP..."
sudo apt-get install -y -qq chromium-browser

# Install Composer dependencies
echo "🎼 Installing Composer dependencies..."
composer install --no-interaction --optimize-autoloader

# Install Node.js dependencies  
echo "📦 Installing Node.js dependencies..."
npm install

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
echo "  - Start Vite dev server: npm run dev" 
echo "  - Run tests: php artisan test"
echo "  - Access Filament admin: /admin"
echo "  - Run Lighthouse audits: Available via spatie/lighthouse-php"
echo ""