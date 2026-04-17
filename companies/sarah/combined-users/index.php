<?php
/**
 * index.php
 * Combined list page:
 * - Local users fetched directly from local DB (PDO).
 * - Remote users fetched via CURL from partner APIs.
 * - All users merged and displayed in one HTML table.
 */

$config = require __DIR__ . '/config.php';
require __DIR__ . '/db.php';
require __DIR__ . '/helpers_curl.php';

$localUsers = [];
$remoteUsers = [];
$errors = [];

// 1) LOCAL USERS: direct DB access (required by assignment)
try {
  $pdo = getLocalPdo($config);
  $localUsers = fetchLocalUsers($pdo);
} catch (Throwable $e) {
  $errors[] = 'Local database error: ' . $e->getMessage();
}

// 2) REMOTE USERS: CURL calls to partner APIs (required by assignment)
foreach ($config['remote_api_urls'] as $remoteUrl) {
  $result = fetchRemoteUsersWithCurl((string)$remoteUrl, (int)$config['curl_timeout']);
  if ($result['ok']) {
    $remoteUsers = array_merge($remoteUsers, $result['users']);
  } else {
    $errors[] = "Remote API failed ({$remoteUrl}): " . $result['error'];
  }
}

// 3) Combined list
$combinedUsers = array_merge($localUsers, $remoteUsers);

// Optional: sort combined users by company then name
usort($combinedUsers, function ($a, $b) {
  $c1 = strtolower((string)($a['company'] ?? ''));
  $c2 = strtolower((string)($b['company'] ?? ''));
  if ($c1 === $c2) {
    return strcmp(strtolower((string)($a['name'] ?? '')), strtolower((string)($b['name'] ?? '')));
  }
  return strcmp($c1, $c2);
});
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Combined Users - <?php echo htmlspecialchars($config['company_name'], ENT_QUOTES, 'UTF-8'); ?></title>
  <style>
    body { font-family: Arial, sans-serif; margin: 24px; }
    h1 { margin-bottom: 8px; }
    .meta { color: #555; margin-bottom: 16px; }
    .error-box { background: #fff3f3; border: 1px solid #f1b5b5; padding: 10px 12px; margin-bottom: 12px; }
    table { border-collapse: collapse; width: 100%; margin-top: 16px; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
    th { background: #f6f6f6; }
    .badge-local { color: #0b6; font-weight: bold; }
    .badge-remote { color: #06c; font-weight: bold; }
    .links { margin-top: 10px; }
  </style>
</head>
<body>
  <h1>Combined List of Users</h1>
  <div class="meta">
    Running on: <strong><?php echo htmlspecialchars($config['company_name'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
    Local users: <?php echo count($localUsers); ?> |
    Remote users: <?php echo count($remoteUsers); ?> |
    Total: <?php echo count($combinedUsers); ?>
  </div>

  <div class="links">
    <a href="api/users.php" target="_blank" rel="noopener">View local API JSON</a>
  </div>

  <?php foreach ($errors as $err): ?>
    <div class="error-box"><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endforeach; ?>

  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>Company</th>
        <th>Source</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($combinedUsers) === 0): ?>
        <tr>
          <td colspan="5">No users found.</td>
        </tr>
      <?php else: ?>
        <?php foreach ($combinedUsers as $user): ?>
          <?php
            $isLocal = ((string)($user['company'] ?? '') === (string)$config['company_name']);
          ?>
          <tr>
            <td><?php echo htmlspecialchars((string)($user['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string)($user['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string)($user['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars((string)($user['company'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
              <?php if ($isLocal): ?>
                <span class="badge-local">Local DB</span>
              <?php else: ?>
                <span class="badge-remote">Remote API (CURL)</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>
