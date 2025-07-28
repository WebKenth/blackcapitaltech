# Devcontainer Setup

This project includes a devcontainer configuration that provides a complete Laravel development environment for GitHub Codespaces and VS Code Remote Containers.

## What's Included

- **PHP 8.3** with all Laravel-required extensions
- **Node.js 20** for frontend development with Vite
- **SQLite** database support  
- **Composer** for PHP dependency management
- **Chromium browser** for Lighthouse performance audits
- **Git** and GitHub CLI
- **VS Code extensions** for Laravel/PHP development

## Getting Started

### GitHub Codespaces
1. Click the "Code" button on the repository
2. Select "Create codespace on main"
3. Wait for the environment to build and setup automatically
4. Start developing!

### VS Code Remote Containers
1. Install the "Remote - Containers" extension in VS Code
2. Clone this repository locally
3. Open in VS Code
4. When prompted, click "Reopen in Container"
5. Wait for the setup to complete

## Development Commands

Once the container is running, you can use these commands:

```bash
# Start the Laravel development server (accessible on port 8000)
php artisan serve

# Start the Vite development server for frontend assets (port 5173)
yarn dev

# Run tests
php artisan test

# Run database migrations
php artisan migrate

# Access Filament admin panel at http://localhost:8000/admin

# Run Lighthouse performance audits using spatie/lighthouse-php
# The Chromium browser is available for headless auditing
```

## Ports

The devcontainer automatically forwards these ports:
- **8000**: Laravel development server
- **5173**: Vite development server

## Database

The project uses SQLite by default, which is perfect for development. The database file is created automatically at `database/database.sqlite`.

## Troubleshooting

If you encounter any issues:

1. **Rebuild the container**: In VS Code, use Command Palette > "Remote-Containers: Rebuild Container"
2. **Check setup logs**: The setup script output is shown during container creation
3. **Manual setup**: Run `.devcontainer/setup.sh` manually if needed

## What Gets Set Up Automatically

The devcontainer setup script automatically:
- Installs all PHP and Node.js dependencies
- Installs Chromium browser for Lighthouse audits
- Creates the `.env` file from `.env.example`
- Generates the Laravel application key
- Creates the SQLite database
- Runs database migrations
- Sets proper file permissions
- Clears Laravel caches