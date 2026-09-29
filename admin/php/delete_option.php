<?php
require_once '../../php/config.php';
requireAdmin();

$data = json_decode(file_get_contents('php://input'), true);
$optionId = (int)($data['option_id'] ?? 0);
if (!$optionId) {
    jsonResponse(['success' => false, 'error' => 'option_id is required'], 400);
}

$polls = readJsonData('polls.json');
foreach ($polls as &$poll) {
    $poll['options'] = array_values(array_filter($poll['options'] ?? [], static fn(array $option): bool => (int)$option['id'] !== $optionId));
}
unset($poll);
writeJsonData('polls.json', $polls);

$votes = array_values(array_filter(readJsonData('votes.json'), static fn(array $vote): bool => (int)$vote['option_id'] !== $optionId));
writeJsonData('votes.json', $votes);

jsonResponse(['success' => true]);

