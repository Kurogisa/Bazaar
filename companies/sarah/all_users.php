<?php
/**
 * all_users.php
 * Combined list: fetches users from this site's api/users.php + teammate APIs via CURL.
 *
 * YOU ONLY NEED TO SWAP THE URLs BELOW (your live site + two teammate URLs).
 * Local dev: temporarily use http://localhost/... full paths if CURL needs absolute URLs.
 */

$currentPage = 'all_users';

// ---------------------------------------------------------------------------
// CONFIG — change these 3 URLs when you deploy / when teammates share links
// ---------------------------------------------------------------------------

// Your own API (this company's live site)
$myApiUrl = 'https://noirium.net/api/users.php';

// Teammate APIs (URLs from your group members)
$teammateApis = [
    ['url' => 'https://parthgala.infinityfreeapp.com/api/users.php', 'label' => 'Parth (parthgala)'],
    ['url' => 'https://aliciayerinkim.com/api/users.php', 'label' => 'Alicia (aliciayerinkim.com)'],
];

// Seconds before CURL gives up (unreachable host, slow server, etc.)
$curlTimeout = 8;

/**
 * Lab / free-host workaround: some sites have expired or misconfigured HTTPS certs.
 * CURL error 60 = SSL certificate problem.
 *
 * Set to TRUE only when you need to load teammate data despite bad SSL.
 * (Not safe for real production — you are skipping identity verification.)
 */
$curlInsecureSsl = true;

// ---------------------------------------------------------------------------
// CURL helper — used only for HTTP JSON APIs (remote + your own if via URL)
// ---------------------------------------------------------------------------

/**
 * @return array{ ok: bool, users: array, error: string }
 */
function fetchUsersJsonWithCurl(string $url, int $timeoutSeconds, bool $insecureSsl = false): array
{
    $ch = curl_init();
    $opts = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeoutSeconds,
        CURLOPT_FOLLOWLOCATION => true,
    ];
    if ($insecureSsl) {
        $opts[CURLOPT_SSL_VERIFYPEER] = false;
        $opts[CURLOPT_SSL_VERIFYHOST] = 0;
    }
    curl_setopt_array($ch, $opts);

    $body = curl_exec($ch);
    $errno = curl_errno($ch);
    $errstr = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno !== 0) {
        return ['ok' => false, 'users' => [], 'error' => "CURL error ({$errno}): {$errstr}"];
    }
    if ($httpCode < 200 || $httpCode >= 300) {
        return ['ok' => false, 'users' => [], 'error' => "HTTP {$httpCode}"];
    }

    $decoded = json_decode((string) $body, true);
    if (!is_array($decoded)) {
        return ['ok' => false, 'users' => [], 'error' => 'Invalid JSON'];
    }

    $list = [];
    if (isset($decoded['users']) && is_array($decoded['users'])) {
        $list = $decoded['users'];
    } elseif (is_array($decoded) && array_keys($decoded) === range(0, count($decoded) - 1)) {
        $list = $decoded;
    } else {
        return ['ok' => false, 'users' => [], 'error' => 'JSON missing users array'];
    }

    $clean = [];
    foreach ($list as $row) {
        if (!is_array($row)) {
            continue;
        }
        $clean[] = [
            'id' => $row['id'] ?? null,
            'name' => (string) ($row['name'] ?? ''),
            'email' => (string) ($row['email'] ?? ''),
            'company' => (string) ($row['company'] ?? ''),
        ];
    }

    return ['ok' => true, 'users' => $clean, 'error' => ''];
}

/**
 * Same parsing as api/users.php — used if CURL to your own live API fails (e.g. local testing).
 *
 * @return array<int, array{id:int,name:string,email:string,company:string}>
 */
function loadLocalUsersFromDataFile(): array
{
    $dataFile = __DIR__ . '/data/users.txt';
    if (!is_readable($dataFile)) {
        return [];
    }
    $users = [];
    $lines = file($dataFile, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return [];
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || (isset($line[0]) && $line[0] === '#')) {
            continue;
        }
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
    return $users;
}

// ---------------------------------------------------------------------------
// Build list of all endpoints to call (me + teammates)
// ---------------------------------------------------------------------------

$combinedUsers = [];
$errors = [];

// --- Your company: CURL to your live api/users.php (assignment-style) ---
$myLabel = 'Noirium (my API via CURL)';
if (strpos($myApiUrl, 'YOURSITE') !== false || strpos($myApiUrl, 'TEAMMATE_') !== false) {
    $errors[] = "{$myLabel}: skipped (set \$myApiUrl in all_users.php)";
} else {
    $myResult = fetchUsersJsonWithCurl($myApiUrl, $curlTimeout, $curlInsecureSsl);
    if ($myResult['ok']) {
        foreach ($myResult['users'] as $u) {
            $u['_source'] = $myLabel;
            $combinedUsers[] = $u;
        }
    } else {
        $errors[] = "{$myLabel} ({$myApiUrl}): {$myResult['error']}";
        // Fallback: read this copy of data/users.txt so you still see your rows offline / if CURL fails
        $local = loadLocalUsersFromDataFile();
        if (count($local) > 0) {
            foreach ($local as $u) {
                $u['_source'] = 'Noirium (local data/users.txt — CURL to my API failed)';
                $combinedUsers[] = $u;
            }
            $errors[] = 'Note: Loaded your users from local data/users.txt because CURL to your API did not succeed.';
        }
    }
}

// --- Teammates: CURL only ---
foreach ($teammateApis as $t) {
    $url = $t['url'];
    $label = $t['label'];
    if (strpos($url, 'YOURSITE') !== false || strpos($url, 'TEAMMATE_') !== false) {
        $errors[] = "{$label}: skipped (placeholder URL — set real URL in all_users.php)";
        continue;
    }
    $result = fetchUsersJsonWithCurl($url, $curlTimeout, $curlInsecureSsl);
    if (!$result['ok']) {
        $errors[] = "{$label} ({$url}): {$result['error']}";
        continue;
    }
    foreach ($result['users'] as $u) {
        $u['_source'] = $label;
        $combinedUsers[] = $u;
    }
}

// Optional: sort by company, then name
usort($combinedUsers, function ($a, $b) {
    $c = strcmp(strtolower($a['company']), strtolower($b['company']));
    return $c !== 0 ? $c : strcmp(strtolower($a['name']), strtolower($b['name']));
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Combined users (all companies)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/styles.css">
    <style>
        .err { background: #fff3f3; border: 1px solid #e8a0a0; }
    </style>
</head>
<body>
    <?php require __DIR__ . '/navBar.php'; ?>

    <div class="container py-4" style="max-width: 1100px;">
    <h1>Combined list of users</h1>
    <p class="text-muted mb-3">
        Your users are served as JSON from <a href="api/users.php" target="_blank" rel="noopener">api/users.php</a>
        (backed by <code>data/users.txt</code>). This page uses <strong>CURL</strong> to call that URL plus each teammate’s API, then shows one merged table.
    </p>

    <?php foreach ($errors as $msg): ?>
        <div class="err alert mb-2"><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endforeach; ?>

    <p class="mb-3"><strong>Total rows:</strong> <?php echo count($combinedUsers); ?></p>

    <table class="table table-bordered table-striped">
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
                <tr><td colspan="5">No users loaded. Fix URLs in <code>all_users.php</code> or check teammate sites.</td></tr>
            <?php else: ?>
                <?php foreach ($combinedUsers as $u): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string) ($u['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($u['company'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($u['_source'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
