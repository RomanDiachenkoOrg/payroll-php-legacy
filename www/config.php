<?php
/*
 * Payroll system configuration
 *
 * Database settings can be overridden with environment variables
 * (see docker-compose.yml).
 */

define('DB_HOST', getenv('DB_HOST') ? getenv('DB_HOST') : 'localhost');
define('DB_USER', getenv('DB_USER') ? getenv('DB_USER') : 'payroll');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'payroll');
define('DB_NAME', getenv('DB_NAME') ? getenv('DB_NAME') : 'payroll');

define('APP_NAME', 'Acme Payroll');
define('APP_VERSION', '2.3.1');
define('EMPLOYEES_PER_PAGE', 10);
define('DATE_FMT', 'm/d/Y');

error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', getenv('APP_DEBUG') ? '1' : '0');
date_default_timezone_set('America/New_York');
