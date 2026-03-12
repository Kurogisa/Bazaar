<?php
session_start();

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
  header('Location: ../login.php?redirect=secure/user.php');
  exit;
}

$currentPage = "secure";
$basePath = '../';
$users = [
  'Mary Smith',
  'John Wang',
  'Alex Bington',
  'Kuroko Tetsuya',
  'Roronora Zoro'
];
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Secure Users - Noirium</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  </head>
  <body>
    <?php require '../navBar.php'; ?>

    <div class="container mt-5">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
          <h1 class="mb-1">Secure Section</h1>
          <p class="text-muted mb-0">
            Logged in as <strong><?php echo htmlspecialchars((string)($_SESSION['userid'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
          </p>
        </div>
        <div>
          <a class="btn btn-outline-secondary" href="logout.php">Logout</a>
        </div>
      </div>

      <hr class="my-4">

      <h2 class="h4">Current users of the web site</h2>
      <ul class="list-group mt-3">
        <?php foreach ($users as $name): ?>
          <li class="list-group-item"><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
