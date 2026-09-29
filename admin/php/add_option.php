<?php
require_once '../../php/config.php';
requireAdmin();

$data = json_decode(file_get_contents('php://input'), true);
$pollId = (int)($data['poll_id'] ?? 0);
$optionText = trim($data['option_text'] ?? '');

if (!$pollId || !$optionText) {
    jsonResponse(['success' => false, 'error' => 'poll_id and option_text are required'], 400);
}

$polls = readJsonData('polls.json');
foreach ($polls as &$poll) {
    if ((int)$poll['id'] === $pollId) {
        $allOptions = array_merge(...array_map(static fn(array $item): array => $item['options'] ?? [], $polls));
        $optionId = nextJsonId($allOptions);
        $poll['options'][] = ['id' => $optionId, 'option_text' => $optionText, 'vote_count' => 0];
        writeJsonData('polls.json', $polls);
        jsonResponse(['success' => true, 'option_id' => $optionId]);
    }
}
unset($poll);

jsonResponse(['success' => false, 'error' => 'Poll not found'], 404);

