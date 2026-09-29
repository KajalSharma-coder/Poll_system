<?php
require_once '../../php/config.php';
requireAdmin();

$data = json_decode(file_get_contents('php://input'), true);
$pollId = (int)($data['poll_id'] ?? 0);
if (!$pollId) {
    jsonResponse(['success' => false, 'error' => 'Poll ID is required'], 400);
}

$polls = array_values(array_filter(readJsonData('polls.json'), static fn(array $poll): bool => (int)$poll['id'] !== $pollId));
$votes = array_values(array_filter(readJsonData('votes.json'), static fn(array $vote): bool => (int)$vote['poll_id'] !== $pollId));
writeJsonData('polls.json', $polls);
writeJsonData('votes.json', $votes);

jsonResponse(['success' => true]);

