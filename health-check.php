<?php
// health-check.php - returns a JSON status for the database connection
// Usage (CLI): php health-check.php
// Usage (HTTP): http://<host>/health-check.php

// load DB config
require __DIR__ . '/config/db.php';

if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
}

$result = [
    'connected' => false,
    'tables' => [],
    'tables_count' => 0,
];

try {
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $result['connected'] = true;
    $result['tables'] = $tables;
    $result['tables_count'] = count($tables);
} catch (Exception $e) {
    $result['error'] = $e->getMessage();
}

echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
