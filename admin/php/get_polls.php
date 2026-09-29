<?php
require_once '../../php/config.php';
requireAdmin();

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT * FROM polls ORDER BY created_at DESC");
    $polls = $stmt->fetchAll();

    foreach ($polls as &$poll) {
        $optStmt = $pdo->prepare("SELECT * FROM poll_options WHERE poll_id = ? ORDER BY id");
        $optStmt->execute([$poll['id']]);
        $poll['options'] = $optStmt->fetchAll();

        $totalVotes = array_sum(array_column($poll['options'], 'vote_count'));
        $poll['total_votes'] = $totalVotes;
    }

    echo json_encode(['success' => true, 'polls' => $polls]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
