<?php
declare(strict_types=1);

[$config, $pdo] = require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth.php';

if (!empty($_SESSION['vault_user_id'])) {
    header('Location: index.php');
    exit;
}

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim((string) ($_POST['username'] ?? ''));
    $p = (string) ($_POST['password'] ?? '');
    $st = $pdo->prepare('SELECT id, username, password_hash, kdf_salt FROM users WHERE username = ? LIMIT 1');
    $st->execute([$u]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row || !password_verify($p, (string) $row['password_hash'])) {
        $err = 'Invalid username or password.';
    } else {
        session_regenerate_id(true);
        $_SESSION['vault_user_id'] = (int) $row['id'];
        $_SESSION['vault_username'] = (string) $row['username'];
        $salt = (string) $row['kdf_salt'];
        $key = vault_derive_key_from_password($p, $salt);
        $_SESSION['vault_key_b64'] = base64_encode($key);
        if (function_exists('sodium_memzero')) {
            sodium_memzero($key);
        }
        header('Location: index.php');
        exit;
    }
}

$currentPage = 'lab';
$basePath = '../../';
$ok = isset($_GET['registered']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Vault — Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
  <link rel="stylesheet" href="../../assets/styles.css">
</head>
<body>
<?php require __DIR__ . '/../../navBar.php'; ?>
<div class="container cc-container py-4" style="max-width: 520px;">
  <div class="cc-paper p-4">
    <h1 class="h3">Secure vault — Login</h1>
    <p class="text-muted small">Authentication: PHP sessions + password hashing (bcrypt). Encryption key: PBKDF2-HMAC-SHA256 (session only).</p>
    <?php if ($ok): ?><div class="alert alert-success">Account created. Log in below.</div><?php endif; ?>
    <?php if ($err !== ''): ?><div class="alert alert-danger"><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <form method="post">
      <div class="mb-3">
        <label class="form-label" for="username">Username</label>
        <input class="form-control" id="username" name="username" required autocomplete="username">
      </div>
      <div class="mb-3">
        <label class="form-label" for="password">Password</label>
        <input class="form-control" type="password" id="password" name="password" required autocomplete="current-password">
      </div>
      <button class="btn btn-primary w-100" type="submit">Login</button>
    </form>
    <p class="mt-3 mb-0"><a href="register.php">Create account</a> · <a href="index.php">Vault home</a></p>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
