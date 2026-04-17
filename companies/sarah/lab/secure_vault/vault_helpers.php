<?php
declare(strict_types=1);

function vault_user_usage_bytes(PDO $pdo, int $userId): int
{
    $st = $pdo->prepare('SELECT COALESCE(SUM(size_plain), 0) FROM vault_files WHERE user_id = ?');
    $st->execute([$userId]);
    return (int) $st->fetchColumn();
}

function vault_safe_filename(string $name): string
{
    $name = basename(str_replace(["\0", '/', '\\'], '', $name));
    $name = preg_replace('/[^a-zA-Z0-9._ -]/', '_', $name) ?? 'file.bin';
    $name = trim($name);
    if ($name === '') {
        $name = 'file.bin';
    }
    return substr($name, 0, 180);
}

function vault_user_storage_dir(array $config, int $userId): string
{
    return $config['storage_dir'] . '/' . $userId;
}

function vault_user_tmp_dir(array $config, int $userId): string
{
    return $config['tmp_dir'] . '/' . $userId;
}
