<?php
/**
 * Ensures SQLite DB and writable dirs exist.
 */
declare(strict_types=1);

$config = require __DIR__ . '/config.php';

foreach ([dirname($config['db_path']), $config['storage_dir'], $config['tmp_dir']] as $dir) {
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new RuntimeException('Cannot create directory: ' . $dir);
        }
    }
}

$pdo = new PDO('sqlite:' . $config['db_path']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        kdf_salt BLOB NOT NULL,
        created_at INTEGER NOT NULL
    )'
);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS vault_files (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        disk_name TEXT NOT NULL,
        orig_name TEXT NOT NULL,
        size_plain INTEGER NOT NULL,
        crypto_algo INTEGER NOT NULL,
        created_at INTEGER NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )'
);

$pdo->exec('CREATE INDEX IF NOT EXISTS idx_vault_files_user ON vault_files(user_id)');

return [$config, $pdo];
