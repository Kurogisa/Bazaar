<?php
declare(strict_types=1);

require_once __DIR__ . '/stock_bootstrap.php';

/**
 * api/stock_ingest.php
 * Triggers ingestion for one or more tickers.
 *
 * Usage:
 * - GET  /api/stock_ingest.php            (ingest default universe)
 * - GET  /api/stock_ingest.php?ticker=AAPL
 * - GET  /api/stock_ingest.php?ticker=AAPL,MSFT
 *
 * Returns JSON summary.
 */

$pdo = stock_db();

$tickerParam = isset($_GET['ticker']) ? trim((string)$_GET['ticker']) : '';
$tickers = [];

if ($tickerParam !== '') {
    foreach (explode(',', $tickerParam) as $t) {
        $t = strtoupper(trim($t));
        if ($t !== '') {
            $tickers[] = $t;
        }
    }
} else {
    $rows = $pdo->query('SELECT ticker FROM stocks ORDER BY ticker')->fetchAll();
    foreach ($rows as $r) {
        $tickers[] = (string)$r['ticker'];
    }
}

$tickers = array_values(array_unique($tickers));
if (count($tickers) === 0) {
    stock_json(['success' => false, 'message' => 'No tickers provided.'], 400);
    exit;
}

// Ingest last ~2 years of daily data (prototype).
$from = (new DateTimeImmutable('now'))->modify('-750 days')->format('Ymd');
$to = (new DateTimeImmutable('now'))->format('Ymd');

$results = [];
$totalInserted = 0;
$totalUpdated = 0;
$totalFailed = 0;

foreach ($tickers as $ticker) {
    try {
        $res = stock_ingest_ticker_daily($pdo, $ticker, $from, $to);
        $results[] = $res;
        $totalInserted += $res['inserted'];
        $totalUpdated += $res['updated'];
    } catch (Throwable $e) {
        $totalFailed++;
        $results[] = [
            'ticker' => $ticker,
            'success' => false,
            'message' => $e->getMessage(),
            'inserted' => 0,
            'updated' => 0,
            'source' => null,
        ];
    }
}

stock_json([
    'success' => ($totalFailed === 0),
    'requested' => $tickers,
    'totals' => [
        'inserted' => $totalInserted,
        'updated' => $totalUpdated,
        'failed' => $totalFailed,
    ],
    'results' => $results,
]);

function stock_ingest_ticker_daily(PDO $pdo, string $ticker, string $fromYmd, string $toYmd): array {
    // Stooq symbol format: aapl.us, spy.us
    $stooqSymbol = strtolower($ticker) . '.us';
    $url = 'https://stooq.com/q/d/l/?s=' . rawurlencode($stooqSymbol) . '&i=d';

    $csv = stock_http_get($url);
    $source = 'stooq';

    if ($csv === null || stripos($csv, 'Date,Open,High,Low,Close,Volume') === false) {
        // Offline fallback: bundled seed CSVs (optional).
        $seedPath = dirname(__DIR__) . '/data/stocks_seed/' . $ticker . '.csv';
        if (is_readable($seedPath)) {
            $csv = file_get_contents($seedPath);
            $source = 'seed';
        }
    }

    if ($csv === null || trim($csv) === '') {
        // Last-resort: generate synthetic-but-plausible daily series so the prototype always runs.
        // This keeps the module demoable even in environments with restricted outbound network.
        $csv = stock_generate_synthetic_daily_csv($ticker, 320);
        $source = 'synthetic';
    }

    $lines = preg_split('/\r\n|\r|\n/', trim($csv));
    if (!$lines || count($lines) < 2) {
        throw new RuntimeException('CSV contained no data rows.');
    }

    // Ensure ticker exists in stocks table.
    $pdo->prepare('INSERT OR IGNORE INTO stocks(ticker, name) VALUES(:t, :n)')
        ->execute([':t' => $ticker, ':n' => $ticker]);

    $inserted = 0;
    $updated = 0;

    $stmt = $pdo->prepare(
        'INSERT INTO daily_prices(ticker, date, open, high, low, close, volume, source)
         VALUES(:ticker, :date, :open, :high, :low, :close, :volume, :source)
         ON CONFLICT(ticker, date) DO UPDATE SET
            open=excluded.open,
            high=excluded.high,
            low=excluded.low,
            close=excluded.close,
            volume=excluded.volume,
            source=excluded.source'
    );

    $pdo->beginTransaction();
    try {
        // Skip header
        for ($i = 1; $i < count($lines); $i++) {
            $row = str_getcsv($lines[$i], ',', '"', "\\");
            if (count($row) < 5) {
                continue;
            }
            $date = trim((string)$row[0]);
            if ($date === '' || $date < '1900-01-01') {
                continue;
            }
            // Filter date range if possible (CSV is full history).
            $ymd = str_replace('-', '', $date);
            if ($ymd < $fromYmd || $ymd > $toYmd) {
                continue;
            }

            $open = stock_float_or_null($row[1] ?? null);
            $high = stock_float_or_null($row[2] ?? null);
            $low = stock_float_or_null($row[3] ?? null);
            $close = stock_float_or_null($row[4] ?? null);
            $volume = stock_int_or_null($row[5] ?? null);

            if ($close === null) {
                continue;
            }

            $before = stock_row_exists($pdo, $ticker, $date);

            $stmt->execute([
                ':ticker' => $ticker,
                ':date' => $date,
                ':open' => $open,
                ':high' => $high,
                ':low' => $low,
                ':close' => $close,
                ':volume' => $volume,
                ':source' => $source,
            ]);

            if ($before) {
                $updated++;
            } else {
                $inserted++;
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    return [
        'ticker' => $ticker,
        'success' => true,
        'message' => 'Ingested daily prices.',
        'inserted' => $inserted,
        'updated' => $updated,
        'source' => $source,
    ];
}

function stock_http_get(string $url): ?string {
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 8,
            'header' => "User-Agent: NoiriumStockLab/1.0\r\n",
        ],
        'https' => [
            'method' => 'GET',
            'timeout' => 8,
            'header' => "User-Agent: NoiriumStockLab/1.0\r\n",
        ],
    ]);

    $data = @file_get_contents($url, false, $ctx);
    if ($data === false) {
        return null;
    }
    return $data;
}

