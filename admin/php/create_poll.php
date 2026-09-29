<?php
require_once '../../php/config.php';
requireAdmin();

$data = json_decode(file_get_contents('php://input'), true);
$question = trim($data['question'] ?? '');
$options = array_values(array_filter(array_map('trim', $data['options'] ?? [])));

if (!$question) {
    jsonResponse(['success' => false, 'error' => 'Poll question is required'], 400);
}
if (count($options) < 2) {
    jsonResponse(['success' => false, 'error' => 'At least 2 options are required'], 400);
}

$polls = readJsonData('polls.json');
$pollId = nextJsonId($polls);
$optionId = 1;
foreach ($polls as $poll) {
    foreach (($poll['options'] ?? []) as $option) {
        $optionId = max($optionId, (int)($option['id'] ?? 0) + 1);
    }
}

$newOptions = [];
foreach ($options as $option) {
    $newOptions[] = ['id' => $optionId++, 'option_text' => $option, 'vote_count' => 0];
}
$polls[] = [
    'id' => $pollId,
    'question' => $question,
    'created_at' => date('c'),
    'options' => $newOptions,
];

try {
    writeJsonData('polls.json', $polls);
    jsonResponse(['success' => true, 'poll_id' => $pollId]);
} catch (Throwable $e) {
    jsonResponse(['success' => false, 'error' => 'Unable to create poll'], 500);
}

