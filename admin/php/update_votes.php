<?php
require_once '../../php/config.php';
requireAdmin();

$data = json_decode(file_get_contents('php://input'), true);
$optionId = (int)($data['option_id'] ?? 0);
$voteCount = $data['vote_count'] ?? null;
if (!$optionId || $voteCount === null || !is_numeric($voteCount) || (int)$voteCount < 0) {
    jsonResponse(['success' => false, 'error' => 'Valid option_id and vote_count are required'], 400);
}

$polls = readJsonData('polls.json');
$updated = false;
foreach ($polls as &$poll) {
    foreach ($poll['options'] ?? [] as &$option) {
        if ((int)$option['id'] === $optionId) {
            $option['vote_count'] = (int)$voteCount;
            $updated = true;
        }
    }
}
unset($poll, $option);

if (!$updated) {
    jsonResponse(['success' => false, 'error' => 'Option not found'], 404);
}
writeJsonData('polls.json', $polls);
jsonResponse(['success' => true]);

