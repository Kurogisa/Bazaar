<?php
declare(strict_types=1);

$currentPage = "users";
$basePath = "../";

require_once __DIR__ . '/bootstrap.php';
[$config, $pdo] = users_bootstrap();

$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$results = [];
$error = '';

if ($q !== '') {
  $qPhone = users_clean_phone($q);
  $like = '%' . $q . '%';
  $likePhone = '%' . $qPhone . '%';

  try {
    $stmt = $pdo->prepare(
      'SELECT id, first_name, last_name, email, home_address, home_phone, cell_phone, created_at
       FROM users
       WHERE first_name LIKE :like
          OR last_name LIKE :like
          OR email LIKE :like
          OR home_phone LIKE :likePhone
          OR cell_phone LIKE :likePhone
       ORDER BY last_name ASC, first_name ASC
       LIMIT 200'
    );
    $stmt->execute([
      ':like' => $like,
      ':likePhone' => $likePhone,
    ]);
    $results = $stmt->fetchAll();
  } catch (Throwable $e) {
    $error = 'Database error: ' . $e->getMessage();
  }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Search Users - Noirium</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="../assets/styles.css">
    <style>
      .table td { vertical-align: middle; }
    </style>
  </head>
  <body>
    <?php require '../navBar.php'; ?>

    <div class="container cc-container mt-5">
      <div class="cc-paper p-4 p-md-5">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-2">
          <div>
            <h1 class="mb-1">Search users</h1>
            <p class="text-muted mb-0">Search by name, email, or phone numbers.</p>
          </div>
          <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="../users.php">Back</a>
            <a class="btn btn-outline-primary" href="create.php">Create user</a>
          </div>
        </div>

        <hr class="my-4">

        <?php if ($error !== ''): ?>
          <div class="alert alert-warning"><?php echo users_h($error); ?></div>
        <?php endif; ?>

        <form method="get" class="row g-2 align-items-end">
          <div class="col-12 col-md-8">
            <label class="form-label">Search</label>
            <input name="q" class="form-control" value="<?php echo users_h($q); ?>" placeholder="e.g., Sarah, sarah@, 408555" autofocus>
          </div>
          <div class="col-12 col-md-4 d-flex gap-2">
            <button class="btn btn-primary" type="submit">Search</button>
            <a class="btn btn-outline-secondary" href="search.php">Clear</a>
          </div>
        </form>

        <div class="mt-4">
          <?php if ($q === ''): ?>
            <div class="text-muted">Enter a query to search.</div>
          <?php else: ?>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
              <div class="text-muted">
                Results for <span class="fw-semibold"><?php echo users_h($q); ?></span>
              </div>
              <div class="text-muted small">
                Showing <?php echo (int)count($results); ?> (max 200)
              </div>
            </div>

            <div class="table-responsive mt-3">
              <table class="table table-sm table-striped">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Home address</th>
                    <th>Home phone</th>
                    <th>Cell phone</th>
                    <th>Created</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (count($results) === 0): ?>
                    <tr><td colspan="7">No users found.</td></tr>
                  <?php else: ?>
                    <?php foreach ($results as $u): ?>
                      <tr>
                        <td><?php echo users_h((string)$u['id']); ?></td>
                        <td><?php echo users_h((string)$u['first_name'] . ' ' . (string)$u['last_name']); ?></td>
                        <td><?php echo users_h((string)$u['email']); ?></td>
                        <td><?php echo users_h((string)$u['home_address']); ?></td>
                        <td><?php echo users_h((string)$u['home_phone']); ?></td>
                        <td><?php echo users_h((string)$u['cell_phone']); ?></td>
                        <td class="text-muted small"><?php echo users_h((string)$u['created_at']); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>

