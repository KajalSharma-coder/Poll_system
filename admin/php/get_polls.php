<?php
require_once '../../php/config.php';
requireAdmin();

try {
    $polls = readJsonData('polls.json');
    foreach ($polls as &$poll) {
        $poll['options'] = array_values($poll['options'] ?? []);
        $poll['total_votes'] = array_sum(array_map(static fn(array $option): int => (int)($option['vote_count'] ?? 0), $poll['options']));
    }
    unset($poll);
    jsonResponse(['success' => true, 'polls' => $polls]);
} catch (Throwable $e) {
    jsonResponse(['success' => false, 'error' => 'Unable to load polls'], 500);
}

