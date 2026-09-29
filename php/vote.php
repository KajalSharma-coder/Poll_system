<?php
require_once 'config.php';

header('Content-Type: application/json');
requireLogin();

$data = json_decode(file_get_contents('php://input'), true);
$pollId = $data['poll_id'] ?? null;
$optionId = $data['option_id'] ?? null;

if (!$pollId || !$optionId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing poll_id or option_id']);
    exit;
}

$checkStmt = $pdo->prepare("SELECT id FROM votes WHERE user_id = ? AND poll_id = ?");
$checkStmt->execute([$_SESSION['user_id'], $pollId]);
if ($checkStmt->fetch()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'You have already voted on this poll']);
    exit;
}

try {
    $pdo->beginTransaction();

    $optStmt = $pdo->prepare("UPDATE poll_options SET vote_count = vote_count + 1 WHERE id = ? AND poll_id = ?");
    $optStmt->execute([$optionId, $pollId]);

    if ($optStmt->rowCount() === 0) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid poll or option']);
        exit;
    }

    $voteStmt = $pdo->prepare("INSERT INTO votes (user_id, poll_id, option_id) VALUES (?, ?, ?)");
    $voteStmt->execute([$_SESSION['user_id'], $pollId, $optionId]);

    $pdo->commit();

    $pollStmt = $pdo->prepare("SELECT * FROM polls WHERE id = ?");
    $pollStmt->execute([$pollId]);
    $poll = $pollStmt->fetch();

    $optStmt = $pdo->prepare("SELECT * FROM poll_options WHERE poll_id = ? ORDER BY id");
    $optStmt->execute([$pollId]);
    $poll['options'] = $optStmt->fetchAll();

    $totalVotes = array_sum(array_column($poll['options'], 'vote_count'));
    $poll['total_votes'] = $totalVotes;

    foreach ($poll['options'] as &$option) {
        $option['percentage'] = $totalVotes > 0
            ? round(($option['vote_count'] / $totalVotes) * 100, 1)
            : 0;
    }

    $poll['voted'] = true;
    $poll['voted_option_id'] = (int)$optionId;

    echo json_encode(['success' => true, 'poll' => $poll]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
