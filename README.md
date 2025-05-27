# NBFC HRMS & Loan Management API

## Overview

A comprehensive HRMS and Loan Management backend API built with Laravel 11, designed for NBFCs and organizations to manage employees, attendance, salaries, loans, documents, and analytics. The API is fully tested, secure, and ready for integration with modern frontend dashboards.

## Features

-   Employee Management (CRUD, bulk upload, profile, statistics)
-   Attendance Management (CRUD, bulk upload, filtering, statistics)
-   Salary Management (CRUD, bulk upload, reporting)
-   Loan Management (CRUD, approval, rejection, disbursement, statistics)
-   Document Management (upload, association, deletion)
-   Organization & System Settings
-   Dashboard Analytics (employee/loan stats)
-   Authentication (Laravel Sanctum)
-   Bulk Upload (CSV/Excel for employees, attendance, salary)
-   Comprehensive API documentation (OpenAPI/Swagger)
-   Robust validation, error handling, and security
-   Automated feature tests for all modules

## Technology Stack

-   Laravel 11 (PHP 8.2+)
-   SQLite/MySQL/PostgreSQL (configurable)
-   Laravel Sanctum (API authentication)
-   OpenAPI/Swagger (API docs)
-   PHPUnit (testing)

## Setup Instructions

1. **Clone the repository:**
    ```bash
    git clone <repo-url>
    cd NBFC-anek-API-s
    ```
2. **Install dependencies:**
    ```bash
    composer install
    ```
3. **Copy and configure environment:**
    ```bash
    cp .env.example .env
    # Edit .env for DB and mail settings
    php artisan key:generate
    ```
4. **Run migrations and seeders:**
    ```bash
    php artisan migrate --seed
    ```
5. **Run the development server:**
    ```bash
    php artisan serve
    ```
6. **(Optional) Run tests:**
    ```bash
    php artisan test
    ```

## API Authentication

-   Uses Laravel Sanctum for token-based authentication.
-   Obtain a token via `/api/login` and include it as `Bearer <token>` in the `Authorization` header for all requests.

## Key API Endpoints

### Employee Management

-   `GET /api/employees` — List/filter employees
-   `POST /api/employees` — Create employee
-   `PUT /api/employees/{id}` — Update employee
-   `DELETE /api/employees/{id}` — Delete employee
-   `POST /api/employees/bulk-upload` — Bulk upload employees (CSV/Excel)
-   `GET /api/employees/statistics` — Employee stats

### Attendance Management

-   `GET /api/attendance` — List/filter attendance
-   `POST /api/attendance` — Create attendance
-   `PUT /api/attendance/{id}` — Update attendance
-   `DELETE /api/attendance/{id}` — Delete attendance
-   `POST /api/attendance/bulk-upload` — Bulk upload attendance
-   `GET /api/attendance/statistics` — Attendance stats

### Salary Management

-   `GET /api/salaries` — List salaries
-   `POST /api/salaries` — Create salary
-   `POST /api/salaries/bulk-upload` — Bulk upload salaries

### Loan Management

-   `GET /api/loans` — List/filter loans
-   `POST /api/loans` — Create loan
-   `PUT /api/loans/{id}` — Update loan
-   `DELETE /api/loans/{id}` — Delete loan
-   `POST /api/loans/{id}/approve` — Approve loan
-   `POST /api/loans/{id}/reject` — Reject loan
-   `POST /api/loans/{id}/disburse` — Disburse loan
-   `GET /api/loans/statistics` — Loan stats

### Document Management

-   `GET /api/documents` — List documents
-   `POST /api/documents` — Upload document
-   `DELETE /api/documents/{id}` — Delete document

### Settings & Dashboard

-   `GET /api/settings` — System/organization settings
-   `PUT /api/settings/{key}` — Update setting
-   `GET /api/dashboard` — Dashboard analytics

### Authentication & User

-   `POST /api/login` — User login
-   `POST /api/logout` — Logout
-   `POST /api/change-password` — Change password

## Bulk Upload

-   Endpoints accept CSV/Excel files for employees, attendance, and salary.
-   Validation and error reporting for each row.

## Testing

-   Run `php artisan test` to execute all feature and unit tests.
-   Factories and seeders provided for all major models.

## API Documentation

-   Swagger/OpenAPI docs available via `/api/documentation` (if enabled).
-   All endpoints annotated for auto-generation.

## Contribution Guidelines

-   Fork the repo, create a feature branch, and submit a PR.
-   Follow PSR-12 coding standards.
-   Add/modify tests for new features.

## License

MIT License. See [LICENSE](LICENSE) for details.
