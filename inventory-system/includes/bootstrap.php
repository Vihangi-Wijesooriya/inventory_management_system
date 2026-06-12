<?php
/**
 * Bootstrap file - included at the top of every page.
 * Loads config, starts session, sets up DB and helpers.
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

// --- Secure session setup ---
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        // 'secure' => true,   // enable when serving over HTTPS
    ]);
    session_start();
}

// Regenerate session ID periodically to mitigate fixation
if (!isset($_SESSION['_regen_at'])) {
    $_SESSION['_regen_at'] = time();
} elseif (time() - $_SESSION['_regen_at'] > 1800) {   // every 30 min
    session_regenerate_id(true);
    $_SESSION['_regen_at'] = time();
}
