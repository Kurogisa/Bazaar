<?php
/**
 * Encrypt staged plaintext file into storage and register in DB.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

[$config, $pdo] = require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/vault_helpers.php';
require_once __DIR__ . '/crypto.php';

vault_require_login();

$raw = file_get_contents('php://input');
$data = json_decode((string) $raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
    exit;
}

$uploadId = preg_replace('/[^a-f0-9]/', '', (string) ($data['upload_id'] ?? ''));
if (strlen($uploadId) !== 32) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Bad upload_id']);
    exit;
}

$uid = vault_user_id();
$tmpBase = vault_user_tmp_dir($config, $uid);
$partPath = $tmpBase . '/' . $uploadId . '.part';
$metaPath = $tmpBase . '/' . $uploadId . '.json';

if (!is_readable($metaPath) || !is_file($partPath)) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Unknown upload']);
    exit;
}

$meta = json_decode((string) file_get_contents($metaPath), true);
if (!is_array($meta) || (int) ($meta['user_id'] ?? 0) !== $uid) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Upload not owned by user']);
    exit;
}

$total = (int) ($meta['total_bytes'] ?? 0);
$received = (int) ($meta['received_bytes'] ?? 0);
if ($received !== $total) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Incomplete upload', 'received' => $received, 'expected' => $total]);
    exit;
}

$plainSize = filesize($partPath);
if ($plainSize === false || (int) $plainSize !== $total) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Part file size mismatch']);
    exit;
}

$storeDir = vault_user_storage_dir($config, $uid);
if (!is_dir($storeDir) && !mkdir($storeDir, 0700, true) && !is_dir($storeDir)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Cannot create storage dir']);
    exit;
}

$diskName = bin2hex(random_bytes(16)) . '.vault';
$outPath = $storeDir . '/' . $diskName;

$algo = vault_pick_algo();
$key = vault_key_binary();

$in = fopen($partPath, 'rb');
$out = fopen($outPath, 'wb');
if ($in === false || $out === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Cannot open files for encryption']);
    exit;
}

try {
    vault_encrypt_stream($in, $out, $key, $algo);
} catch (Throwable $e) {
    fclose($in);
    fclose($out);
    @unlink($outPath);
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Encrypt failed: ' . $e->getMessage()]);
    exit;
}
fclose($in);
fclose($out);

$st = $pdo->prepare(
    'INSERT INTO vault_files (user_id, disk_name, orig_name, size_plain, crypto_algo, created_at) VALUES (?,?,?,?,?,?)'
);
$st->execute([
    $uid,
    $diskName,
    (string) ($meta['orig_name'] ?? 'file.bin'),
    $total,
    $algo,
    time(),
]);

@unlink($partPath);
@unlink($metaPath);

echo json_encode([
    'ok' => true,
    'file_id' => (int) $pdo->lastInsertId(),
    'disk_name' => $diskName,
], JSON_PRETTY_PRINT);
