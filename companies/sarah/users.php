<?php $currentPage = "users"; ?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Users - Noirium</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/styles.css">
  </head>
  <body>
    <?php require 'navBar.php'; ?>

    <div class="container cc-container mt-5">
      <div class="cc-paper p-4 p-md-5">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-2">
          <div>
            <h1 class="mb-1">Users</h1>
            <p class="text-muted mb-0">Create and search users stored in MySQL.</p>
          </div>
          <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="users/seed.php">Seed 20 users</a>
          </div>
        </div>

        <hr class="my-4">

        <div class="list-group">
          <a class="list-group-item list-group-item-action" href="users/create.php">Create user</a>
          <a class="list-group-item list-group-item-action" href="users/search.php">Search users</a>
        </div>

        <div class="alert alert-secondary mt-4 mb-0">
          <div class="fw-semibold">Setup</div>
          <div class="small text-muted mt-1">
            Update DB credentials in <span class="fw-semibold">users/config.php</span>. Then open <span class="fw-semibold">Seed 20 users</span> once.
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>

