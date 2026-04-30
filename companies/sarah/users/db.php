<?php
declare(strict_types=1);

function users_pdo(array $config): PDO
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

function users_migrate(PDO $pdo): void
{
  $pdo->exec(
    'CREATE TABLE IF NOT EXISTS users (
      id INT AUTO_INCREMENT PRIMARY KEY,
      first_name VARCHAR(80) NOT NULL,
      last_name VARCHAR(80) NOT NULL,
      email VARCHAR(190) NOT NULL,
      home_address VARCHAR(255) NOT NULL,
      home_phone VARCHAR(40) NOT NULL,
      cell_phone VARCHAR(40) NOT NULL,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY uniq_email (email),
      KEY idx_name (last_name, first_name),
      KEY idx_home_phone (home_phone),
      KEY idx_cell_phone (cell_phone)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
  );
}

function users_clean_phone(string $phone): string
{
  // Keep digits and leading +
  $phone = trim($phone);
  $phone = preg_replace('/(?!^)\\D+/', '', $phone); // remove non-digits except possibly at start
  return $phone ?? '';
}

function users_h(string $s): string
{
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

