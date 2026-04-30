<?php
declare(strict_types=1);

$currentPage = "users";
$basePath = "../";

require_once __DIR__ . '/bootstrap.php';
[$config, $pdo] = users_bootstrap();

$count = (int)$pdo->query('SELECT COUNT(*) AS c FROM users')->fetchColumn();

$inserted = 0;
$skipped = 0;
$error = '';

// 20 sample users (adjust company-themed names/addresses if you want).
$seedUsers = [
  ['Ava','Noir','ava.noir@example.com','12 Rose St, San Jose, CA','+14085550101','+14085550901'],
  ['Liam','Sage','liam.sage@example.com','44 Honey Ln, San Jose, CA','+14085550102','+14085550902'],
  ['Mia','Cottage','mia.cottage@example.com','3 Willow Ave, Cupertino, CA','+14085550103','+14085550903'],
  ['Noah','Market','noah.market@example.com','77 Blossom Rd, Sunnyvale, CA','+14085550104','+14085550904'],
  ['Emma','Brew','emma.brew@example.com','5 Cedar Ct, Santa Clara, CA','+14085550105','+14085550905'],
  ['Oliver','Lantern','oliver.lantern@example.com','18 Maple Dr, Milpitas, CA','+14085550106','+14085550906'],
  ['Sophia','Moss','sophia.moss@example.com','62 Fern Way, Mountain View, CA','+14085550107','+14085550907'],
  ['Elijah','Harvest','elijah.harvest@example.com','9 Orchard St, Palo Alto, CA','+14085550108','+14085550908'],
  ['Isabella','Rosewood','isabella.rosewood@example.com','101 Acorn Blvd, San Jose, CA','+14085550109','+14085550909'],
  ['James','Scone','james.scone@example.com','22 Teacup Ln, Cupertino, CA','+14085550110','+14085550910'],
  ['Charlotte','Clover','charlotte.clover@example.com','14 Meadow Dr, Sunnyvale, CA','+14085550111','+14085550911'],
  ['Benjamin','Whisk','benjamin.whisk@example.com','70 Cozy Ct, Santa Clara, CA','+14085550112','+14085550912'],
  ['Amelia','Basil','amelia.basil@example.com','6 Hearth Ave, Milpitas, CA','+14085550113','+14085550913'],
  ['Lucas','Pastry','lucas.pastry@example.com','88 Kettle Rd, Mountain View, CA','+14085550114','+14085550914'],
  ['Harper','Lace','harper.lace@example.com','27 Linen St, Palo Alto, CA','+14085550115','+14085550915'],
  ['Henry','Baker','henry.baker@example.com','33 Creamery Way, San Jose, CA','+14085550116','+14085550916'],
  ['Evelyn','Breeze','evelyn.breeze@example.com','4 Sunbeam Ln, Cupertino, CA','+14085550117','+14085550917'],
  ['Alexander','Thyme','alexander.thyme@example.com','59 Sprout Rd, Sunnyvale, CA','+14085550118','+14085550918'],
  ['Abigail','River','abigail.river@example.com','11 Brook Ct, Santa Clara, CA','+14085550119','+14085550919'],
  ['Daniel','Bloom','daniel.bloom@example.com','90 Petal Ave, Milpitas, CA','+14085550120','+14085550920'],
];

try {
  $pdo->beginTransaction();

  $stmt = $pdo->prepare(
    'INSERT INTO users(first_name, last_name, email, home_address, home_phone, cell_phone)
     VALUES(:fn, :ln, :em, :addr, :hp, :cp)'
  );

  foreach ($seedUsers as $u) {
    [$fn, $ln, $em, $addr, $hp, $cp] = $u;
    try {
      $stmt->execute([
        ':fn' => $fn,
        ':ln' => $ln,
        ':em' => $em,
        ':addr' => $addr,
        ':hp' => users_clean_phone($hp),
        ':cp' => users_clean_phone($cp),
      ]);
      $inserted++;
    } catch (Throwable $e) {
      $skipped++;
    }
  }

  $pdo->commit();
} catch (Throwable $e) {
  $pdo->rollBack();
  $error = $e->getMessage();
}

$newCount = (int)$pdo->query('SELECT COUNT(*) AS c FROM users')->fetchColumn();
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Seed Users - Noirium</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="../assets/styles.css">
  </head>
  <body>
    <?php require '../navBar.php'; ?>

    <div class="container cc-container mt-5">
      <div class="cc-paper p-4 p-md-5">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-2">
          <div>
            <h1 class="mb-1">Seed 20 users</h1>
            <p class="text-muted mb-0">One-time helper to satisfy the “20 users” requirement.</p>
          </div>
          <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="../users.php">Back</a>
            <a class="btn btn-outline-primary" href="search.php?q=">Search users</a>
          </div>
        </div>

        <hr class="my-4">

        <?php if ($error !== ''): ?>
          <div class="alert alert-warning">Seed failed: <?php echo users_h($error); ?></div>
        <?php else: ?>
          <div class="alert alert-success">
            Inserted <?php echo (int)$inserted; ?> users (skipped <?php echo (int)$skipped; ?> duplicates).
          </div>
        <?php endif; ?>

        <div class="small text-muted">
          Users before: <?php echo (int)$count; ?> • Users now: <?php echo (int)$newCount; ?>
        </div>

        <div class="mt-3">
          <a class="btn btn-primary" href="search.php?q=">Go to search</a>
          <a class="btn btn-outline-primary" href="create.php">Create one manually</a>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>

