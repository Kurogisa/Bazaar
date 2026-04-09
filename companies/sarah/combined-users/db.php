<?php
/**
 * db.php
 * Creates and returns a PDO connection to the LOCAL company database.
 */

function getLocalPdo(array $config): PDO
{
  $db = $config['db'];

  $dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $db['host'],
    (int)$db['port'],
    $db['database'],
    $db['charset']
  );

  $pdo = new PDO($dsn, $db['username'], $db['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);

  return $pdo;
}

/**
 * Local DB access (direct query).
 * This is used by index.php and api/users.php for local users.
 */
function fetchLocalUsers(PDO $pdo): array
{
  $sql = 'SELECT id, name, email, company FROM users ORDER BY id ASC';
  $stmt = $pdo->query($sql);
  return $stmt->fetchAll();
}
