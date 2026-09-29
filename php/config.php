<?php
session_start();

$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$dbname = getenv('DB_NAME') ?: 'poll_system';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$sslCa = getenv('DB_SSL_CA') ?: '';

try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    if ($sslCa !== '') {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
    }

    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Please log in first']);
        exit;
    }
}

function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireAdmin(): void {
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Admin access required']);
        exit;
    }
}
