<?php
require_once '../../php/config.php';
requireAdmin();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$optionId = $data['option_id'] ?? null;

if (!$optionId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'option_id is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM poll_options WHERE id = ?");
    $stmt->execute([$optionId]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
