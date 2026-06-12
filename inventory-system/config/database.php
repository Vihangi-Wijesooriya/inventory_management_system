<?php
/**
 * Database connection (PDO + MySQL).
 * Returns a single shared PDO instance.
 */

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $host    = 'localhost';
    $dbname  = 'inventory';
    $user    = 'root';
    $pass    = '';                 // XAMPP default
    $charset = 'utf8mb4';

    $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        // In production: log the error, show generic message.
        die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
    }

    return $pdo;
}
