<?php
declare(strict_types=1);

require_once __DIR__ . '/stock_bootstrap.php';

/**
 * api/stock_predict.php
 *
 * GET /api/stock_predict.php?ticker=AAPL
 * - Returns latest prediction (and computes/stores it if missing).
 *
 * Prediction approach (prototype):
 * - Features: last close, SMA(5), SMA(20), return_1d, vol_20d (std dev of returns)
 * - Model: simple linear regression on last 30 closes vs time index
 * - Output: next-day predicted close + trend label
 */

$pdo = stock_db();

$ticker = isset($_GET['ticker']) ? strtoupper(trim((string)$_GET['ticker'])) : '';
if ($ticker === '') {
    stock_json(['success' => false, 'message' => 'ticker is required'], 400);
    exit;
}

$model = 'linreg_30d_v1';
$horizon = 1;

// Need enough history.
$hist = $pdo->prepare(
    'SELECT date, close
     FROM daily_prices
     WHERE ticker = :t
     ORDER BY date DESC
     LIMIT 90'
);
$hist->execute([':t' => $ticker]);
$rowsDesc = $hist->fetchAll();
if (count($rowsDesc) < 35) {
    stock_json([
        'success' => false,
        'message' => 'Not enough historical data. Run ingestion first.',
        'ticker' => $ticker,
        'need_min_rows' => 35,
        'have_rows' => count($rowsDesc),
    ], 400);
    exit;
}

$rows = array_reverse($rowsDesc); // oldest -> newest
$dates = array_map(fn($r) => (string)$r['date'], $rows);
$closes = array_map(fn($r) => (float)$r['close'], $rows);

$asOfDate = end($dates);
$lastClose = end($closes);

// Try cached prediction first.
$cached = $pdo->prepare(
    'SELECT ticker, as_of_date, horizon_days, predicted_close, predicted_return, trend, model, features_json, created_at
     FROM predictions
     WHERE ticker=:t AND as_of_date=:d AND horizon_days=:h AND model=:m
     LIMIT 1'
);
$cached->execute([':t' => $ticker, ':d' => $asOfDate, ':h' => $horizon, ':m' => $model]);
$predRow = $cached->fetch();
if ($predRow) {
    stock_json(['success' => true, 'prediction' => $predRow]);
    exit;
}

// Compute features on the most recent window.
$sma5 = stock_sma(array_slice($closes, -5));
$sma20 = stock_sma(array_slice($closes, -20));
$ret1d = stock_return_1d($closes);
$vol20 = stock_volatility(array_slice($closes, -21)); // 20 returns

// Linear regression on last 30 closes: y = a + b*x, predict x = 30 (0-indexed).
$window = array_slice($closes, -30);
$lin = stock_linreg($window);
$predictedClose = max(0.01, $lin['a'] + $lin['b'] * 30.0);

$predictedReturn = ($predictedClose / $lastClose) - 1.0;
$trend = 'neutral';
if ($predictedReturn > 0.005) $trend = 'bullish';
if ($predictedReturn < -0.005) $trend = 'bearish';

$features = [
    'last_close' => $lastClose,
    'sma_5' => $sma5,
    'sma_20' => $sma20,
    'return_1d' => $ret1d,
    'vol_20d' => $vol20,
    'linreg_a' => $lin['a'],
    'linreg_b' => $lin['b'],
    'n' => count($window),
];

$ins = $pdo->prepare(
    'INSERT INTO predictions(ticker, as_of_date, horizon_days, predicted_close, predicted_return, trend, model, features_json)
     VALUES(:t, :d, :h, :pc, :pr, :tr, :m, :fj)'
);
$ins->execute([
    ':t' => $ticker,
    ':d' => $asOfDate,
    ':h' => $horizon,
    ':pc' => $predictedClose,
    ':pr' => $predictedReturn,
    ':tr' => $trend,
    ':m' => $model,
    ':fj' => json_encode($features, JSON_UNESCAPED_SLASHES),
]);

$cached->execute([':t' => $ticker, ':d' => $asOfDate, ':h' => $horizon, ':m' => $model]);
$predRow = $cached->fetch();

stock_json([
    'success' => true,
    'prediction' => $predRow,
]);

function stock_sma(array $values): float {
    $n = count($values);
    if ($n === 0) return 0.0;
    return array_sum($values) / $n;
}

function stock_return_1d(array $closes): float {
    $n = count($closes);
    if ($n < 2) return 0.0;
    $prev = (float)$closes[$n - 2];
    $last = (float)$closes[$n - 1];
    if ($prev <= 0) return 0.0;
    return ($last / $prev) - 1.0;
}

function stock_volatility(array $closesWindow): float {
    // std dev of daily returns within the window (expects >=2 closes)
    $n = count($closesWindow);
    if ($n < 3) return 0.0;
    $rets = [];
    for ($i = 1; $i < $n; $i++) {
        $prev = (float)$closesWindow[$i - 1];
        $cur = (float)$closesWindow[$i];
        if ($prev > 0) {
            $rets[] = ($cur / $prev) - 1.0;
        }
    }
    $m = count($rets);
    if ($m < 2) return 0.0;
    $mean = array_sum($rets) / $m;
    $var = 0.0;
    foreach ($rets as $r) {
        $var += ($r - $mean) * ($r - $mean);
    }
    $var = $var / ($m - 1);
    return sqrt($var);
}

function stock_linreg(array $y): array {
    // x = 0..n-1
    $n = count($y);
    if ($n === 0) return ['a' => 0.0, 'b' => 0.0];
    $sumX = 0.0;
    $sumY = 0.0;
    $sumXX = 0.0;
    $sumXY = 0.0;
    for ($i = 0; $i < $n; $i++) {
        $x = (float)$i;
        $yy = (float)$y[$i];
        $sumX += $x;
        $sumY += $yy;
        $sumXX += $x * $x;
        $sumXY += $x * $yy;
    }
    $den = ($n * $sumXX) - ($sumX * $sumX);
    if (abs($den) < 1e-12) {
        return ['a' => $sumY / $n, 'b' => 0.0];
    }
    $b = (($n * $sumXY) - ($sumX * $sumY)) / $den;
    $a = ($sumY - ($b * $sumX)) / $n;
    return ['a' => $a, 'b' => $b];
}

