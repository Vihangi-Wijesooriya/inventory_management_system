<?php
/**
 * Database connection (PDO + MySQL).
 * DB_ENV: 'local' (XAMPP) or 'live' (Aiven cloud MySQL, SSL required).
 */

const DB_ENV = 'local';

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    if (DB_ENV === 'live') {
        $host   = 'mysql-cb7510a-vihangiwijesooriya09-ba25.h.aivencloud.com';
        $port   = 26742;
        $dbname = 'inventory_db';
        $user   = 'avnadmin';
        $pass   = 'AVNS_4rg5_ltNa4hD37RYYDk';
        $sslCa  = __DIR__ . '/ca.pem';
    } else {
        $host   = 'localhost';
        $port   = 3306;
        $dbname = 'inventory_db';
        $user   = 'root';
        $pass   = '';
        $sslCa  = null;
    }

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    if ($sslCa !== null) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
    }

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        error_log('DB connection failed: ' . $e->getMessage());
        http_response_code(500);
        exit('Database connection failed.');
    }

    return $pdo;
}
