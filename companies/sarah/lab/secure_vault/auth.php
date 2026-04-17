<?php
/**
 * Session authentication + vault key in session (binary as base64).
 */
declare(strict_types=1);

$config = require __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name($config['session_name']);
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

function vault_require_login(): void
{
    if (empty($_SESSION['vault_user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function vault_user_id(): int
{
    return (int) ($_SESSION['vault_user_id'] ?? 0);
}

function vault_username(): string
{
    return (string) ($_SESSION['vault_username'] ?? '');
}

/**
 * 32-byte key derived at login; never stored on disk.
 */
function vault_key_binary(): string
{
    $b64 = (string) ($_SESSION['vault_key_b64'] ?? '');
    $raw = base64_decode($b64, true);
    if ($raw === false || strlen($raw) !== 32) {
        throw new RuntimeException('Vault session key missing; log in again.');
    }
    return $raw;
}

function vault_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function vault_derive_key_from_password(string $password, string $saltBinary): string
{
    $key = hash_pbkdf2('sha256', $password, $saltBinary, VAULT_PBKDF2_ITERATIONS, 32, true);
    if ($key === false || strlen($key) !== 32) {
        throw new RuntimeException('KDF failed');
    }
    return $key;
}
