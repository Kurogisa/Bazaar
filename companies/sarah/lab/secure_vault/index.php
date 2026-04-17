<?php
/**
 * Secure encrypted vault lab — dashboard + chunked upload UI (up to 4 GiB per user).
 *
 * Group research notes (document for instructor):
 * - Authentication: PHP sessions + password_hash/password_verify (bcrypt) + session_regenerate_id on login.
 * - Key derivation: PBKDF2-HMAC-SHA256 (100k iterations) from password + per-user random salt; derived key only in session.
 * - Encryption: libsodium secretstream (XChaCha20-Poly1305) when available; else AES-256-CBC + HMAC-SHA256 over ciphertext.
 * - Large files: staged upload in 4 MiB chunks, then streaming encrypt to local disk (no full-file memory load).
 */
declare(strict_types=1);

[$config, $pdo] = require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/vault_helpers.php';
require_once __DIR__ . '/crypto.php';

vault_require_login();

$uid = vault_user_id();
$usage = vault_user_usage_bytes($pdo, $uid);
$algo = vault_pick_algo();
$algoLabel = $algo === VAULT_CRYPTO_SODIUM ? 'libsodium secretstream (preferred)' : 'OpenSSL AES-256-CBC + HMAC (fallback)';

$st = $pdo->prepare('SELECT id, orig_name, size_plain, created_at, crypto_algo FROM vault_files WHERE user_id = ? ORDER BY id DESC');
$st->execute([$uid]);
$files = $st->fetchAll(PDO::FETCH_ASSOC);

$currentPage = 'lab';
$basePath = '../../';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Lab — Secure encrypted vault</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
  <link rel="stylesheet" href="../../assets/styles.css">
</head>
<body>
<?php require __DIR__ . '/../../navBar.php'; ?>

<div class="container cc-container py-4">
  <div class="cc-paper p-4 p-md-5">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
      <div>
        <h1 class="h3 mb-1">Secure encrypted vault (lab)</h1>
        <p class="text-muted mb-0">
          Logged in as <strong><?php echo htmlspecialchars(vault_username(), ENT_QUOTES, 'UTF-8'); ?></strong>
          · Quota <?php echo number_format($usage / (1024 * 1024), 2); ?> / <?php echo number_format(VAULT_MAX_BYTES_PER_USER / (1024 * 1024), 0); ?> MiB
        </p>
      </div>
      <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary btn-sm" href="../lab.php">Back to Lab</a>
        <a class="btn btn-outline-danger btn-sm" href="logout.php">Logout</a>
      </div>
    </div>

    <hr class="my-4">

    <div class="alert alert-light border small">
      <div class="fw-semibold mb-1">What this demonstrates</div>
      <ul class="mb-0">
        <li><strong>Authentication</strong> required before upload/download (session cookie).</li>
        <li><strong>Encrypt + decrypt</strong> on disk using <?php echo htmlspecialchars($algoLabel, ENT_QUOTES, 'UTF-8'); ?>.</li>
        <li><strong>4 GiB design</strong>: per-user cap and streaming crypto; browser uploads in <?php echo (int) (VAULT_UPLOAD_CHUNK_BYTES / (1024 * 1024)); ?> MiB chunks so PHP <code>post_max_size</code> can stay smaller.</li>
      </ul>
    </div>

    <h2 class="h5 mt-4">Upload a file (encrypted)</h2>
    <p class="text-muted small">Pick a file. It is sent in chunks, encrypted with your vault key, and stored under <code>lab/secure_vault/storage/</code>.</p>
    <input class="form-control" type="file" id="vaultFile">
    <div class="mt-2 d-flex flex-wrap gap-2 align-items-center">
      <button class="btn btn-primary" type="button" id="vaultUploadBtn" disabled>Encrypt &amp; store</button>
      <span class="text-muted small" id="vaultStatus">Select a file to begin.</span>
    </div>
    <div class="progress mt-2 d-none" id="vaultProgress">
      <div class="progress-bar" id="vaultProgressBar" style="width: 0%"></div>
    </div>

    <h2 class="h5 mt-5">Your files</h2>
    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead>
          <tr>
            <th>Name</th>
            <th>Size (plain)</th>
            <th>Algo</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($files) === 0): ?>
            <tr><td colspan="4" class="text-muted">No files yet.</td></tr>
          <?php else: ?>
            <?php foreach ($files as $f): ?>
              <tr>
                <td><?php echo htmlspecialchars((string) $f['orig_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo number_format((int) $f['size_plain']); ?> B</td>
                <td><?php echo ((int) $f['crypto_algo'] === VAULT_CRYPTO_SODIUM) ? 'sodium' : 'openssl'; ?></td>
                <td class="text-end">
                  <div class="d-flex flex-wrap gap-1 justify-content-end">
                    <a class="btn btn-sm btn-outline-primary" href="download.php?id=<?php echo (int) $f['id']; ?>">Decrypt &amp; download</a>
                    <a class="btn btn-sm btn-outline-secondary" href="view_encrypted.php?id=<?php echo (int) $f['id']; ?>">View encrypted</a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
(function () {
  const chunkBytes = <?php echo (int) VAULT_UPLOAD_CHUNK_BYTES; ?>;
  const fileInput = document.getElementById('vaultFile');
  const btn = document.getElementById('vaultUploadBtn');
  const statusEl = document.getElementById('vaultStatus');
  const prog = document.getElementById('vaultProgress');
  const progBar = document.getElementById('vaultProgressBar');

  fileInput.addEventListener('change', () => {
    btn.disabled = !fileInput.files || fileInput.files.length === 0;
    statusEl.textContent = btn.disabled ? 'Select a file to begin.' : ('Selected: ' + fileInput.files[0].name + ' (' + fileInput.files[0].size + ' bytes)');
  });

  btn.addEventListener('click', async () => {
    const f = fileInput.files && fileInput.files[0];
    if (!f) return;

    btn.disabled = true;
    prog.classList.remove('d-none');
    progBar.style.width = '0%';
    statusEl.textContent = 'Starting upload…';

    try {
      const initRes = await fetch('upload_init.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ orig_name: f.name, total_bytes: f.size })
      });
      const initJson = await initRes.json();
      if (!initJson.ok) throw new Error(initJson.error || 'init failed');

      const uploadId = initJson.upload_id;
      const total = f.size;
      let offset = 0;

      while (offset < total) {
        const end = Math.min(offset + chunkBytes, total);
        const blob = f.slice(offset, end);
        const fd = new FormData();
        fd.append('upload_id', uploadId);
        fd.append('chunk', blob, 'chunk.bin');

        const chunkRes = await fetch('upload_chunk.php', { method: 'POST', credentials: 'same-origin', body: fd });
        const chunkJson = await chunkRes.json();
        if (!chunkJson.ok) throw new Error(chunkJson.error || 'chunk failed');

        offset = end;
        const pct = Math.round((offset / total) * 90);
        progBar.style.width = pct + '%';
        statusEl.textContent = 'Uploading… ' + offset + ' / ' + total + ' bytes';
      }

      statusEl.textContent = 'Encrypting on server…';
      const finRes = await fetch('upload_finish.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ upload_id: uploadId })
      });
      const finJson = await finRes.json();
      if (!finJson.ok) throw new Error(finJson.error || 'finish failed');

      progBar.style.width = '100%';
      statusEl.textContent = 'Done. Reloading…';
      window.location.reload();
    } catch (e) {
      console.error(e);
      statusEl.textContent = 'Error: ' + (e && e.message ? e.message : e);
      btn.disabled = false;
    }
  });
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
