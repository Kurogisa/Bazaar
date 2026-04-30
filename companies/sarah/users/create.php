<?php
declare(strict_types=1);

$currentPage = "users";
$basePath = "../";

require_once __DIR__ . '/bootstrap.php';

[$config, $pdo] = users_bootstrap();

$errors = [];
$okMsg = '';

$values = [
  'first_name' => '',
  'last_name' => '',
  'email' => '',
  'home_address' => '',
  'home_phone' => '',
  'cell_phone' => '',
];

function postv(string $k): string {
  return isset($_POST[$k]) ? trim((string)$_POST[$k]) : '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  foreach (array_keys($values) as $k) {
    $values[$k] = postv($k);
  }
  $values['home_phone'] = users_clean_phone($values['home_phone']);
  $values['cell_phone'] = users_clean_phone($values['cell_phone']);

  if ($values['first_name'] === '') $errors[] = 'First name is required.';
  if ($values['last_name'] === '') $errors[] = 'Last name is required.';
  if ($values['email'] === '' || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
  if ($values['home_address'] === '') $errors[] = 'Home address is required.';
  if ($values['home_phone'] === '') $errors[] = 'Home phone is required.';
  if ($values['cell_phone'] === '') $errors[] = 'Cell phone is required.';

  if (count($errors) === 0) {
    try {
      $stmt = $pdo->prepare(
        'INSERT INTO users(first_name, last_name, email, home_address, home_phone, cell_phone)
         VALUES(:fn, :ln, :em, :addr, :hp, :cp)'
      );
      $stmt->execute([
        ':fn' => $values['first_name'],
        ':ln' => $values['last_name'],
        ':em' => $values['email'],
        ':addr' => $values['home_address'],
        ':hp' => $values['home_phone'],
        ':cp' => $values['cell_phone'],
      ]);
      $okMsg = 'User created successfully.';
      foreach (array_keys($values) as $k) $values[$k] = '';
    } catch (Throwable $e) {
      // Handle duplicate email nicely
      if (str_contains(strtolower($e->getMessage()), 'uniq_email')) {
        $errors[] = 'That email already exists.';
      } else {
        $errors[] = 'Database error: ' . $e->getMessage();
      }
    }
  }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create User - Noirium</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="../assets/styles.css">
  </head>
  <body>
    <?php require '../navBar.php'; ?>

    <div class="container cc-container mt-5">
      <div class="cc-paper p-4 p-md-5">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-2">
          <div>
            <h1 class="mb-1">Create user</h1>
            <p class="text-muted mb-0">Adds a new user record to MySQL.</p>
          </div>
          <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="../users.php">Back</a>
            <a class="btn btn-outline-primary" href="search.php">Search users</a>
          </div>
        </div>

        <hr class="my-4">

        <?php if ($okMsg !== ''): ?>
          <div class="alert alert-success"><?php echo users_h($okMsg); ?></div>
        <?php endif; ?>
        <?php if (count($errors) > 0): ?>
          <div class="alert alert-warning">
            <div class="fw-semibold mb-1">Please fix:</div>
            <ul class="mb-0">
              <?php foreach ($errors as $err): ?>
                <li><?php echo users_h((string)$err); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="post" class="row g-3">
          <div class="col-12 col-md-6">
            <label class="form-label">First name</label>
            <input name="first_name" class="form-control" value="<?php echo users_h($values['first_name']); ?>" required>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Last name</label>
            <input name="last_name" class="form-control" value="<?php echo users_h($values['last_name']); ?>" required>
          </div>
          <div class="col-12">
            <label class="form-label">Email</label>
            <input name="email" type="email" class="form-control" value="<?php echo users_h($values['email']); ?>" required>
          </div>
          <div class="col-12">
            <label class="form-label">Home address</label>
            <input name="home_address" class="form-control" value="<?php echo users_h($values['home_address']); ?>" required>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Home phone</label>
            <input name="home_phone" class="form-control" value="<?php echo users_h($values['home_phone']); ?>" placeholder="(408) 555-0101" required>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Cell phone</label>
            <input name="cell_phone" class="form-control" value="<?php echo users_h($values['cell_phone']); ?>" placeholder="(408) 555-0199" required>
          </div>
          <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary" type="submit">Create user</button>
            <a class="btn btn-outline-secondary" href="create.php">Reset</a>
          </div>
        </form>

      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>

