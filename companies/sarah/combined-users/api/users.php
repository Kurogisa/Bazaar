<?php
/**
 * api/users.php
 * Local API endpoint that returns this company's users as JSON.
 */

header('Content-Type: application/json; charset=utf-8');

try {
  $config = require __DIR__ . '/../config.php';
  require __DIR__ . '/../db.php';

  $pdo = getLocalPdo($config);
  $users = fetchLocalUsers($pdo); // Local DB access (direct)

  echo json_encode([
    'success' => true,
    'company' => $config['company_name'],
    'count' => count($users),
    'users' => $users,
  ], JSON_PRETTY_PRINT);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'message' => 'Failed to fetch local users.',
    'error' => $e->getMessage(),
    'users' => [],
  ], JSON_PRETTY_PRINT);
}
