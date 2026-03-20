<?php
// Shared catalog data for listing pages.

$PRODUCT_CATALOG = [
  [
    'id' => 'cyber-noir-bento',
    'name' => 'Cyber Noir Bento Catering',
    'href' => 'cyber-noir-bento.php',
    'image' => '/products/images/cyber-noir-bento/hero.svg',
  ],
  [
    'id' => 'neon-citrus-mocktail-bar',
    'name' => 'Neon Citrus Mocktail Bar',
    'href' => 'neon-citrus-mocktail-bar.php',
    'image' => '/products/images/neon-citrus-mocktail-bar/hero.svg',
  ],
  [
    'id' => 'charcoal-macaron-box',
    'name' => 'Charcoal Macaron Gift Box',
    'href' => 'charcoal-macaron-box.php',
    'image' => '/products/images/charcoal-macaron-box/hero.svg',
  ],
  [
    'id' => 'fantasy-arc-dessert-station',
    'name' => 'Fantasy Arc Dessert Station',
    'href' => 'fantasy-arc-dessert-station.php',
    'image' => '/products/images/fantasy-arc-dessert-station/hero.svg',
  ],
  [
    'id' => 'cosplay-greenroom-snacks',
    'name' => 'Cosplay Greenroom Snacks',
    'href' => 'cosplay-greenroom-snacks.php',
    'image' => '/products/images/cosplay-greenroom-snacks/hero.svg',
  ],
  [
    'id' => 'kiki-bento-design',
    'name' => 'Kiki Bento Box',
    'href' => 'kiki-bento.php',
    'image' => '/companies/sarah/products/images/kiki-bento/kiki.jpg',
  ],
  [
    'id' => 'vip-tasting-flight',
    'name' => 'VIP Tasting Flight',
    'href' => 'vip-tasting-flight.php',
    'image' => '/products/images/vip-tasting-flight/hero.svg',
  ],
  [
    'id' => 'anime-watch-party-kit',
    'name' => 'Anime Watch Party Kit',
    'href' => 'anime-watch-party-kit.php',
    'image' => '/products/images/anime-watch-party-kit/hero.svg',
  ],
  [
    'id' => 'convention-dessert-trays',
    'name' => 'Convention Dessert Trays',
    'href' => 'convention-dessert-trays.php',
    'image' => '/products/images/convention-dessert-trays/hero.svg',
  ],
  [
    'id' => 'private-immersive-catering',
    'name' => 'Private Immersive Event Catering',
    'href' => 'private-immersive-catering.php',
    'image' => '/products/images/private-immersive-catering/hero.svg',
  ],
];

$PRODUCT_BY_ID = [];
foreach ($PRODUCT_CATALOG as $p) {
  $PRODUCT_BY_ID[(string)$p['id']] = $p;
}
?>