function stock_float_or_null($v): ?float {
    if ($v === null) return null;
    $s = trim((string)$v);
    if ($s === '' || strtolower($s) === 'null') return null;
    if (!is_numeric($s)) return null;
    return (float)$s;
}

function stock_int_or_null($v): ?int {
    if ($v === null) return null;
    $s = trim((string)$v);
    if ($s === '' || strtolower($s) === 'null') return null;
    if (!is_numeric($s)) return null;
    return (int)$s;
}

function stock_row_exists(PDO $pdo, string $ticker, string $date): bool {
    static $stmt = null;
    if (!$stmt) {
        $stmt = $pdo->prepare('SELECT 1 FROM daily_prices WHERE ticker=:t AND date=:d LIMIT 1');
    }
    $stmt->execute([':t' => $ticker, ':d' => $date]);
    return (bool)$stmt->fetchColumn();
}

function stock_generate_synthetic_daily_csv(string $ticker, int $days): string {
    $days = max(60, min(800, $days));
    $seed = abs(crc32($ticker));
    mt_srand($seed);

    // Base price varies by ticker deterministically.
    $base = 50.0 + ($seed % 25000) / 100.0; // 50..300
    $price = $base;

    $out = ["Date,Open,High,Low,Close,Volume"];
    $d = new DateTimeImmutable('now');
    $count = 0;
    $maxIter = $days * 2; // allow skipping weekends

    for ($i = 0; $i < $maxIter && $count < $days; $i++) {
        $d = $d->modify('-1 day');
        $dow = (int)$d->format('N'); // 6,7 => weekend
        if ($dow >= 6) continue;

        // Random walk with mild drift and bounded volatility.
        $drift = (mt_rand(-5, 8)) / 10000.0;    // -0.05%..+0.08%
        $shock = (mt_rand(-30, 30)) / 10000.0;  // -0.30%..+0.30%
        $ret = $drift + $shock;

        $open = $price;
        $close = max(1.0, $price * (1.0 + $ret));

        $range = max(0.5, abs($close - $open) + ($price * (mt_rand(5, 35) / 10000.0)));
        $high = max($open, $close) + $range * 0.6;
        $low = min($open, $close) - $range * 0.4;
        $volume = 1000000 + ($seed % 5000000) + mt_rand(0, 2500000);

        $out[] = sprintf(
            "%s,%.4f,%.4f,%.4f,%.4f,%d",
            $d->format('Y-m-d'),
            $open,
            $high,
            $low,
            $close,
            $volume
        );

        $price = $close;
        $count++;
    }

    // Stooq CSV is newest->oldest; our insert loop doesn't assume order, but keep it similar.
    $header = array_shift($out);
    $rows = array_reverse($out);
    array_unshift($rows, $header);
    return implode("\n", $rows);
}

