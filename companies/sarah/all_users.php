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
    // InfinityFree sometimes uses .infinityfree.me vs .infinityfreeapp.com — use the one that matches their panel
    ['url' => 'https://parthgala.infinityfree.me/api/users.php', 'label' => 'Parth (parthgala)'],
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
 * Free hosts often print warnings/HTML before JSON. Strip BOM/whitespace and pull the first {...} block.
 */
function sanitizeJsonResponseBody(string $body): string
{
    $body = (string) $body;
    if (strncmp($body, "\xEF\xBB\xBF", 3) === 0) {
        $body = substr($body, 3);
    }
    return trim($body);
}

/**
 * InfinityFree (and similar) may return an HTML+JS "browser check" page to non-browser clients.
 * PHP CURL cannot execute JavaScript, so the real JSON never loads.
 */
function looksLikeBrowserOnlySecurityPage(string $raw): bool
{
    $r = strtolower($raw);
    if (strpos($r, '/aes.js') !== false || strpos($r, 'aes.js') !== false) {
        return true;
    }
    if (strpos($r, 'tonumbers') !== false && strpos($r, 'parseint') !== false && strpos($r, '<script') !== false) {
        return true;
    }
    return false;
}

/**
 * Extract first top-level JSON object substring (handles noise before/after JSON).
 */
function extractFirstJsonObject(string $body): ?string
{
    $start = strpos($body, '{');
    if ($start === false) {
        return null;
    }
    return extractJsonObjectStartingAt($body, $start);
}

/**
 * Some endpoints return a raw JSON array. Same “noise before JSON” issue as objects.
 */
function extractFirstJsonArray(string $body): ?string
{
    $start = strpos($body, '[');
    if ($start === false) {
        return null;
    }
    return extractJsonArrayStartingAt($body, $start);
}

/**
 * Balanced {...} starting exactly at $start (must point at '{').
 * Note: naive brace counting; fine for typical lab JSON without { } inside strings.
 */
function extractJsonObjectStartingAt(string $body, int $start): ?string
{
    $len = strlen($body);
    if ($start < 0 || $start >= $len || $body[$start] !== '{') {
        return null;
    }
    $depth = 0;
    for ($i = $start; $i < $len; $i++) {
        $ch = $body[$i];
        if ($ch === '{') {
            $depth++;
        } elseif ($ch === '}') {
            $depth--;
            if ($depth === 0) {
                return substr($body, $start, $i - $start + 1);
            }
        }
    }
    return null;
}

/**
 * Balanced [...] starting exactly at $start (must point at '[').
 */
function extractJsonArrayStartingAt(string $body, int $start): ?string
{
    $len = strlen($body);
    if ($start < 0 || $start >= $len || $body[$start] !== '[') {
        return null;
    }
    $depth = 0;
    for ($i = $start; $i < $len; $i++) {
        $ch = $body[$i];
        if ($ch === '[') {
            $depth++;
        } elseif ($ch === ']') {
            $depth--;
            if ($depth === 0) {
                return substr($body, $start, $i - $start + 1);
            }
        }
    }
    return null;
}

/**
 * HTML/wrapper pages often contain an early "{}" or tiny JSON before the real payload.
 * Scan for every { and [ in the first N bytes and decode each candidate.
 *
 * @return array<int, array<string, mixed>>|null
 */
function bruteForceExtractUsersList(string $raw): ?array
{
    $best = null;
    $bestCount = -1;
    $scanLen = min(strlen($raw), 24000);

    for ($i = 0; $i < $scanLen; $i++) {
        $ch = $raw[$i];
        if ($ch === '{') {
            $slice = extractJsonObjectStartingAt($raw, $i);
            if ($slice === null || strlen($slice) < 3) {
                continue;
            }
            $d = json_decode($slice, true);
            if (!is_array($d)) {
                continue;
            }
            $list = extractUsersListFromDecoded($d);
            if ($list === null) {
                continue;
            }
            $c = count($list);
            if ($c > $bestCount) {
                $bestCount = $c;
                $best = $list;
            }
        } elseif ($ch === '[') {
            $slice = extractJsonArrayStartingAt($raw, $i);
            if ($slice === null || strlen($slice) < 2) {
                continue;
            }
            $d = json_decode($slice, true);
            if (!is_array($d) || !isZeroIndexedList($d) || $d === []) {
                continue;
            }
            $first = $d[0] ?? null;
            if (!is_array($first) || !rowLooksLikeUser($first)) {
                continue;
            }
            $c = count($d);
            if ($c > $bestCount) {
                $bestCount = $c;
                $best = $d;
            }
        }
    }

    return $bestCount >= 0 ? $best : null;
}

/**
 * True if $arr is a list (0..n-1 keys) of associative rows.
 */
function isZeroIndexedList(array $arr): bool
{
    if ($arr === []) {
        return true;
    }
    $keys = array_keys($arr);
    $n = count($keys);
    for ($i = 0; $i < $n; $i++) {
        if ((string) $keys[$i] !== (string) $i) {
            return false;
        }
    }
    return true;
}

/**
 * Does this row look like a user record? (teammates may use slightly different keys)
 */
