<?php
require_once '../../php/config.php';
requireAdmin();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$pollId = $data['poll_id'] ?? null;

if (!$pollId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Poll ID is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM polls WHERE id = ?");
    $stmt->execute([$pollId]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
