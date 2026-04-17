<?php
declare(strict_types=1);

[$config, $pdo] = require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/vault_helpers.php';

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim((string) ($_POST['username'] ?? ''));
    $p = (string) ($_POST['password'] ?? '');
    if ($u === '' || strlen($u) < 2) {
        $err = 'Username too short.';
    } elseif (strlen($p) < 8) {
        $err = 'Password must be at least 8 characters.';
    } else {
        $salt = random_bytes(16);
        $hash = password_hash($p, PASSWORD_DEFAULT);
        try {
            $st = $pdo->prepare('INSERT INTO users (username, password_hash, kdf_salt, created_at) VALUES (?,?,?,?)');
            $st->execute([$u, $hash, $salt, time()]);
            $uid = (int) $pdo->lastInsertId();
            @mkdir(vault_user_storage_dir($config, $uid), 0700, true);
            @mkdir(vault_user_tmp_dir($config, $uid), 0700, true);
            header('Location: login.php?registered=1');
            exit;
        } catch (Throwable $e) {
            $err = 'Could not register (username taken?).';
        }
    }
}

$currentPage = 'lab';
$basePath = '../../';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Vault — Register</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
  <link rel="stylesheet" href="../../assets/styles.css">
</head>
<body>
<?php require __DIR__ . '/../../navBar.php'; ?>
<div class="container cc-container py-4" style="max-width: 520px;">
  <div class="cc-paper p-4">
    <h1 class="h3">Secure vault — Register</h1>
    <p class="text-muted small">Lab: PBKDF2-derived encryption key is created at login and kept in your session only.</p>
    <?php if ($err !== ''): ?><div class="alert alert-danger"><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <form method="post">
      <div class="mb-3">
        <label class="form-label" for="username">Username</label>
        <input class="form-control" id="username" name="username" required autocomplete="username">
      </div>
      <div class="mb-3">
        <label class="form-label" for="password">Password</label>
        <input class="form-control" type="password" id="password" name="password" required autocomplete="new-password" minlength="8">
      </div>
      <button class="btn btn-primary w-100" type="submit">Create account</button>
    </form>
    <p class="mt-3 mb-0"><a href="login.php">Back to login</a></p>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
