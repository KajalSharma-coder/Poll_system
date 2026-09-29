<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';

if (!$username || !$password) {
    jsonResponse(['success' => false, 'error' => 'Username and password are required'], 400);
}

$user = null;
foreach (readJsonData('users.json') as $candidate) {
    if (($candidate['username'] ?? '') === $username) {
        $user = $candidate;
        break;
    }
}

if (!$user || !password_verify($password, $user['password_hash'] ?? '')) {
    jsonResponse(['success' => false, 'error' => 'Invalid username or password'], 401);
}

$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['display_name'] = $user['display_name'];
$_SESSION['role'] = $user['role'];

jsonResponse([
    'success' => true,
    'user' => [
        'id' => (int)$user['id'],
        'username' => $user['username'],
        'display_name' => $user['display_name'],
        'role' => $user['role'],
    ],
]);

