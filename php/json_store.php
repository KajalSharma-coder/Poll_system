<?php

function jsonDataDirectory(): string
{
    $directory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data';
    if (!is_dir($directory)) {
        mkdir($directory, 0775, true);
    }
    return $directory;
}

function jsonDataDefaults(string $filename): array
{
    if ($filename === 'users.json') {
        return [
            ['id' => 1, 'username' => 'admin', 'password_hash' => password_hash('admin123', PASSWORD_DEFAULT), 'display_name' => 'Administrator', 'role' => 'admin', 'created_at' => date('c')],
            ['id' => 2, 'username' => 'alice', 'password_hash' => password_hash('password123', PASSWORD_DEFAULT), 'display_name' => 'Alice Johnson', 'role' => 'user', 'created_at' => date('c')],
        ];
    }

    if ($filename === 'polls.json') {
        return [
            ['id' => 1, 'question' => 'What is your favorite programming language?', 'created_at' => date('c'), 'options' => [
                ['id' => 1, 'option_text' => 'JavaScript', 'vote_count' => 0],
                ['id' => 2, 'option_text' => 'Python', 'vote_count' => 0],
                ['id' => 3, 'option_text' => 'Java', 'vote_count' => 0],
            ]],
        ];
    }

    return [];
}

function readJsonData(string $filename): array
{
    $path = jsonDataDirectory() . DIRECTORY_SEPARATOR . $filename;
    if (!is_file($path) || filesize($path) === 0) {
        $defaults = jsonDataDefaults($filename);
        writeJsonData($filename, $defaults);
        return $defaults;
    }

    $contents = file_get_contents($path);
    if ($contents === false || trim($contents) === '') {
        return [];
    }

    $data = json_decode($contents, true);
    return is_array($data) ? $data : [];
}

function writeJsonData(string $filename, array $data): void
{
    $path = jsonDataDirectory() . DIRECTORY_SEPARATOR . $filename;
    $handle = fopen($path, 'c+');
    if ($handle === false || !flock($handle, LOCK_EX)) {
        throw new RuntimeException('Unable to lock data file.');
    }

    try {
        $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            throw new RuntimeException('Unable to encode data.');
        }
        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, $encoded . PHP_EOL);
        fflush($handle);
        flock($handle, LOCK_UN);
    } finally {
        fclose($handle);
    }
}

function nextJsonId(array $items): int
{
    $ids = array_map(static fn(array $item): int => (int)($item['id'] ?? 0), $items);
    return ($ids ? max($ids) : 0) + 1;
}

function jsonResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}
