# Laravel Project Setup Guide

This is a Laravel web application project. The project includes database configuration, migrations, models, controllers, routes, and a complete application structure ready for development.

## Project Information
- **Framework**: Laravel 12
- **Language**: PHP 8.1+
- **Database**: SQLite (default, configurable to MySQL/PostgreSQL)
- **Setup Date**: May 22, 2026

## Project Structure
```
├── app/                    # Application core logic
│   ├── Models/            # Eloquent models
│   ├── Controllers/       # HTTP controllers
│   └── Services/          # Business logic services
├── routes/                # Route definitions
│   └── web.php            # Web routes
├── database/              # Database files
│   ├── migrations/        # Database migrations
│   └── seeders/           # Database seeders
├── resources/             # Frontend assets
│   ├── views/             # Blade templates
│   ├── css/               # Stylesheets
│   └── js/                # JavaScript files
├── config/                # Configuration files
├── public/                # Web root
├── storage/               # File storage
└── tests/                 # Test suite
```

## Getting Started

### Prerequisites
- PHP 8.1 or higher
- Composer
- Node.js & npm (for frontend compilation)
- A database (SQLite, MySQL, or PostgreSQL)

### Installation Steps

1. **Dependencies are already installed**. Check if needed:
   ```bash
   composer install
   npm install
   ```

2. **Environment Setup** (if needed):
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Database Setup**:
   ```bash
   php artisan migrate
   php artisan migrate:seed    # Optional: seed sample data
   ```

4. **Compile Frontend Assets**:
   ```bash
   npm run dev              # Development mode
   npm run build            # Production build
   ```

5. **Start Development Server**:
   ```bash
   php artisan serve
   ```
   The application will be available at `http://localhost:8000`

## Essential Artisan Commands

### Create Components
```bash
php artisan make:model ModelName -m      # Create model with migration
php artisan make:controller ControllerName
php artisan make:migration create_table_name
php artisan make:request FormRequest
php artisan make:middleware MiddlewareName
php artisan make:event EventName
php artisan make:listener ListenerName
```

### Database
```bash
php artisan migrate                      # Run migrations
php artisan migrate:rollback             # Undo last migration
php artisan migrate:refresh              # Rollback and re-run
php artisan migrate:fresh                # Drop all tables and re-run
php artisan db:seed                      # Run seeders
php artisan tinker                       # Interactive shell
```

### Debugging
```bash
php artisan route:list                   # List all routes
php artisan config:show                  # Show configuration
php artisan cache:clear                  # Clear caches
php artisan storage:link                 # Link storage directory
```

## Configuration Files

- `.env` - Environment variables (database, mail, cache settings)
- `config/app.php` - Application configuration
- `config/database.php` - Database configuration
- `config/auth.php` - Authentication configuration
- `config/mail.php` - Mail configuration

## Database

The project uses SQLite by default (stored in `database/database.sqlite`).

To use MySQL/PostgreSQL:
1. Update `.env`:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your_database
   DB_USERNAME=root
   DB_PASSWORD=
   ```

2. Run migrations: `php artisan migrate`

## Development Workflow

1. Create models and migrations:
   ```bash
   php artisan make:model Post -m
   ```

2. Define migrations in `database/migrations/`

3. Create controllers:
   ```bash
   php artisan make:controller PostController
   ```

4. Add routes in `routes/web.php`

5. Create views in `resources/views/`

6. Run tests:
   ```bash
   php artisan test
   php artisan test --filter=TestName
   ```

## Testing

PHPUnit is already configured. Run tests with:
```bash
php artisan test                         # Run all tests
php artisan test --filter=UserTest       # Run specific test
php artisan test --parallel              # Run tests in parallel
```

## Deployment

### Production Build
```bash
composer install --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build
```

### Server Requirements
- PHP 8.1+ with extensions: OpenSSL, PDO, Mbstring, Tokenizer, JSON, Ctype, BCMath
- Web server (Apache/Nginx)
- Database (MySQL 5.7+, PostgreSQL 10+, SQLite)

## Troubleshooting

**"No such file or directory" errors:**
```bash
composer install
php artisan storage:link
```

**Permission errors:**
```bash
chmod -R 775 storage bootstrap/cache
```

**Cache issues:**
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

## Useful Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Laravel API Reference](https://laravel.com/api)
- [Eloquent ORM](https://laravel.com/docs/eloquent)
- [Blade Templates](https://laravel.com/docs/blade)
- [Database Migrations](https://laravel.com/docs/migrations)
- [Routing](https://laravel.com/docs/routing)
- [Authentication](https://laravel.com/docs/authentication)

## Next Steps

1. Create your first model: `php artisan make:model Post -m`
2. Define database schema in migrations
3. Create controllers: `php artisan make:controller PostController`
4. Define routes in `routes/web.php`
5. Create views in `resources/views/`
6. Run migrations: `php artisan migrate`
7. Start development server: `php artisan serve`

---

**Project initialized**: May 22, 2026
**Framework**: Laravel 12 with all dependencies installed
