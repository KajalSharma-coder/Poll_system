<?php
require_once '../../php/config.php';
requireAdmin();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$pollId = $data['poll_id'] ?? null;
$optionText = trim($data['option_text'] ?? '');

if (!$pollId || !$optionText) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'poll_id and option_text are required']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO poll_options (poll_id, option_text) VALUES (?, ?)");
    $stmt->execute([$pollId, $optionText]);

    echo json_encode(['success' => true, 'option_id' => (int)$pdo->lastInsertId()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
