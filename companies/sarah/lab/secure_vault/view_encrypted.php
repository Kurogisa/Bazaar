<?php
/**
 * Show ciphertext metadata and a hex preview of the stored .vault file (must be logged in).
 */
declare(strict_types=1);

[$config, $pdo] = require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/vault_helpers.php';
require_once __DIR__ . '/crypto.php';

vault_require_login();

const VAULT_HEX_PREVIEW_BYTES = 512;

/**
 * @return non-empty-string
 */
function vault_hex_dump_preview(string $bin, int $maxBytes = VAULT_HEX_PREVIEW_BYTES): string
{
    $bin = substr($bin, 0, $maxBytes);
    $len = strlen($bin);
    if ($len === 0) {
        return '(empty file)';
    }
    $lines = [];
    $per = 16;
    for ($o = 0; $o < $len; $o += $per) {
        $slice = substr($bin, $o, $per);
        $hexParts = [];
        $sliceLen = strlen($slice);
        for ($j = 0; $j < $sliceLen; $j++) {
            $hexParts[] = sprintf('%02X', ord($slice[$j]));
        }
        $hex = implode(' ', $hexParts);
        $ascii = '';
        for ($j = 0; $j < $sliceLen; $j++) {
            $c = $slice[$j];
            $ascii .= ($c >= ' ' && $c <= '~') ? $c : '.';
        }
        $lines[] = sprintf('%08X  %-47s | %s', $o, $hex, $ascii);
    }
    return implode("\n", $lines);
}

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo 'Bad id';
    exit;
}

$uid = vault_user_id();
$st = $pdo->prepare('SELECT id, disk_name, orig_name, size_plain, crypto_algo, created_at FROM vault_files WHERE id = ? AND user_id = ? LIMIT 1');
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

$sizeCipher = filesize($path);
if ($sizeCipher === false) {
    http_response_code(500);
    echo 'Size error';
    exit;
}

$handle = fopen($path, 'rb');
$previewBin = $handle !== false ? (string) fread($handle, VAULT_HEX_PREVIEW_BYTES) : '';
if ($handle !== false) {
    fclose($handle);
}

$algoLabel = ((int) $row['crypto_algo'] === VAULT_CRYPTO_SODIUM) ? 'libsodium secretstream' : 'OpenSSL AES-256-CBC + HMAC';
$hexText = vault_hex_dump_preview($previewBin);
$truncated = $sizeCipher > VAULT_HEX_PREVIEW_BYTES;
$currentPage = 'lab';
$basePath = '../../';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Encrypted view — Secure vault</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
  <link rel="stylesheet" href="../../assets/styles.css">
</head>
<body>
<?php require __DIR__ . '/../../navBar.php'; ?>

<div class="container cc-container py-4">
  <div class="cc-paper p-4 p-md-5">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
      <div>
        <h1 class="h3 mb-1">Ciphertext on disk</h1>
        <p class="text-muted mb-0"><?php echo htmlspecialchars((string) $row['orig_name'], ENT_QUOTES, 'UTF-8'); ?></p>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary btn-sm" href="index.php">Back to vault</a>
        <a class="btn btn-primary btn-sm" href="download_encrypted.php?id=<?php echo (int) $row['id']; ?>">Download ciphertext (.txt hex)</a>
      </div>
    </div>

    <hr class="my-4">

    <dl class="row small mb-4">
      <dt class="col-sm-3">Plaintext size (stored)</dt>
      <dd class="col-sm-9"><?php echo number_format((int) $row['size_plain']); ?> B</dd>
      <dt class="col-sm-3">Ciphertext size (file)</dt>
      <dd class="col-sm-9"><?php echo number_format($sizeCipher); ?> B</dd>
      <dt class="col-sm-3">Algorithm</dt>
      <dd class="col-sm-9"><?php echo htmlspecialchars($algoLabel, ENT_QUOTES, 'UTF-8'); ?></dd>
      <dt class="col-sm-3">Stored as</dt>
      <dd class="col-sm-9"><code><?php echo htmlspecialchars(basename((string) $row['disk_name']), ENT_QUOTES, 'UTF-8'); ?></code></dd>
    </dl>

    <h2 class="h6">First <?php echo (int) VAULT_HEX_PREVIEW_BYTES; ?> bytes (hex)</h2>
    <?php if ($truncated): ?>
      <p class="text-muted small mb-2">File is larger; only the beginning is shown. Use <strong>Download .vault</strong> for the full ciphertext.</p>
    <?php endif; ?>
    <pre class="bg-light border rounded p-3 small mb-0" style="max-height: 28rem; overflow: auto; font-size: 0.8rem;"><code><?php echo htmlspecialchars($hexText, ENT_QUOTES, 'UTF-8'); ?></code></pre>
  </div>
</div>
</body>
</html>
