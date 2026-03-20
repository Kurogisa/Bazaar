<?php
// Shared product page renderer + cookie tracking.
// Each product page sets $productId and then requires this file.

if (!isset($productId)) {
  http_response_code(500);
  echo "Missing productId";
  exit;
}

$products = [
  'cyber-noir-bento' => [
    'name' => 'Cyber Noir Bento Catering',
    'tagline' => 'High-volume convention bento service with cyberpunk styling.',
    'image' => '/products/images/cyber-noir-bento/hero.svg',
    'description' => "A scalable bento program designed for convention rush hours: pre-packed meals, rapid replenishment, and branded menu cards.\n\nIncludes vegetarian options, allergen labeling, and on-site service coordination.",
  ],
  'neon-citrus-mocktail-bar' => [
    'name' => 'Neon Citrus Mocktail Bar',
    'tagline' => 'A glowing beverage station for immersive events.',
    'image' => '/products/images/neon-citrus-mocktail-bar/hero.svg',
    'description' => "A staffed mocktail bar with rotating seasonal citrus blends, optional dry-ice visual effects, and themed signage.\n\nPerfect for evening receptions, anime premieres, and cyber-noir themed parties.",
  ],
  'charcoal-macaron-box' => [
    'name' => 'Charcoal Macaron Gift Box',
    'tagline' => 'A sleek dessert set inspired by noir palettes.',
    'image' => '/products/images/charcoal-macaron-box/hero.svg',
    'description' => "A premium macaron box featuring dark cocoa shells, black sesame cream, and rotating fruit accents.\n\nAvailable as attendee gifts, VIP bundles, or merch add-ons.",
  ],
  'fantasy-arc-dessert-station' => [
    'name' => 'Fantasy Arc Dessert Station',
    'tagline' => 'Sakura, yuzu, and chocolate—curated like a story arc.',
    'image' => '/products/images/fantasy-arc-dessert-station/hero.svg',
    'description' => "A full dessert station with layered pastries, yuzu tarts, and chocolate truffles arranged in a “chapter” layout.\n\nIncludes table styling and labeled pairing notes.",
  ],
  'cosplay-greenroom-snacks' => [
    'name' => 'Cosplay Greenroom Snacks',
    'tagline' => 'Quick bites for performers, panels, and backstage crews.',
    'image' => '/products/images/cosplay-greenroom-snacks/hero.svg',
    'description' => "Individually wrapped, mess-free snacks designed for costume comfort.\n\nIncludes protein options, fruit cups, and low-allergen picks.",
  ],
  'kiki-bento' => [
    'name' => 'Kiki Bento Box',
    'tagline' => 'Menu cards and naming that match your event’s universe.',
    'image' => '/companies/sarah/products/images/kiki-bento/kiki.jpg',
    'description' => "A creative service for naming dishes, building a cohesive menu narrative, and producing print-ready menu cards.\n\nBest paired with our catering packages.",
  ],
  'vip-tasting-flight' => [
    'name' => 'VIP Tasting Flight',
    'tagline' => 'A guided tasting for sponsors and special guests.',
    'image' => '/products/images/vip-tasting-flight/hero.svg',
    'description' => "A plated tasting experience featuring 6–8 small courses with story-driven descriptions.\n\nIncludes an optional host to introduce each course.",
  ],
  'anime-watch-party-kit' => [
    'name' => 'Anime Watch Party Kit',
    'tagline' => 'Snacks + drinks + themed labels for home events.',
    'image' => '/products/images/anime-watch-party-kit/hero.svg',
    'description' => "A ready-to-go kit with savory bites, dessert portions, and mocktail mixers.\n\nIncludes printable labels and a simple setup guide.",
  ],
  'convention-dessert-trays' => [
    'name' => 'Convention Dessert Trays',
    'tagline' => 'High-impact trays for booths, meetups, and VIP rooms.',
    'image' => '/products/images/convention-dessert-trays/hero.svg',
    'description' => "Assorted dessert trays built for easy serving and fast turnover.\n\nOptions include gluten-friendly selections and clear allergen notes.",
  ],
  'private-immersive-catering' => [
    'name' => 'Private Immersive Event Catering',
    'tagline' => 'Custom menus for cosplay gatherings and themed celebrations.',
    'image' => '/products/images/private-immersive-catering/hero.svg',
    'description' => "A fully customized catering plan: menu consultation, themed plating, and optional table styling.\n\nIdeal for birthdays, club events, and private screenings.",
  ],
];

if (!isset($products[$productId])) {
  http_response_code(404);
  echo "Unknown product";
  exit;
}

$cookieOptions = [
  'expires' => time() + 60 * 60 * 24 * 30, // 30 days
  'path' => '/',
  'secure' => false,
  'httponly' => false,
  'samesite' => 'Lax',
];

// Track last 5 visited products
$recentCookieName = 'noirium_recent_products';
$recent = [];
if (isset($_COOKIE[$recentCookieName])) {
  $decoded = json_decode((string)$_COOKIE[$recentCookieName], true);
  if (is_array($decoded)) $recent = $decoded;
}
$recent = array_values(array_filter($recent, fn($id) => is_string($id) && $id !== '' && isset($products[$id])));
$recent = array_values(array_diff($recent, [$productId]));
array_unshift($recent, $productId);
$recent = array_slice($recent, 0, 5);
setcookie($recentCookieName, json_encode($recent), $cookieOptions);

// Track visit counts (for "most visited")
$countCookieName = 'noirium_product_counts';
$counts = [];
if (isset($_COOKIE[$countCookieName])) {
  $decoded = json_decode((string)$_COOKIE[$countCookieName], true);
  if (is_array($decoded)) $counts = $decoded;
}
if (!is_array($counts)) $counts = [];
foreach ($counts as $k => $v) {
  if (!is_string($k) || !isset($products[$k]) || !is_numeric($v)) unset($counts[$k]);
}
$counts[$productId] = (int)($counts[$productId] ?? 0) + 1;
setcookie($countCookieName, json_encode($counts), $cookieOptions);

$product = $products[$productId];
$currentPage = "product";
$basePath = '../';
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?> - Noirium</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  </head>
  <body>
    <?php require __DIR__ . '/../navBar.php'; ?>

    <div class="container mt-5">
      <div class="mb-3">
        <a href="../product.php" class="btn btn-outline-secondary btn-sm">&larr; Back to Products</a>
      </div>

      <h1 class="mb-2"><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></h1>
      <p class="text-muted"><?php echo htmlspecialchars($product['tagline'], ENT_QUOTES, 'UTF-8'); ?></p>

      <div class="card mb-4">
        <img
          src="<?php echo htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8'); ?>"
          class="card-img-top"
          alt="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>"
        >
      </div>

      <?php foreach (explode("\n", (string)$product['description']) as $line): ?>
        <?php if (trim($line) !== ''): ?>
          <p><?php echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
      <?php endforeach; ?>

      <hr class="my-4">
      <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-primary" href="recent.php">Last 5 visited products</a>
        <a class="btn btn-outline-primary" href="popular.php">Top 5 most visited products</a>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
