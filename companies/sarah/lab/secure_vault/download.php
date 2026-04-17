<?php
/**
 * Decrypt and stream a vault file to the browser (must be logged in).
 */
declare(strict_types=1);

[$config, $pdo] = require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/vault_helpers.php';
require_once __DIR__ . '/crypto.php';

vault_require_login();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo 'Bad id';
    exit;
}

$uid = vault_user_id();
$st = $pdo->prepare('SELECT id, disk_name, orig_name, size_plain FROM vault_files WHERE id = ? AND user_id = ? LIMIT 1');
$st->execute([$id, $uid]);
$row = $st->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    http_response_code(404);
    echo 'Not found';
    exit;
}

$path = vault_user_storage_dir($config, $uid) . '/' . basename((string) $row['disk_name']);
if (!is_readable($path)) {
    http_response_code(404);
    echo 'Missing ciphertext';
    exit;
}

$orig = (string) $row['orig_name'];
$ascii = preg_replace('/[^\x20-\x7E]/', '_', $orig) ?: 'download.bin';

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $ascii . '"');
header('X-Content-Type-Options: nosniff');

$key = vault_key_binary();
$in = fopen($path, 'rb');
$out = fopen('php://output', 'wb');
if ($in === false || $out === false) {
    http_response_code(500);
    echo 'Stream error';
    exit;
}

try {
    vault_decrypt_stream($in, $out, $key);
} catch (Throwable $e) {
    http_response_code(500);
    exit;
}
fclose($in);
fclose($out);
exit;
