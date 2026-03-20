<?php
$currentPage = "product";
require __DIR__ . '/products/_catalog.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Products & Services - Noirium</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  </head>
  <body>
    <?php require 'navBar.php'; ?>

    <div class="container mt-5">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
          <h1 class="mb-1">Products & Services</h1>
          <p class="text-muted mb-0">Explore 10 offerings. Visits are tracked using cookies.</p>
        </div>
        <div class="d-flex gap-2">
          <a class="btn btn-outline-primary" href="products/recent.php">Last 5 visited</a>
          <a class="btn btn-outline-primary" href="products/popular.php">Top 5 most visited</a>
        </div>
      </div>

      <hr class="my-4">

      <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php foreach ($PRODUCT_CATALOG as $p): ?>
          <div class="col">
            <div class="card h-100">
              <img
                src="<?php echo htmlspecialchars((string)$p['image'], ENT_QUOTES, 'UTF-8'); ?>"
                class="card-img-top"
                alt="<?php echo htmlspecialchars((string)$p['name'], ENT_QUOTES, 'UTF-8'); ?>"
                style="height: 180px; object-fit: cover;"
              >
              <div class="card-body d-flex flex-column">
                <h2 class="h5 card-title"><?php echo htmlspecialchars((string)$p['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
                <div class="mt-auto">
                  <a class="btn btn-primary w-100" href="<?php echo 'products/' . htmlspecialchars((string)$p['href'], ENT_QUOTES, 'UTF-8'); ?>">
                    View details
                  </a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-..."
            crossorigin="anonymous"></script>
  </body>
</html>