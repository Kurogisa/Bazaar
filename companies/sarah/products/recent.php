<?php
$currentPage = "product";
require __DIR__ . '/_catalog.php';

$recentCookieName = 'noirium_recent_products';
$recent = [];
if (isset($_COOKIE[$recentCookieName])) {
  $decoded = json_decode((string)$_COOKIE[$recentCookieName], true);
  if (is_array($decoded)) $recent = $decoded;
}
$recent = array_values(array_filter($recent, fn($id) => is_string($id) && isset($PRODUCT_BY_ID[$id])));
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Last 5 Visited Products - Noirium</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  </head>
  <body>
    <?php $basePath = '../'; require __DIR__ . '/../navBar.php'; ?>

    <div class="container mt-5">
      <div class="mb-3">
        <a href="../product.php" class="btn btn-outline-secondary btn-sm">&larr; Back to Products</a>
      </div>

      <h1 class="mb-3">Last 5 visited products</h1>
      <p class="text-muted">Tracked using web cookies.</p>

      <?php if (count($recent) === 0): ?>
        <div class="alert alert-info">No product visits tracked yet. Visit a product page first.</div>
      <?php else: ?>
        <div class="list-group">
          <?php foreach ($recent as $id): ?>
            <?php $p = $PRODUCT_BY_ID[$id]; ?>
            <a class="list-group-item list-group-item-action d-flex align-items-center gap-3" href="<?php echo htmlspecialchars($p['href'], ENT_QUOTES, 'UTF-8'); ?>">
              <img src="<?php echo htmlspecialchars($p['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="" width="72" height="48" style="object-fit: cover; border-radius: 6px;">
              <div class="flex-grow-1">
                <?php echo htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8'); ?>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
