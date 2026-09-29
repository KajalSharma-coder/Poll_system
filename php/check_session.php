<?php
require_once 'config.php';

if (isLoggedIn()) {
    jsonResponse([
        'logged_in' => true,
        'user' => [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'display_name' => $_SESSION['display_name'],
            'role' => $_SESSION['role'],
        ],
    ]);
}

jsonResponse(['logged_in' => false]);

