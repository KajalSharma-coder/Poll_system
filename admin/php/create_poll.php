<?php
require_once '../../php/config.php';
requireAdmin();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$question = trim($data['question'] ?? '');
$options = $data['options'] ?? [];

if (!$question) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Poll question is required']);
    exit;
}

if (count($options) < 2) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'At least 2 options are required']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO polls (question) VALUES (?)");
    $stmt->execute([$question]);
    $pollId = $pdo->lastInsertId();

    $optStmt = $pdo->prepare("INSERT INTO poll_options (poll_id, option_text) VALUES (?, ?)");
    foreach ($options as $option) {
        $optStmt->execute([$pollId, trim($option)]);
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'poll_id' => (int)$pollId]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
