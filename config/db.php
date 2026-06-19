<?php
require_once __DIR__ . '/load_env.php';

loadEnvFile(__DIR__ . '/../.env');

// Database configuration with environment-variable support and sensible defaults
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$dbname = getenv('DB_NAME') ?: 'musa_beauty';
$user = getenv('DB_USER') ?: 'musa_user';
$pass = getenv('DB_PASS') ?: 'musa_pass';

$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
$dsnSocket = "mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=$dbname;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    try {
        $pdo = new PDO($dsnSocket, $user, $pass, $options);
    } catch (PDOException $fallbackError) {
        die('Database connection failed: ' . $fallbackError->getMessage());
    }
}
