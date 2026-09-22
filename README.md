<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

---

**Quick Navigation:**
[Requirements](#requirements) &nbsp;|&nbsp;
[Installation via Docker (Sail)](#installation-via-docker-sail) &nbsp;|&nbsp;
[Installation via Composer](#installation-via-composer) &nbsp;|&nbsp;
[About Laravel](#about-laravel)

---

## Requirements

Before getting started, make sure your environment meets the following requirements.

**For Docker (Sail) — Recommended:**

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) 20.10 or higher
- [Docker Compose](https://docs.docker.com/compose/install/) v2 or higher
- No local PHP or Composer installation needed

**For Composer (Local):**

- [PHP](https://www.php.net/downloads) 8.3 or higher
- [Composer](https://getcomposer.org/) 2.x
- [Node.js](https://nodejs.org/) 20 or higher (for frontend assets)
- A supported database: MySQL 8.x / PostgreSQL / SQLite

---

## Installation via Docker (Sail)

[Laravel Sail](https://laravel.com/docs/sail) provides a Docker-based development environment. No local PHP or Composer is required.

**1. Clone the repository:**

```bash
git clone <repository-url> Osn-Readiness-Web
cd Osn-Readiness-Web
```

**2. Copy the environment file:**

```bash
cp .env.example .env
```

**3. Install dependencies using the Sail installer image:**

```bash
docker run --rm \
    --user "$(id -u):$(id -g)" \
    -e COMPOSER_HOME=/tmp/composer \
    -v "$(pwd)":/opt \
    -w /opt \
    laravelsail/php85-composer:latest \
    composer install --ignore-platform-reqs
```

**4. Build and start the containers:**

```bash
./vendor/bin/sail build
./vendor/bin/sail up -d
```

**5. Generate application key and run migrations:**

```bash
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
```

**6. Open the application:**

Visit [http://localhost:8000](http://localhost:8000) in your browser.

**Common Sail commands:**

```bash
# Stop containers
./vendor/bin/sail down

# Run Artisan commands
./vendor/bin/sail artisan make:controller ExampleController

# Open a shell inside the container
./vendor/bin/sail shell

# Install a Composer package
./vendor/bin/sail composer require vendor/package

# Run tests
./vendor/bin/sail test
```

---

## Installation via Composer

Use this method if you prefer to run the project directly on your local machine without Docker.

**1. Clone the repository:**

```bash
git clone <repository-url> Osn-Readiness-Web
cd Osn-Readiness-Web
```

**2. Install PHP dependencies:**

```bash
composer install
```

**3. Copy the environment file and configure it:**

```bash
cp .env.example .env
```

Open `.env` and update the database connection settings to match your local setup:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=osn_readiness_web
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
```

**4. Generate application key:**

```bash
php artisan key:generate
```

**5. Run database migrations:**

```bash
php artisan migrate
```

**6. Install frontend dependencies and build assets:**

```bash
npm install
npm run build
```

**7. Start the development server:**

```bash
php artisan serve
```

Visit [http://localhost:8000](http://localhost:8000) in your browser.

---

## About Laravel


Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
