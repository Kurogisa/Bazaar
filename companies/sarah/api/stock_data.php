<?php
declare(strict_types=1);

require_once __DIR__ . '/stock_bootstrap.php';

/**
 * api/stock_data.php
 *
 * GET /api/stock_data.php
 *   -> list available stocks
 *
 * GET /api/stock_data.php?ticker=AAPL&limit=260
 *   -> price series for a ticker
 */

$pdo = stock_db();

$ticker = isset($_GET['ticker']) ? strtoupper(trim((string)$_GET['ticker'])) : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 260;
$limit = max(20, min(2000, $limit));

if ($ticker === '') {
    $stocks = $pdo->query('SELECT ticker, name, currency, exchange FROM stocks ORDER BY ticker')->fetchAll();
    stock_json([
        'success' => true,
        'count' => count($stocks),
        'stocks' => $stocks,
    ]);
    exit;
}

$s = $pdo->prepare('SELECT ticker, name, currency, exchange FROM stocks WHERE ticker = :t LIMIT 1');
$s->execute([':t' => $ticker]);
$stock = $s->fetch();
if (!$stock) {
    stock_json(['success' => false, 'message' => 'Unknown ticker.'], 404);
    exit;
}

$q = $pdo->prepare(
    'SELECT date, open, high, low, close, volume, source
     FROM daily_prices
     WHERE ticker = :t
     ORDER BY date DESC
     LIMIT :lim'
);
$q->bindValue(':t', $ticker, PDO::PARAM_STR);
$q->bindValue(':lim', $limit, PDO::PARAM_INT);
$q->execute();
$rowsDesc = $q->fetchAll();
$rows = array_reverse($rowsDesc); // oldest -> newest for charting

stock_json([
    'success' => true,
    'stock' => $stock,
    'limit' => $limit,
    'count' => count($rows),
    'prices' => $rows,
]);

