<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function users_bootstrap(): array
{
  $config = require __DIR__ . '/config.php';
  $pdo = users_pdo($config);
  users_migrate($pdo);

  return [$config, $pdo];
}

