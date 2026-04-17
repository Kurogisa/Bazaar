<?php
/**
 * Streaming encrypt/decrypt for large files (up to 4GB) without loading whole file in memory.
 *
 * Preferred: libsodium secretstream (XChaCha20-Poly1305).
 * Fallback: AES-256-CBC + HMAC-SHA256 (encrypt-then-MAC over ciphertext bytes).
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';

const VAULT_MAGIC = "NVLT";
const VAULT_FILE_VERSION = 1;

/** Plaintext bytes per sodium secretstream frame (must match encrypt + decrypt). */
const VAULT_SODIUM_PLAIN_CHUNK_BYTES = 1024 * 1024;

function vault_pick_algo(): int
{
    if (function_exists('sodium_crypto_secretstream_xchacha20poly1305_init_push')) {
        return VAULT_CRYPTO_SODIUM;
    }
    return VAULT_CRYPTO_OPENSSL_CBC;
}

function vault_pkcs7_pad(string $data, int $block): string
{
    $pad = $block - (strlen($data) % $block);
    return $data . str_repeat(chr($pad), $pad);
}

/**
 * @param resource $in
 * @param resource $out
 */
function vault_encrypt_stream($in, $out, string $key32, int $algo): void
{
    if (strlen($key32) !== 32) {
        throw new InvalidArgumentException('Key must be 32 bytes');
    }
    fwrite($out, VAULT_MAGIC . chr(VAULT_FILE_VERSION) . chr($algo));

    if ($algo === VAULT_CRYPTO_SODIUM) {
        vault_encrypt_stream_sodium($in, $out, $key32);
        return;
    }
    vault_encrypt_stream_openssl_cbc($in, $out, $key32);
}

/**
 * @param resource $in
 * @param resource $out
 */
function vault_decrypt_stream($in, $out, string $key32): void
{
    if (strlen($key32) !== 32) {
        throw new InvalidArgumentException('Key must be 32 bytes');
    }
    $hdr = fread($in, strlen(VAULT_MAGIC) + 2);
    if ($hdr === false || strlen($hdr) !== strlen(VAULT_MAGIC) + 2) {
        throw new RuntimeException('Truncated vault file header');
    }
    if (substr($hdr, 0, strlen(VAULT_MAGIC)) !== VAULT_MAGIC) {
        throw new RuntimeException('Not a vault file');
    }
    $ver = ord($hdr[strlen(VAULT_MAGIC)]);
    $algo = ord($hdr[strlen(VAULT_MAGIC) + 1]);
    if ($ver !== VAULT_FILE_VERSION) {
        throw new RuntimeException('Unsupported vault file version');
    }
    if ($algo === VAULT_CRYPTO_SODIUM) {
        vault_decrypt_stream_sodium($in, $out, $key32);
        return;
    }
    if ($algo === VAULT_CRYPTO_OPENSSL_CBC) {
        vault_decrypt_stream_openssl_cbc($in, $out, $key32);
        return;
    }
    throw new RuntimeException('Unknown crypto algo');
}

/**
 * @param resource $in
 * @param resource $out
 */
function vault_encrypt_stream_sodium($in, $out, string $key32): void
{
    /** @var array{0: string, 1: string} $pair */
    $pair = sodium_crypto_secretstream_xchacha20poly1305_init_push($key32);
    $state = $pair[0];
    $header = $pair[1];
    fwrite($out, $header);

    while (!feof($in)) {
        $chunk = fread($in, VAULT_SODIUM_PLAIN_CHUNK_BYTES);
        if ($chunk === false) {
            break;
        }
        if ($chunk === '') {
            continue;
        }
        $tag = feof($in)
            ? SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL
            : SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE;
        // Arg 3 = additional_data (string); arg 4 = tag (int). Passing $tag as arg 3 breaks on PHP 8+ strict types.
        $cipher = sodium_crypto_secretstream_xchacha20poly1305_push($state, $chunk, '', $tag);
        fwrite($out, $cipher);
    }
}

/**
 * @param resource $in
 * @param resource $out
 */
function vault_decrypt_stream_sodium($in, $out, string $key32): void
{
    $header = fread($in, SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES);
    if ($header === false || strlen($header) !== SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES) {
        throw new RuntimeException('Bad sodium header');
    }
    $state = sodium_crypto_secretstream_xchacha20poly1305_init_pull($header, $key32);

    // One push() per encrypt loop iteration; pull() must receive exactly those bytes (not a split chunk).
    $maxCipherChunk = VAULT_SODIUM_PLAIN_CHUNK_BYTES + SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_ABYTES;
    while (true) {
        $cipher = fread($in, $maxCipherChunk);
        if ($cipher === false || $cipher === '') {
            break;
        }
        /** @var array{0: string, 1: int} $pull */
        $pull = sodium_crypto_secretstream_xchacha20poly1305_pull($state, $cipher);
        $plain = $pull[0];
        $tag = $pull[1];
        if ($plain !== '') {
            fwrite($out, $plain);
        }
        if ($tag === SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL) {
            break;
        }
    }
}

