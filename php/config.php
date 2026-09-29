<?php
require_once __DIR__ . '/json_store.php';

$sessionDirectory = jsonDataDirectory() . DIRECTORY_SEPARATOR . 'sessions';
if (!is_dir($sessionDirectory)) {
    mkdir($sessionDirectory, 0775, true);
}
session_save_path($sessionDirectory);
session_start();

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        jsonResponse(['success' => false, 'error' => 'Please log in first'], 401);
    }
}

function isAdmin(): bool
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireAdmin(): void
{
    if (!isAdmin()) {
        jsonResponse(['success' => false, 'error' => 'Admin access required'], 403);
    }
}
