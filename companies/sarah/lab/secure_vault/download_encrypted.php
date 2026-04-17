<?php
/**
 * Download the on-disk ciphertext (the .vault bytes) as hex text (.txt) so it is viewable anywhere.
 *
 * Note: this is still ciphertext; it just gets encoded as readable text.
 */
declare(strict_types=1);

[$config, $pdo] = require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/vault_helpers.php';

vault_require_login();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo 'Bad id';
    exit;
}

$uid = vault_user_id();
$st = $pdo->prepare('SELECT disk_name, orig_name FROM vault_files WHERE id = ? AND user_id = ? LIMIT 1');
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
$base = pathinfo($orig, PATHINFO_FILENAME);
if ($base === '' || $base === '.') {
    $base = 'vault';
}
$ascii = preg_replace('/[^\x20-\x7E]/', '_', $base) ?: 'vault';
$filename = $ascii . '.encrypted.txt';

header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('X-Content-Type-Options: nosniff');

$in = fopen($path, 'rb');
if ($in === false) {
    http_response_code(500);
    echo 'Read error';
    exit;
}

// Stream hex so large files don't load into memory.
// We insert newlines every 32 bytes (64 hex chars) for readability.
$lineBytes = 32;
$carry = '';
while (!feof($in)) {
    $chunk = fread($in, 8192);
    if ($chunk === false || $chunk === '') {
        break;
    }
    $hex = $carry . bin2hex($chunk);
    $fullLineLen = $lineBytes * 2;
    $hexLen = strlen($hex);
    $usable = (int) (floor($hexLen / $fullLineLen) * $fullLineLen);
    if ($usable > 0) {
        $block = substr($hex, 0, $usable);
        $carry = substr($hex, $usable);
        echo rtrim(chunk_split($block, $fullLineLen, "\n"), "\n") . "\n";
    } else {
        $carry = $hex;
    }
}
fclose($in);

if ($carry !== '') {
    echo $carry . "\n";
}
exit;
