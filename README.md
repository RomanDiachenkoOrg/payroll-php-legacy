# Acme Payroll (PHP / MySQL)

In-house payroll and HR system: employees and departments, weekly timesheets with
manager approval, monthly pay runs and payslips, department cost reports and an
audit log. Plain procedural PHP, one page per file, MySQL database.

## Tech stack

- PHP 7.4 (procedural, no framework), mysqli
- MySQL 5.7
- Apache (php:7.4-apache image)
- jQuery 1.12, table based layout, `css/style.css`
- PHPUnit 9 for the calculation functions
- Docker Compose for local development (optional phpMyAdmin)

## Prerequisites

- Docker 20.10+ with Docker Compose v2 (`docker compose`)
- Free port 8080 (web) - change with `WEB_PORT`, e.g. `WEB_PORT=8088 docker compose up`
- To run the unit tests outside Docker: PHP 7.4+ and PHPUnit 9

## How to run

```bash
docker compose up -d
```

Open http://localhost:8080 . On the first start MySQL loads `db/01_schema.sql` and
`db/02_seed.sql` from `docker-entrypoint-initdb.d` (give it ~20 seconds).

- phpMyAdmin (optional): `docker compose --profile tools up -d` then http://localhost:8081 (user `payroll` / `payroll`)
- Stop: `docker compose down` - reset the database: `docker compose down -v`

The `www/` folder is mounted into the container, so edits are live.

Database settings come from environment variables (`DB_HOST`, `DB_USER`, `DB_PASS`,
`DB_NAME`, see `www/config.php`). To run without Docker, point an Apache/PHP 7.4 vhost
at `www/`, import the two SQL files into MySQL 5.7 and set those variables.

## Demo accounts

Password for all: **`Passw0rd!`**

| Login | Role | Access |
|---|---|---|
| `admin@acme.test` | Admin | everything |
| `manager@acme.test` | Manager (Engineering) | Engineering employees, approve/reject Engineering timesheets, reports |
| `employee@acme.test` | Employee | own profile, own payslips, own timesheets |

Seed data: 5 departments, 25 employees (salaried and hourly, 3 inactive), salary
history, timesheets in all statuses, pay runs for Sep and Oct 2026 (paid) and a draft
for Nov 2026, audit log entries.

## Payroll rules

- Salaried: gross = annual salary / 12
- Hourly: gross = rate x regular hours + rate x 1.5 x overtime (hours over 40 in a week, approved timesheets only)
- Income tax on monthly gross: 0-1,000 0%, 1,000.01-4,000 15%, above 4,000 25% (editable on *Tax & Rates*)
- Social contribution 5% of gross; pension 3% of gross if opted in; other deductions per employee
- net = gross - tax - social - pension - other, rounded to cents (half up), USD

## Pages

| Page | Description | Roles |
|---|---|---|
| `login.php` / `logout.php` | Login (validation, wrong password message), logout | all |
| `dashboard.php` | Headcount, last payroll, pending timesheets, next pay date, bar chart | all (by role) |
| `employees.php` | List with search, department/status filter, sorting, paging | admin, manager |
| `employee_edit.php` | Add / edit employee (validation) | admin |
| `employee_view.php` | Profile, salary history, payslips, deactivate (with confirmation) | admin, manager, self |
| `salary_raise.php` | Add raise | admin |
| `profile.php` | Redirects to your own employee record | employees |
| `departments.php`, `department_edit.php`, `department_delete.php` | Department CRUD (no delete with active employees) | admin (manager read-only) |
| `timesheets.php`, `timesheet_edit.php`, `timesheet_view.php`, `timesheet_action.php` | Weekly timesheets, submit, approve/reject with reason | all |
| `payruns.php`, `payrun_new.php`, `payrun_view.php`, `payrun_action.php` | Pay runs: create, calculate, review, approve, mark paid (locked) | admin |
| `payslip.php` (`&print=1`), `payslip_csv.php` | Payslip breakdown, print view, CSV export | admin, own payslips |
| `reports.php`, `report_csv.php` | Payroll cost by department, CSV export | admin, manager |
| `settings.php` | Tax brackets and rates | admin |
| `users.php` | User list | admin |
| `audit.php` | Audit log with filters | admin |

## Project structure

```
www/                    document root
  config.php            settings (DB from environment)
  header.php footer.php page layout, role based menu
  includes/db.php       mysqli connection + helpers
  includes/functions.php session, auth, flash messages, misc helpers
  includes/payroll_calc.php  payroll calculations (unit tested)
  includes/timesheet_lib.php, report_lib.php
  *.php                 one file per page
  css/ js/
db/01_schema.sql        tables
db/02_seed.sql          demo data
docker/php/             Dockerfile and php.ini for the web container
tests/                  PHPUnit tests
docker-compose.yml
```

## Unit tests

```bash
phpunit                 # PHPUnit 9, uses phpunit.xml
```

or without a local PHP:

```bash
docker run --rm -v "$PWD":/app -w /app php:7.4-cli sh -c "curl -sSL -o /tmp/phpunit.phar https://phar.phpunit.de/phpunit-9.phar && php /tmp/phpunit.phar"
```

GitHub Actions (`.github/workflows/ci.yml`) lints all PHP files, runs the tests and builds the web image.
