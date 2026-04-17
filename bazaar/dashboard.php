<?php
/**
 * Bazaar Dashboard
 * Fetches user lists from all companies via cURL and displays them together.
 */

// ---- Resolve companies directory ----
$companiesDir = __DIR__ . '/companies';
if (!is_dir($companiesDir)) {
    $companiesDir = __DIR__ . '/../companies';
}

// ---- Load company definitions ----
$companies = [];
foreach (glob($companiesDir . '/*/company.php') as $file) {
    $data = include $file;
    if (is_array($data)) $companies[] = $data;
}

// ---- cURL fetch helper ----
function fetchUsers(string $url): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'Bazaar-Dashboard/1.0',
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ]);
    $response = curl_exec($ch);
    $error    = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error)            return ['error' => "cURL: $error",         'users' => []];
    if ($httpCode !== 200) return ['error' => "HTTP $httpCode",        'users' => []];
    $data = json_decode($response, true);
    if (!$data)            return ['error' => 'Invalid JSON response', 'users' => []];

    return ['error' => null, 'users' => $data['users'] ?? []];
}

// ---- Fetch all ----
$results    = [];
$totalUsers = 0;
foreach ($companies as $co) {
    $data = fetchUsers($co['api_url']);
    $results[] = [
        'company' => $co,
        'users'   => $data['users'],
        'error'   => $data['error'],
    ];
    $totalUsers += count($data['users']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#212529">
    <title>Dashboard | Bazaar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bazaar-dashboard">

<!-- ===== NAVBAR ===== -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top border-bottom border-secondary border-opacity-25">
    <div class="container px-3 px-sm-4">
        <a class="navbar-brand" href="index.php">Bazaar<span class="dot">.</span></a>
        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto gap-1">
                <li class="nav-item"><a class="nav-link" href="index.php">Companies</a></li>
                <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- ===== PAGE HERO ===== -->
<section class="dash-hero text-white">
    <div class="container px-3 px-sm-4 py-4 py-md-5 text-center">
        <h1 class="mb-2 dash-hero-title">Aggregated Dashboard</h1>
        <p class="dash-hero-lead mb-0 mx-auto" style="max-width:36rem;color:#94a3b8">User data fetched live from all companies via cURL.</p>
    </div>
</section>

<!-- ===== SUMMARY STATS ===== -->
<div class="dash-stats-bar border-bottom py-3 py-md-4">
    <div class="container px-3 px-sm-4">
        <div class="row text-center g-3">
            <div class="col-6 col-md-3">
                <div class="stat-number text-primary"><?= $totalUsers ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <?php foreach ($results as $r): ?>
            <div class="col-6 col-md-3">
                <div class="stat-number" style="color:<?= htmlspecialchars($r['company']['color']) ?>">
                    <?= $r['error'] ? '—' : count($r['users']) ?>
                </div>
                <div class="stat-label"><?= htmlspecialchars($r['company']['name']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ===== USER TABLES ===== -->
<section class="section dash-section">
    <div class="container px-3 px-sm-4">

        <?php foreach ($results as $r):
            $co = $r['company']; ?>

        <div class="mb-5">
            <!-- Company heading -->
            <div class="company-group-header">
                <div class="company-group-icon" style="background:<?= $co['bg_color'] ?>;color:<?= $co['color'] ?>">
                    <i class="bi <?= $co['icon'] ?>"></i>
                </div>
                <div class="company-group-meta min-w-0">
                    <strong class="d-block"><?= htmlspecialchars($co['name']) ?></strong>
                    <span class="text-muted small d-block mt-1">
                        <i class="bi bi-cloud-arrow-down-fill me-1"></i>cURL from
                        <a href="<?= htmlspecialchars($co['api_url']) ?>" target="_blank" rel="noopener noreferrer" class="text-muted text-break">
                            <?= htmlspecialchars(parse_url($co['api_url'], PHP_URL_HOST) ?: $co['api_url']) ?>
                        </a>
                    </span>
                </div>
                <?php if ($r['error']): ?>
                <span class="badge text-bg-danger company-group-badge text-wrap text-start">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i><?= htmlspecialchars($r['error']) ?>
                </span>
                <?php else: ?>
                <span class="badge company-group-badge align-self-center" style="background:<?= $co['color'] ?>">
                    <?= count($r['users']) ?> users
                </span>
                <?php endif; ?>
            </div>

            <?php if (!empty($r['users'])): ?>
            <div class="card border-0 shadow-sm dash-user-card" style="border-radius:12px;overflow:hidden;border-top:3px solid <?= $co['color'] ?> !important;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 dash-user-table">
                        <thead style="background:#f8fafc;font-size:.8rem;text-transform:uppercase;color:#64748b;letter-spacing:.5px">
                            <tr>
                                <th class="ps-3 ps-md-4 py-3">Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Last Login</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($r['users'] as $u): ?>
                            <tr>
                                <td class="ps-3 ps-md-4 fw-semibold text-break"><?= htmlspecialchars($u['name']       ?? '—') ?></td>
                                <td class="text-muted small text-break"><?= htmlspecialchars($u['email']      ?? '—') ?></td>
                                <td>
                                    <?php if (!empty($u['role'])): ?>
                                    <span class="badge fw-normal" style="background:<?= $co['bg_color'] ?>;color:<?= $co['color'] ?>">
                                        <?= htmlspecialchars($u['role']) ?>
                                    </span>
                                    <?php else: ?><span class="text-muted small">—</span><?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($u['status'])): ?>
                                    <span class="status-dot <?= $u['status'] === 'Online' ? 'online' : 'offline' ?> me-1"></span>
                                    <?= htmlspecialchars($u['status']) ?>
                                    <?php else: ?><span class="text-muted small">—</span><?php endif; ?>
                                </td>
                                <td class="text-muted small text-nowrap"><?= htmlspecialchars($u['last_login'] ?? '—') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php elseif ($r['error']): ?>
            <div class="alert alert-danger d-flex flex-column flex-sm-row align-items-sm-center gap-2 mb-0">
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <i class="bi bi-wifi-off fs-5"></i>
                    <span>Could not reach <strong><?= htmlspecialchars($co['name']) ?></strong>.</span>
                </div>
                <div class="small"><span class="text-danger-emphasis">Endpoint:</span> <code class="d-inline-block text-break"><?= htmlspecialchars($co['api_url']) ?></code></div>
            </div>

            <?php else: ?>
            <div class="alert alert-secondary mb-0">No users returned from <?= htmlspecialchars($co['name']) ?>.</div>
            <?php endif; ?>
        </div>

        <?php endforeach; ?>
    </div>
</section>

<footer class="dash-footer">
    <div class="container px-3 px-sm-4">
        <a href="index.php"><i class="bi bi-arrow-left me-1"></i>Back to Companies</a>
        <p class="mb-0 mt-2">&copy; <?= date('Y') ?> Bazaar</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
