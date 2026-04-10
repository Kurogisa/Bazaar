<?php $currentPage = "home"; ?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Homepage - Noirium</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/styles.css">
  </head>
  <body>
    <?php require 'navBar.php'; ?>

    <!-- Main Content -->
    <div class="container cc-container mt-5">
      <div class="cc-paper p-4 p-md-5">
      <h1>Welcome to Noirium</h1>
      <p class="text-muted mb-4">A whimsical cottage-core cafe marketplace for charming bites and cozy events.</p>

      <hr class="cc-divider">

      <p>
        <a href="https://aliciayerinkim.com/cat.php" class="btn btn-primary">Human Drawn Cat</a>
        <a href="http://noirium.net/lab/lab04_ai.php" class="btn btn-secondary">AI Drawn Cat</a>
      </p>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>