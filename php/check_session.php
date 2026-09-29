<?php
require_once 'config.php';

header('Content-Type: application/json');

if (isLoggedIn()) {
    echo json_encode([
        'logged_in' => true,
        'user' => [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'display_name' => $_SESSION['display_name'],
            'role' => $_SESSION['role'],
        ]
    ]);
} else {
    echo json_encode(['logged_in' => false]);
}
