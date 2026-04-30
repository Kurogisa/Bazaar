<?php
declare(strict_types=1);

/**
 * Stock Prediction prototype - shared bootstrap.
 * - Uses SQLite (file: ../data/stocks.sqlite)
 * - Auto-creates schema on first use
 */

function stock_db_path(): string {
    $preferred = dirname(__DIR__) . '/data/stocks.sqlite';
    $preferredDir = dirname($preferred);
    if (is_dir($preferredDir) && is_writable($preferredDir)) {
        return $preferred;
    }
    // Fallback for hosted environments where project directories are not writable.
    return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'noirium_stocks.sqlite';
}

function stock_db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dbPath = stock_db_path();
    $dir = dirname($dbPath);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $pdo = new PDO('sqlite:' . $dbPath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Keep SQLite behaving well for concurrent reads/writes.
    $pdo->exec('PRAGMA busy_timeout = 3000;');
    try {
        $pdo->exec('PRAGMA journal_mode = WAL;');
    } catch (Throwable $e) {
        // If the environment can't switch journal modes, keep going (prototype).
    }
    $pdo->exec('PRAGMA synchronous = NORMAL;');
    $pdo->exec('PRAGMA foreign_keys = ON;');

    stock_migrate($pdo);
    stock_seed_if_empty($pdo);
    return $pdo;
}

function stock_migrate(PDO $pdo): void {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS stocks (
            ticker TEXT PRIMARY KEY,
            name TEXT NOT NULL,
            currency TEXT NOT NULL DEFAULT "USD",
            exchange TEXT NOT NULL DEFAULT "",
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS daily_prices (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ticker TEXT NOT NULL,
            date TEXT NOT NULL, -- YYYY-MM-DD
            open REAL,
            high REAL,
            low REAL,
            close REAL NOT NULL,
            volume INTEGER,
            source TEXT NOT NULL DEFAULT "stooq",
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ticker) REFERENCES stocks(ticker) ON DELETE CASCADE,
            UNIQUE (ticker, date)
        )'
    );

    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_prices_ticker_date ON daily_prices(ticker, date);');

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS predictions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ticker TEXT NOT NULL,
            as_of_date TEXT NOT NULL, -- last observed trading date
            horizon_days INTEGER NOT NULL DEFAULT 1,
            predicted_close REAL NOT NULL,
            predicted_return REAL NOT NULL, -- (predicted_close / last_close) - 1
            trend TEXT NOT NULL, -- bullish|bearish|neutral
            model TEXT NOT NULL, -- e.g. "linreg_30d_v1"
            features_json TEXT NOT NULL DEFAULT "{}",
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ticker) REFERENCES stocks(ticker) ON DELETE CASCADE,
            UNIQUE (ticker, as_of_date, horizon_days, model)
        )'
    );

    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_pred_ticker_asof ON predictions(ticker, as_of_date);');
}

function stock_default_universe(): array {
    // Prototype: small, recognizable, liquid set.
    return [
        ['ticker' => 'AAPL',  'name' => 'Apple Inc.'],
        ['ticker' => 'MSFT',  'name' => 'Microsoft Corp.'],
        ['ticker' => 'AMZN',  'name' => 'Amazon.com Inc.'],
        ['ticker' => 'GOOGL', 'name' => 'Alphabet Inc. (Class A)'],
        ['ticker' => 'NVDA',  'name' => 'NVIDIA Corp.'],
        ['ticker' => 'TSLA',  'name' => 'Tesla Inc.'],
        ['ticker' => 'META',  'name' => 'Meta Platforms Inc.'],
        ['ticker' => 'JPM',   'name' => 'JPMorgan Chase & Co.'],
        ['ticker' => 'XOM',   'name' => 'Exxon Mobil Corp.'],
        ['ticker' => 'SPY',   'name' => 'SPDR S&P 500 ETF Trust'],
    ];
}

function stock_seed_if_empty(PDO $pdo): void {
    $count = (int) $pdo->query('SELECT COUNT(*) AS c FROM stocks')->fetch()['c'];
    if ($count > 0) {
        return;
    }

    $pdo->beginTransaction();
    try {
        $ins = $pdo->prepare('INSERT INTO stocks(ticker, name) VALUES(:ticker, :name)');
        foreach (stock_default_universe() as $s) {
            $ins->execute([
                ':ticker' => $s['ticker'],
                ':name' => $s['name'],
            ]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function stock_json(array $payload, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_PRETTY_PRINT);
}

