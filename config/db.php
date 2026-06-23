<?php
require_once __DIR__ . '/load_env.php';

$envPath = __DIR__ . '/../.env';
$envExamplePath = __DIR__ . '/../.env.example';

loadEnvFile($envPath);
if (!is_file($envPath) && is_file($envExamplePath)) {
    loadEnvFile($envExamplePath);
}

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

function connectDatabase() {
    global $dsn, $dsnSocket, $user, $pass, $options;
    
    $attempts = 3;
    $delay = 1; // seconds
    $lastError = null;
    
    // Try TCP connection first
    for ($i = 0; $i < $attempts; $i++) {
        try {
            return new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            $lastError = $e;
            if ($i < $attempts - 1) {
                usleep($delay * 1000000);
                $delay *= 2; // Exponential backoff
            }
        }
    }
    
    // Try socket connection as fallback
    try {
        return new PDO($dsnSocket, $user, $pass, $options);
    } catch (PDOException $socketError) {
        $lastError = $socketError;
    }
    
    // Connection failed - provide helpful error message
    $errorMsg = "Database connection failed.\n\n";
    $errorMsg .= "Error: " . ($lastError ? $lastError->getMessage() : 'Unknown error') . "\n\n";
    $errorMsg .= "Please ensure:\n";
    $errorMsg .= "1. MySQL is running (check XAMPP Control Panel or run 'mysqld --standalone')\n";
    $errorMsg .= "2. Database credentials are correct in .env file:\n";
    $errorMsg .= "   DB_HOST=" . getenv('DB_HOST') . "\n";
    $errorMsg .= "   DB_PORT=" . getenv('DB_PORT') . "\n";
    $errorMsg .= "   DB_NAME=" . getenv('DB_NAME') . "\n";
    $errorMsg .= "   DB_USER=" . getenv('DB_USER') . "\n";
    $errorMsg .= "3. Database exists and user has permissions\n\n";
    $errorMsg .= "Run: npm run start:mysql (if available)\n";
    
    // Log error
    @error_log($errorMsg);
    
    die($errorMsg);
}

function ensureDefaultAccounts(PDO $pdo) {
    $defaultUsers = [
        [
            'name' => 'Salon Admin',
            'email' => 'admin@example.com',
            'phone' => '+254700000000',
            'password' => 'admin123',
            'role' => 'admin',
            'specialization' => null,
        ],
        [
            'name' => 'Mia Stylists',
            'email' => 'mia@beauty.com',
            'phone' => '+254700000001',
            'password' => 'stylist123',
            'role' => 'stylist',
            'specialization' => 'Hair styling and color',
        ],
    ];

    foreach ($defaultUsers as $defaultUser) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $defaultUser['email']]);
        $existingUser = $stmt->fetch();

        $passwordHash = password_hash($defaultUser['password'], PASSWORD_DEFAULT);
        if ($existingUser) {
            $updateFields = [];
            $updateParams = [':id' => $existingUser['id']];

            if ($existingUser['name'] !== $defaultUser['name']) {
                $updateFields[] = 'name = :name';
                $updateParams[':name'] = $defaultUser['name'];
            }
            if ($existingUser['role'] !== $defaultUser['role']) {
                $updateFields[] = 'role = :role';
                $updateParams[':role'] = $defaultUser['role'];
            }
            if ($existingUser['phone'] !== $defaultUser['phone']) {
                $updateFields[] = 'phone = :phone';
                $updateParams[':phone'] = $defaultUser['phone'];
            }
            if (!password_verify($defaultUser['password'], $existingUser['password'])) {
                $updateFields[] = 'password = :password';
                $updateParams[':password'] = $passwordHash;
            }

            if (!empty($updateFields)) {
                $pdo->prepare('UPDATE users SET ' . implode(', ', $updateFields) . ' WHERE id = :id')
                    ->execute($updateParams);
            }
            $userId = $existingUser['id'];
        } else {
            $insert = $pdo->prepare('INSERT INTO users (name, email, phone, password, role) VALUES (:name, :email, :phone, :password, :role)');
            $insert->execute([
                ':name' => $defaultUser['name'],
                ':email' => $defaultUser['email'],
                ':phone' => $defaultUser['phone'],
                ':password' => $passwordHash,
                ':role' => $defaultUser['role'],
            ]);
            $userId = $pdo->lastInsertId();
        }

        if ($defaultUser['role'] === 'stylist') {
            $stylistStmt = $pdo->prepare('SELECT * FROM stylists WHERE user_id = :user_id LIMIT 1');
            $stylistStmt->execute([':user_id' => $userId]);
            $stylist = $stylistStmt->fetch();

            if ($stylist) {
                if ($stylist['specialization'] !== $defaultUser['specialization']) {
                    $pdo->prepare('UPDATE stylists SET specialization = :specialization WHERE user_id = :user_id')
                        ->execute([
                            ':specialization' => $defaultUser['specialization'],
                            ':user_id' => $userId,
                        ]);
                }
            } else {
                $pdo->prepare('INSERT INTO stylists (user_id, specialization) VALUES (:user_id, :specialization)')
                    ->execute([
                        ':user_id' => $userId,
                        ':specialization' => $defaultUser['specialization'],
                    ]);
            }
        }
    }
}

$pdo = connectDatabase();
ensureDefaultAccounts($pdo);
