<?php
/**
 * api/users.php
 * Returns this company's dummy users as JSON (assignment: file-based, no DB required).
 * Data source: ../data/users.txt (CSV: id,name,email,company)
 *
 * Teammates: copy this file unchanged; only edit data/users.txt with your names/emails.
 */

header('Content-Type: application/json; charset=utf-8');

$dataFile = dirname(__DIR__) . '/data/users.txt';

if (!is_readable($dataFile)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'users.txt not found or not readable',
        'users' => [],
    ], JSON_PRETTY_PRINT);
    exit;
}

$users = [];
$lines = file($dataFile, FILE_IGNORE_NEW_LINES);

foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || (isset($line[0]) && $line[0] === '#')) {
        continue;
    }
    // Parse CSV row: id,name,email,company (commas inside fields not supported — keep it simple)
    $parts = str_getcsv($line);
    if (count($parts) < 4) {
        continue;
    }
    $users[] = [
        'id' => (int) trim($parts[0]),
        'name' => trim($parts[1]),
        'email' => trim($parts[2]),
        'company' => trim($parts[3]),
    ];
}

echo json_encode([
    'success' => true,
    'count' => count($users),
    'users' => $users,
], JSON_PRETTY_PRINT);
