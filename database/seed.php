Run this file once via http://localhost/poll-system/database/seed.php
to populate sample users and polls.

<?php

$host = 'localhost';
$dbname = 'poll_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

echo "Seeding database...\n";

$stmt = $pdo->prepare("INSERT INTO users (username, password_hash, display_name, role) VALUES (?, ?, ?, ?)");

$users = [
    ['admin',   password_hash('admin123',   PASSWORD_BCRYPT), 'Administrator', 'admin'],
    ['alice',   password_hash('password123', PASSWORD_BCRYPT), 'Alice Johnson',  'user'],
    ['bob',     password_hash('password123', PASSWORD_BCRYPT), 'Bob Smith',     'user'],
    ['charlie', password_hash('password123', PASSWORD_BCRYPT), 'Charlie Brown', 'user'],
];

foreach ($users as $user) {
    $stmt->execute($user);
    echo "  Created user: {$user[0]}\n";
}

$polls = [
    ['What is your favorite programming language?', ['JavaScript', 'Python', 'Java', 'C++', 'Go', 'Rust']],
    ['Which frontend framework do you prefer?',     ['React', 'Vue.js', 'Angular', 'Svelte']],
    ['What is your preferred code editor?',         ['VS Code', 'IntelliJ IDEA', 'Sublime Text', 'Vim / Neovim']],
];

$pollStmt = $pdo->prepare("INSERT INTO polls (question) VALUES (?)");
$optStmt  = $pdo->prepare("INSERT INTO poll_options (poll_id, option_text) VALUES (?, ?)");

foreach ($polls as $poll) {
    $pollStmt->execute([$poll[0]]);
    $pollId = $pdo->lastInsertId();
    foreach ($poll[1] as $option) {
        $optStmt->execute([$pollId, $option]);
    }
    echo "  Created poll: {$poll[0]}\n";
}

echo "\nDone! Sample users:\n";
echo "  admin   / admin123   (admin)\n";
echo "  alice   / password123 (user)\n";
echo "  bob     / password123 (user)\n";
echo "  charlie / password123 (user)\n";
