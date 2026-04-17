<?php
/**
 * Secure Vault lab — configuration
 *
 * Authentication: PHP sessions + password_hash / password_verify (bcrypt).
 * Encryption key: PBKDF2-HMAC-SHA256 from password + per-user salt (derived at login, kept in session only).
 * Content encryption: libsodium secretstream (XChaCha20-Poly1305) if available; else AES-256-CBC streaming.
 */

declare(strict_types=1);

// Max single file / per-user vault (4 GiB). Lab requirement: support up to 4GB stored locally.
const VAULT_MAX_BYTES_PER_USER = 4 * 1024 * 1024 * 1024;
const VAULT_MAX_SINGLE_FILE = 4 * 1024 * 1024 * 1024;

// Chunk size for browser uploads (stay under typical post_max_size on shared hosts).
const VAULT_UPLOAD_CHUNK_BYTES = 4 * 1024 * 1024; // 4 MiB

// PBKDF2 iterations (balance security vs login time on shared hosting).
const VAULT_PBKDF2_ITERATIONS = 100000;

const VAULT_CRYPTO_SODIUM = 1;
const VAULT_CRYPTO_OPENSSL_CBC = 2;

$vaultRoot = __DIR__;
return [
    'db_path' => $vaultRoot . '/data/vault.sqlite',
    'storage_dir' => $vaultRoot . '/storage',
    'tmp_dir' => $vaultRoot . '/tmp',
    'session_name' => 'noirium_vault_sess',
];
