<?php
require_once 'config.php';
requireLogin();

$data = json_decode(file_get_contents('php://input'), true);
$pollId = (int)($data['poll_id'] ?? 0);
$optionId = (int)($data['option_id'] ?? 0);

if (!$pollId || !$optionId) {
    jsonResponse(['success' => false, 'error' => 'Missing poll_id or option_id'], 400);
}

$votes = readJsonData('votes.json');
foreach ($votes as $vote) {
    if ((int)$vote['user_id'] === (int)$_SESSION['user_id'] && (int)$vote['poll_id'] === $pollId) {
        jsonResponse(['success' => false, 'error' => 'You have already voted on this poll'], 403);
    }
}

$polls = readJsonData('polls.json');
$pollIndex = null;
$optionIndex = null;
foreach ($polls as $index => $poll) {
    if ((int)$poll['id'] === $pollId) {
        $pollIndex = $index;
        foreach (($poll['options'] ?? []) as $candidateIndex => $option) {
            if ((int)$option['id'] === $optionId) {
                $optionIndex = $candidateIndex;
                break;
            }
        }
        break;
    }
}

if ($pollIndex === null || $optionIndex === null) {
    jsonResponse(['success' => false, 'error' => 'Invalid poll or option'], 400);
}

$polls[$pollIndex]['options'][$optionIndex]['vote_count'] = (int)($polls[$pollIndex]['options'][$optionIndex]['vote_count'] ?? 0) + 1;
$votes[] = [
    'id' => nextJsonId($votes),
    'user_id' => (int)$_SESSION['user_id'],
    'poll_id' => $pollId,
    'option_id' => $optionId,
    'created_at' => date('c'),
];

try {
    writeJsonData('polls.json', $polls);
    writeJsonData('votes.json', $votes);
} catch (Throwable $e) {
    jsonResponse(['success' => false, 'error' => 'Unable to save vote'], 500);
}

$poll = $polls[$pollIndex];
$poll['total_votes'] = array_sum(array_map(static fn(array $option): int => (int)$option['vote_count'], $poll['options']));
foreach ($poll['options'] as &$option) {
    $option['percentage'] = $poll['total_votes'] > 0 ? round(($option['vote_count'] / $poll['total_votes']) * 100, 1) : 0;
}
unset($option);
$poll['voted'] = true;
$poll['voted_option_id'] = $optionId;

jsonResponse(['success' => true, 'poll' => $poll]);

