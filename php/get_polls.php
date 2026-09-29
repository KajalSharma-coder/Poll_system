<?php
require_once 'config.php';

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

        foreach ($poll['options'] as &$option) {
            $option['percentage'] = $totalVotes > 0
                ? round(($option['vote_count'] / $totalVotes) * 100, 1)
                : 0;
        }

        $poll['voted'] = false;
        $poll['voted_option_id'] = null;

        if (isLoggedIn()) {
            $voteStmt = $pdo->prepare("SELECT option_id FROM votes WHERE user_id = ? AND poll_id = ?");
            $voteStmt->execute([$_SESSION['user_id'], $poll['id']]);
            $vote = $voteStmt->fetch();
            if ($vote) {
                $poll['voted'] = true;
                $poll['voted_option_id'] = (int)$vote['option_id'];
            }
        }
    }

    echo json_encode(['success' => true, 'polls' => $polls]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