/**
 * @param resource $in
 * @param resource $out
 */
function vault_encrypt_stream_openssl_cbc($in, $out, string $key32): void
{
    $iv = random_bytes(16);
    fwrite($out, $iv);
    $prev = $iv;
    $macKey = hash_hmac('sha256', 'vault-aes-integrity-v1', $key32, true);
    $h = hash_init('sha256', HASH_HMAC, $macKey);

    $buffer = '';
    while (true) {
        $chunk = fread($in, 65536);
        if ($chunk === false) {
            $chunk = '';
        }
        $buffer .= $chunk;
        $eof = feof($in);

        if (!$eof) {
            $usable = (int) (floor(strlen($buffer) / 16) * 16);
            if ($usable > 0) {
                $block = substr($buffer, 0, $usable);
                $buffer = substr($buffer, $usable);
                $ct = openssl_encrypt($block, 'aes-256-cbc', $key32, OPENSSL_RAW_DATA, $prev);
                if ($ct === false) {
                    throw new RuntimeException('OpenSSL encrypt failed');
                }
                hash_update($h, $ct);
                fwrite($out, $ct);
                $prev = substr($ct, -16);
            }
            continue;
        }

        $padded = vault_pkcs7_pad($buffer, 16);
        $ct = openssl_encrypt($padded, 'aes-256-cbc', $key32, OPENSSL_RAW_DATA, $prev);
        if ($ct === false) {
            throw new RuntimeException('OpenSSL encrypt failed');
        }
        hash_update($h, $ct);
        fwrite($out, $ct);
        fwrite($out, hash_final($h, true));
        break;
    }
}

/**
 * @param resource $in
 * @param resource $out
 */
function vault_decrypt_stream_openssl_cbc($in, $out, string $key32): void
{
    $iv = fread($in, 16);
    if ($iv === false || strlen($iv) !== 16) {
        throw new RuntimeException('Bad IV');
    }
    $macKey = hash_hmac('sha256', 'vault-aes-integrity-v1', $key32, true);

    $meta = stream_get_meta_data($in);
    if (!isset($meta['uri'])) {
        throw new RuntimeException('Stream must be a regular file for AES fallback (need filesize)');
    }
    $path = $meta['uri'];
    clearstatcache(true, $path);
    $size = filesize($path);
    if ($size === false) {
        throw new RuntimeException('Cannot stat vault file');
    }
    $posAfterIv = ftell($in);
    if ($posAfterIv === false) {
        throw new RuntimeException('ftell failed');
    }
    if ($size < $posAfterIv + 32) {
        throw new RuntimeException('Vault file too small');
    }
    $cipherLen = $size - $posAfterIv - 32;

    $h = hash_init('sha256', HASH_HMAC, $macKey);
    $prev = $iv;
    $readCipher = 0;
    $carry = '';

    while ($readCipher < $cipherLen) {
        $need = min(1024 * 1024, $cipherLen - $readCipher);
        $ct = fread($in, $need);
        if ($ct === false || $ct === '') {
            throw new RuntimeException('Unexpected EOF in ciphertext');
        }
        if ((strlen($ct) % 16) !== 0) {
            throw new RuntimeException('Ciphertext length not aligned');
        }
        hash_update($h, $ct);
        $pt = openssl_decrypt($ct, 'aes-256-cbc', $key32, OPENSSL_RAW_DATA, $prev);
        if ($pt === false) {
            throw new RuntimeException('OpenSSL decrypt failed');
        }
        $prev = substr($ct, -16);
        $readCipher += strlen($ct);

        $buf = $carry . $pt;
        if ($readCipher < $cipherLen) {
            if (strlen($buf) > 16) {
                fwrite($out, substr($buf, 0, -16));
                $carry = substr($buf, -16);
            } else {
                $carry = $buf;
            }
        } else {
            if ($buf === '') {
                throw new RuntimeException('Empty plaintext');
            }
            $pad = ord($buf[strlen($buf) - 1]);
            if ($pad < 1 || $pad > 16) {
                throw new RuntimeException('Bad padding');
            }
            fwrite($out, substr($buf, 0, strlen($buf) - $pad));
        }
    }

    $mac = fread($in, 32);
    if ($mac === false || strlen($mac) !== 32) {
        throw new RuntimeException('Missing MAC');
    }
    if (!hash_equals(hash_final($h, true), $mac)) {
        throw new RuntimeException('Integrity check failed (wrong password or corrupted file)');
    }
}