function rowLooksLikeUser(array $row): bool
{
    $keys = array_map('strtolower', array_keys($row));
    $has = function ($k) use ($keys) {
        return in_array(strtolower($k), $keys, true);
    };
    return $has('name') || $has('email') || $has('username') || $has('user_name')
        || $has('fullname') || $has('full_name') || $has('firstname')
        || $has('id') || $has('user_id');
}

/**
 * Pick the first array value that looks like a list of user objects.
 */
function extractUsersListFromDecoded(array $decoded): ?array
{
    // 1) Exact keys we expect (and common lab variants)
    $preferredKeys = ['users', 'data', 'items', 'results', 'list', 'records', 'user', 'people', 'members'];
    foreach ($preferredKeys as $key) {
        if (!isset($decoded[$key]) || !is_array($decoded[$key])) {
            continue;
        }
        $candidate = $decoded[$key];
        if ($candidate === []) {
            return [];
        }
        if (isZeroIndexedList($candidate)) {
            $first = $candidate[0] ?? null;
            if (is_array($first) && rowLooksLikeUser($first)) {
                return $candidate;
            }
        } else {
            // Object map: { "users": { "1": {...}, "2": {...} } }
            $values = array_values($candidate);
            if ($values !== [] && is_array($values[0]) && rowLooksLikeUser($values[0])) {
                return $values;
            }
        }
    }

    // 2) Nested shapes: { "data": { "users": [ ... ] } }
    if (isset($decoded['data']) && is_array($decoded['data']) && !isZeroIndexedList($decoded['data'])) {
        $inner = extractUsersListFromDecoded($decoded['data']);
        if ($inner !== null) {
            return $inner;
        }
    }

    // 3) Root is already a JSON array of users
    if (isZeroIndexedList($decoded)) {
        $first = $decoded[0] ?? null;
        if (is_array($first) && rowLooksLikeUser($first)) {
            return $decoded;
        }
    }

    // 4) Last resort: first numeric-list child that looks like users
    foreach ($decoded as $v) {
        if (!is_array($v) || !isZeroIndexedList($v) || $v === []) {
            continue;
        }
        $first = $v[0] ?? null;
        if (is_array($first) && rowLooksLikeUser($first)) {
            return $v;
        }
    }

    return null;
}

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
        // Many hosts gzip JSON; without this, $body can be binary and json_decode fails (browser auto-decompresses).
        CURLOPT_ENCODING => '',
        // Some free hosts block or alter requests with no User-Agent; mimic a normal browser fetch.
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER => [
            'Accept: application/json, text/plain, */*',
            'Accept-Language: en-US,en;q=0.9',
        ],
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

    $raw = sanitizeJsonResponseBody((string) $body);

    if (looksLikeBrowserOnlySecurityPage($raw)) {
        return [
            'ok' => false,
            'users' => [],
            'error' => 'Remote host returned a browser-only security page (not JSON). '
                . 'InfinityFree often blocks server-to-server CURL with a JavaScript challenge (aes.js). '
                . 'Your browser can pass it; PHP CURL cannot. '
                . 'Fix: teammate should expose the API on a host/path that allows direct CURL, '
                . 'or use a static JSON URL (GitHub raw, etc.) for the lab demo.',
        ];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        $slice = extractFirstJsonObject($raw);
        if ($slice !== null) {
            $decoded = json_decode($slice, true);
        }
    }
    if (!is_array($decoded)) {
        $slice = extractFirstJsonArray($raw);
        if ($slice !== null) {
            $decoded = json_decode($slice, true);
        }
    }
    if (!is_array($decoded)) {
        $snippet = preg_replace('/\s+/', ' ', substr($raw, 0, 180));
        $jsonErr = function_exists('json_last_error_msg') ? json_last_error_msg() : 'json error';
        return [
            'ok' => false,
            'users' => [],
            'error' => "Invalid JSON ({$jsonErr}). Start of response: " . $snippet,
        ];
    }

    $list = extractUsersListFromDecoded($decoded);
    // Wrapper HTML may include an early "{}"; first decode can be empty while real JSON is later.
    if ($list === null) {
        $list = bruteForceExtractUsersList($raw);
    }

    if ($list === null) {
        $topKeys = array_keys($decoded);
        $keysStr = implode(', ', array_map('strval', $topKeys));
        $preview = preg_replace('/\s+/', ' ', substr($raw, 0, 220));
        return [
            'ok' => false,
            'users' => [],
            'error' => 'Could not find a users list in JSON. Top-level keys: '
                . ($keysStr !== '' ? $keysStr : '(none)')
                . '. Response preview: '
                . $preview,
        ];
    }

    $clean = [];
    foreach ($list as $row) {
        if (!is_array($row)) {
            continue;
        }
        $name = $row['name'] ?? $row['user_name'] ?? $row['username'] ?? $row['full_name'] ?? $row['fullName'] ?? $row['FullName'] ?? '';
        if ($name === '' && isset($row['firstName'], $row['lastName'])) {
            $name = trim((string) $row['firstName'] . ' ' . (string) $row['lastName']);
        }
        $email = $row['email'] ?? $row['mail'] ?? $row['user_email'] ?? $row['userEmail'] ?? '';
        $company = $row['company'] ?? $row['org'] ?? $row['organization'] ?? '';
        $id = $row['id'] ?? $row['user_id'] ?? null;
        $clean[] = [
            'id' => $id,
            'name' => (string) $name,
            'email' => (string) $email,
            'company' => (string) $company,
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
