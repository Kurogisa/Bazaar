<?php
session_start();
$currentPage = "login";

const ADMIN_USERID = 'admin';
// NOTE: Change this to whatever your class/lab expects.
const ADMIN_PASSWORD = 'admin';

$isSafeRedirect = function (string $value): bool {
  if ($value === '') return false;
  if (strpos($value, '://') !== false) return false;
  if (strpos($value, "\n") !== false || strpos($value, "\r") !== false) return false;
  // Keep redirects relative to this directory (no leading slash).
  if (isset($value[0]) && $value[0] === '/') return false;
  return true;
};

$redirect = isset($_GET['redirect']) ? (string)$_GET['redirect'] : 'secure/user.php';
if (!$isSafeRedirect($redirect)) {
  $redirect = 'secure/user.php';
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $userid = isset($_POST['userid']) ? trim((string)$_POST['userid']) : '';
  $password = isset($_POST['password']) ? (string)$_POST['password'] : '';
  $postedRedirect = isset($_POST['redirect']) ? (string)$_POST['redirect'] : $redirect;
  if (!$isSafeRedirect($postedRedirect)) {
    $postedRedirect = 'secure/user.php';
  }

  if ($userid === ADMIN_USERID && $password === ADMIN_PASSWORD) {
    session_regenerate_id(true);
    $_SESSION['is_admin'] = true;
    $_SESSION['userid'] = $userid;
    header('Location: ' . $postedRedirect);
    exit;
  }

  $error = 'Invalid userid or password.';
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Noirium</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  </head>
  <body>
    <?php require 'navBar.php'; ?>

    <div class="container mt-5" style="max-width: 520px;">
      <h1 class="mb-3">Administrator Login</h1>
      <p class="text-muted mb-4">Secure pages require an administrator login.</p>

      <?php if ($error !== ''): ?>
        <div class="alert alert-danger" role="alert">
          <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <form method="post" action="login.php<?php echo ($redirect !== '' ? '?redirect=' . urlencode($redirect) : ''); ?>">
        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect, ENT_QUOTES, 'UTF-8'); ?>">

        <div class="mb-3">
          <label for="userid" class="form-label">User ID</label>
          <input
            type="text"
            class="form-control"
            id="userid"
            name="userid"
            autocomplete="username"
            required
          >
        </div>

        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <input
            type="password"
            class="form-control"
            id="password"
            name="password"
            autocomplete="current-password"
            required
          >
        </div>

        <button type="submit" class="btn btn-primary w-100">Login</button>
      </form>

      <hr class="my-4">
      <p class="mb-0">
        After login you’ll be redirected to
        <code><?php echo htmlspecialchars($redirect, ENT_QUOTES, 'UTF-8'); ?></code>.
      </p>
    </div>
    

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-..."
            crossorigin="anonymous"></script>
  </body>
</html>