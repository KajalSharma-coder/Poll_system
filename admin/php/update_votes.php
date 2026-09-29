<?php
require_once '../../php/config.php';
requireAdmin();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$optionId = $data['option_id'] ?? null;
$voteCount = $data['vote_count'] ?? null;

if (!$optionId || $voteCount === null || $voteCount < 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Valid option_id and vote_count are required']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE poll_options SET vote_count = ? WHERE id = ?");
    $stmt->execute([(int)$voteCount, $optionId]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
