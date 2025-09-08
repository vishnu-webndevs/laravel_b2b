<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About B2B Application

This is a B2B (Business-to-Business) application built with Laravel framework. It provides vendor management capabilities where users can submit vendor requests and administrators can approve or reject them.

## Setup

1. Clone the repository
2. Run `composer install`
3. Copy `.env.example` to `.env` and configure your database settings
4. Run `php artisan migrate --seed`
5. Run `php artisan key:generate`
6. Run `php artisan serve` to start the development server

## Vendor Request Status Values

The application uses numerical status values for vendor requests:

- `0`: Pending - Vendor request has been submitted but not yet reviewed
- `1`: Approved - Vendor request has been approved by administrator
- `2`: Rejected - Vendor request has been rejected by administrator

## API Endpoints

- `/api/register` - User registration
- `/api/login` - User authentication
- `/api/vendor-requests` - Vendor request submission and management
- `/api/admin/vendor-requests` - Administrator vendor request management

## Database Migrations

To refresh the database and apply all migrations:
```bash
php artisan migrate --seed
```

## Contributing

Thank you for considering contributing to the B2B application!

## Security Vulnerabilities

If you discover a security vulnerability, please send an e-mail to the development team.

## License

The B2B application is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
