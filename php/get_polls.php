<?php
require_once 'config.php';

try {
    $polls = readJsonData('polls.json');
    $votes = readJsonData('votes.json');
    $userVotes = [];

    if (isLoggedIn()) {
        foreach ($votes as $vote) {
            if ((int)$vote['user_id'] === (int)$_SESSION['user_id']) {
                $userVotes[(int)$vote['poll_id']] = (int)$vote['option_id'];
            }
        }
    }

    usort($polls, static fn(array $a, array $b): int => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));

    foreach ($polls as &$poll) {
        $poll['options'] = array_values($poll['options'] ?? []);
        $totalVotes = array_sum(array_map(static fn(array $option): int => (int)($option['vote_count'] ?? 0), $poll['options']));
        $poll['total_votes'] = $totalVotes;

        foreach ($poll['options'] as &$option) {
            $option['vote_count'] = (int)($option['vote_count'] ?? 0);
            $option['percentage'] = $totalVotes > 0 ? round(($option['vote_count'] / $totalVotes) * 100, 1) : 0;
        }
        unset($option);

        $poll['voted'] = isset($userVotes[(int)$poll['id']]);
        $poll['voted_option_id'] = $userVotes[(int)$poll['id']] ?? null;
    }
    unset($poll);

    jsonResponse(['success' => true, 'polls' => $polls]);
} catch (Throwable $e) {
    jsonResponse(['success' => false, 'error' => 'Unable to load polls'], 500);
}

