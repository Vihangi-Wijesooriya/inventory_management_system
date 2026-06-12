<?php
/**
 * Application configuration constants.
 * Edit BASE_URL if you change the project folder name in htdocs.
 */

// --- Site identity ---
define('APP_NAME', 'Inventory Management System');
define('APP_VERSION', '1.0.0');

// --- URLs / paths ---
// If your XAMPP URL is http://localhost/inventory-system/ keep this as-is.
define('BASE_URL', 'http://localhost:8000');
define('BASE_PATH', dirname(__DIR__));            // absolute filesystem root
define('UPLOAD_PATH', BASE_PATH . '/public/uploads');
define('UPLOAD_URL',  BASE_URL  . '/public/uploads');

// --- Session & security ---
define('SESSION_NAME', 'INVSYS_SESSION');
define('SESSION_LIFETIME', 60 * 60 * 2);          // 2 hours

// --- Business defaults ---
define('LOW_STOCK_DEFAULT_THRESHOLD', 10);
define('CURRENCY_SYMBOL', 'Rs.');
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i');

// --- Error reporting (turn off display in production) ---
error_reporting(E_ALL);
ini_set('display_errors', '1');                   // set to '0' in production
