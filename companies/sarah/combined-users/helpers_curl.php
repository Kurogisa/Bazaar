<?php
/**
 * helpers_curl.php
 * CURL helper used to call remote company APIs.
 */

function fetchRemoteUsersWithCurl(string $url, int $timeoutSeconds = 5): array
{
  $ch = curl_init();

  curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => $timeoutSeconds,
    CURLOPT_FOLLOWLOCATION => true,
  ]);

  $body = curl_exec($ch);
  $curlErrNo = curl_errno($ch);
  $curlError = curl_error($ch);
  $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($curlErrNo !== 0) {
    return [
      'ok' => false,
      'users' => [],
      'error' => "CURL error ({$curlErrNo}): {$curlError}",
    ];
  }

  if ($httpCode < 200 || $httpCode >= 300) {
    return [
      'ok' => false,
      'users' => [],
      'error' => "HTTP error code {$httpCode}",
    ];
  }

  $decoded = json_decode((string)$body, true);
  if (!is_array($decoded)) {
    return [
      'ok' => false,
      'users' => [],
      'error' => 'Invalid JSON response',
    ];
  }

  // Accept either:
  // 1) plain array of users, or
  // 2) object with key "users"
  $users = [];
  if (isset($decoded['users']) && is_array($decoded['users'])) {
    $users = $decoded['users'];
  } elseif (array_is_list($decoded)) {
    $users = $decoded;
  } else {
    return [
      'ok' => false,
      'users' => [],
      'error' => 'JSON does not contain a valid users list',
    ];
  }

  // Keep only required fields in each user.
  $cleanUsers = [];
  foreach ($users as $u) {
    if (!is_array($u)) continue;
    $cleanUsers[] = [
      'id' => $u['id'] ?? null,
      'name' => $u['name'] ?? '',
      'email' => $u['email'] ?? '',
      'company' => $u['company'] ?? '',
    ];
  }

  return [
    'ok' => true,
    'users' => $cleanUsers,
    'error' => '',
  ];
}
