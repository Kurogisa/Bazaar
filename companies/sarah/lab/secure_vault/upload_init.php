<?php
/**
 * Start a chunked upload. Returns upload_id and chunk size for the browser.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

[$config, $pdo] = require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/vault_helpers.php';

vault_require_login();

$raw = file_get_contents('php://input');
$data = json_decode((string) $raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON body']);
    exit;
}

$orig = vault_safe_filename((string) ($data['orig_name'] ?? $data['filename'] ?? 'upload.bin'));
$total = (int) ($data['total_bytes'] ?? 0);

if ($total <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'total_bytes required']);
    exit;
}
if ($total > VAULT_MAX_SINGLE_FILE) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'File exceeds 4 GiB limit']);
    exit;
}

$uid = vault_user_id();
$usage = vault_user_usage_bytes($pdo, $uid);
if ($usage + $total > VAULT_MAX_BYTES_PER_USER) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Vault quota exceeded (4 GiB per user)']);
    exit;
}

$uploadId = bin2hex(random_bytes(16));
$tmpBase = vault_user_tmp_dir($config, $uid);
if (!is_dir($tmpBase) && !mkdir($tmpBase, 0700, true) && !is_dir($tmpBase)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Cannot create tmp dir']);
    exit;
}

$partPath = $tmpBase . '/' . $uploadId . '.part';
$metaPath = $tmpBase . '/' . $uploadId . '.json';
if (file_put_contents($partPath, '') === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Cannot create upload part file']);
    exit;
}

$meta = [
    'orig_name' => $orig,
    'total_bytes' => $total,
    'received_bytes' => 0,
    'user_id' => $uid,
    'created' => time(),
];
if (file_put_contents($metaPath, json_encode($meta), LOCK_EX) === false) {
    @unlink($partPath);
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Cannot write meta']);
    exit;
}

echo json_encode([
    'ok' => true,
    'upload_id' => $uploadId,
    'chunk_bytes' => VAULT_UPLOAD_CHUNK_BYTES,
    'orig_name' => $orig,
], JSON_PRETTY_PRINT);
