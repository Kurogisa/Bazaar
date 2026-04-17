<?php
/**
 * Append one binary chunk to the staged upload (multipart: chunk file + upload_id).
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

[$config] = require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/vault_helpers.php';

vault_require_login();

$uploadId = preg_replace('/[^a-f0-9]/', '', (string) ($_POST['upload_id'] ?? ''));
if (strlen($uploadId) !== 32) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Bad upload_id']);
    exit;
}

if (empty($_FILES['chunk']) || !is_uploaded_file((string) $_FILES['chunk']['tmp_name'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing chunk file']);
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

$tmpChunk = (string) $_FILES['chunk']['tmp_name'];
$add = filesize($tmpChunk);
if ($add === false) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Bad chunk']);
    exit;
}

if ($received + $add > $total) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Chunk overflows declared total']);
    exit;
}

$out = fopen($partPath, 'ab');
if ($out === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Cannot open part file']);
    exit;
}
$in = fopen($tmpChunk, 'rb');
if ($in === false) {
    fclose($out);
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Cannot read chunk']);
    exit;
}
stream_copy_to_stream($in, $out);
fclose($in);
fclose($out);

$meta['received_bytes'] = $received + $add;
file_put_contents($metaPath, json_encode($meta), LOCK_EX);

echo json_encode([
    'ok' => true,
    'received_bytes' => (int) $meta['received_bytes'],
    'total_bytes' => $total,
], JSON_PRETTY_PRINT);
