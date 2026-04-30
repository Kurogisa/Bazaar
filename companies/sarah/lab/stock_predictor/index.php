<?php $currentPage = "lab"; $basePath = "../../"; ?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lab — Stock Prediction Engine</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="../../assets/styles.css">
    <style>
      .cc-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
      .cc-kpi { font-size: 1.4rem; font-weight: 700; }
      .cc-badge-bullish { background: rgba(79,122,91,.18); color: #2f2a24; border: 1px solid rgba(79,122,91,.35); }
      .cc-badge-bearish { background: rgba(184,92,92,.16); color: #2f2a24; border: 1px solid rgba(184,92,92,.35); }
      .cc-badge-neutral { background: rgba(198,161,91,.14); color: #2f2a24; border: 1px solid rgba(198,161,91,.35); }
      canvas { width: 100% !important; height: 320px !important; }
    </style>
  </head>
  <body>
    <?php require '../../navBar.php'; ?>

    <div class="container cc-container mt-5">
      <div class="cc-paper p-4 p-md-5">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-3">
          <div>
            <h1 class="mb-1">Lab — Stock Prediction Engine</h1>
            <p class="text-muted mb-0">Prototype: daily OHLC ingest → indicators → next-day prediction.</p>
          </div>
          <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="../../lab.php">Back to Lab</a>
          </div>
        </div>

        <div class="row g-3 align-items-end">
          <div class="col-12 col-md-6">
            <label for="tickerSelect" class="form-label">Stock</label>
            <select id="tickerSelect" class="form-select"></select>
            <div id="stockMeta" class="small text-muted mt-1"></div>
          </div>
          <div class="col-12 col-md-6 d-flex gap-2 justify-content-md-end">
            <button id="refreshBtn" class="btn btn-primary">Refresh chart + prediction</button>
            <button id="ingestBtn" class="btn btn-outline-primary">Update data</button>
          </div>
        </div>

        <hr class="my-4">

        <div class="row g-3">
          <div class="col-12 col-lg-8">
            <div class="card">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <div class="fw-semibold">Close price (last ~1y)</div>
                  <div id="chartStatus" class="small text-muted"></div>
                </div>
                <canvas id="priceChart"></canvas>
              </div>
            </div>
          </div>
          <div class="col-12 col-lg-4">
            <div class="card mb-3">
              <div class="card-body">
                <div class="fw-semibold mb-2">Prediction (next trading day)</div>
                <div class="d-flex align-items-center justify-content-between">
                  <div>
                    <div class="small text-muted">Predicted close</div>
                    <div id="predClose" class="cc-kpi cc-mono">—</div>
                  </div>
                  <div class="text-end">
                    <div class="small text-muted">Trend</div>
                    <div id="predTrend" class="badge cc-badge-neutral">—</div>
                  </div>
                </div>
                <div class="mt-3">
                  <div class="small text-muted">Predicted return</div>
                  <div id="predReturn" class="cc-mono">—</div>
                </div>
                <div class="mt-3 small text-muted">
                  <div><span class="fw-semibold">Model</span>: <span id="predModel" class="cc-mono">—</span></div>
                  <div><span class="fw-semibold">As of</span>: <span id="predAsOf" class="cc-mono">—</span></div>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-body">
                <div class="fw-semibold mb-2">What this prototype stores</div>
                <ul class="mb-0 small text-muted">
                  <li>Daily OHLC + volume per ticker (SQLite)</li>
                  <li>Computed prediction rows (cached per as-of date)</li>
                  <li>Simple indicators (SMA + volatility) stored with prediction</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <div id="toast" class="alert alert-secondary mt-4 d-none" role="alert"></div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js" crossorigin="anonymous"></script>
    <script>
      const api = {
        list: "../../api/stock_data.php",
        data: (ticker) => `../../api/stock_data.php?ticker=${encodeURIComponent(ticker)}&limit=260`,
        predict: (ticker) => `../../api/stock_predict.php?ticker=${encodeURIComponent(ticker)}`,
        ingest: (ticker) => ticker
          ? `../../api/stock_ingest.php?ticker=${encodeURIComponent(ticker)}`
          : `../../api/stock_ingest.php`,
      };

      const els = {
        tickerSelect: document.getElementById("tickerSelect"),
        stockMeta: document.getElementById("stockMeta"),
        refreshBtn: document.getElementById("refreshBtn"),
        ingestBtn: document.getElementById("ingestBtn"),
        chartStatus: document.getElementById("chartStatus"),
        predClose: document.getElementById("predClose"),
        predTrend: document.getElementById("predTrend"),
        predReturn: document.getElementById("predReturn"),
        predModel: document.getElementById("predModel"),
        predAsOf: document.getElementById("predAsOf"),
        toast: document.getElementById("toast"),
      };

      let chart = null;

      function showToast(msg, kind = "secondary") {
        els.toast.className = `alert alert-${kind} mt-4`;
        els.toast.textContent = msg;
        els.toast.classList.remove("d-none");
        window.clearTimeout(showToast._t);
        showToast._t = window.setTimeout(() => els.toast.classList.add("d-none"), 4500);
      }

      function formatPct(x) {
        const p = (x * 100).toFixed(2);
        return `${p}%`;
      }

      function formatUsd(x) {
        if (!Number.isFinite(x)) return "—";
        return new Intl.NumberFormat(undefined, { style: "currency", currency: "USD" }).format(x);
      }

      function badgeClass(trend) {
        if (trend === "bullish") return "cc-badge-bullish";
        if (trend === "bearish") return "cc-badge-bearish";
        return "cc-badge-neutral";
      }

      async function fetchJson(url) {
        const r = await fetch(url, { headers: { "Accept": "application/json" } });
        const j = await r.json().catch(() => ({}));
        if (!r.ok || j.success === false) {
          const msg = j.message || `Request failed (${r.status})`;
          throw new Error(msg);
        }
        return j;
      }

      function sma(values, period) {
        const out = new Array(values.length).fill(null);
        let sum = 0;
        for (let i = 0; i < values.length; i++) {
          sum += values[i];
          if (i >= period) sum -= values[i - period];
          if (i >= period - 1) out[i] = sum / period;
        }
        return out;
      }

      async function loadUniverse() {
        const j = await fetchJson(api.list);
        els.tickerSelect.innerHTML = "";
        j.stocks.forEach(s => {
          const opt = document.createElement("option");
          opt.value = s.ticker;
          opt.textContent = `${s.ticker} — ${s.name}`;
          els.tickerSelect.appendChild(opt);
        });
        return j.stocks;
      }

      async function loadChartAndPrediction() {
        const ticker = els.tickerSelect.value;
        if (!ticker) return;

        els.chartStatus.textContent = "Loading…";

        // Load chart data first so the UI updates even if prediction fails.
        let dataJ = await fetchJson(api.data(ticker));

        // If we don't have enough rows for a prediction (or even a chart),
        // auto-run ingestion for the selected ticker once.
        if ((dataJ.prices || []).length < 35) {
          els.chartStatus.textContent = "Fetching data…";
          try {
            await fetchJson(api.ingest(ticker));
            dataJ = await fetchJson(api.data(ticker));
          } catch (e) {
            // Keep going; we'll show "no data" state and a warning toast.
            showToast(`Could not update data for ${ticker}: ${e.message}`, "warning");
          }
        }

        const prices = dataJ.prices || [];
        const labels = prices.map(p => p.date);
        const closes = prices.map(p => Number(p.close));
        const sma20 = sma(closes, 20);

        els.stockMeta.textContent = `${dataJ.stock.name} • ${prices.length} points • source: ${prices[prices.length - 1]?.source || "—"}`;
        els.chartStatus.textContent = prices.length ? `Last: ${labels[labels.length - 1]}` : "No data yet";

        // Try prediction; if it fails, keep chart updated and show placeholders.
        try {
          const predJ = await fetchJson(api.predict(ticker));
          const pred = predJ.prediction;
          const pc = Number(pred.predicted_close);
          const pr = Number(pred.predicted_return);
          els.predClose.textContent = formatUsd(pc);
          els.predReturn.textContent = `${formatPct(pr)} vs last close`;
          els.predModel.textContent = pred.model;
          els.predAsOf.textContent = pred.as_of_date;
          els.predTrend.textContent = pred.trend;
          els.predTrend.className = `badge ${badgeClass(pred.trend)}`;
        } catch (e) {
          els.predClose.textContent = "—";
          els.predReturn.textContent = "—";
          els.predModel.textContent = "—";
          els.predAsOf.textContent = "—";
          els.predTrend.textContent = "—";
          els.predTrend.className = "badge cc-badge-neutral";
          showToast(`Prediction not available for ${ticker} yet: ${e.message}`, "secondary");
        }

        const ctx = document.getElementById("priceChart");
        if (chart) chart.destroy();
        chart = new Chart(ctx, {
          type: "line",
          data: {
            labels,
            datasets: [
              {
                label: "Close",
                data: closes,
                borderColor: "rgba(184,92,92,.9)",
                backgroundColor: "rgba(184,92,92,.12)",
                borderWidth: 2,
                tension: 0.15,
                pointRadius: 0,
              },
              {
                label: "SMA 20",
                data: sma20,
                borderColor: "rgba(79,122,91,.9)",
                borderWidth: 2,
                tension: 0.1,
                pointRadius: 0,
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { display: true, position: "bottom" },
              tooltip: { mode: "index", intersect: false },
            },
            interaction: { mode: "index", intersect: false },
            scales: {
              x: { ticks: { maxTicksLimit: 8 } },
              y: { ticks: { callback: (v) => `$${v}` } },
            },
          }
        });
      }

      async function ingestSelected() {
        const ticker = els.tickerSelect.value;
        els.ingestBtn.disabled = true;
        els.ingestBtn.textContent = "Updating…";
        try {
          const j = await fetchJson(api.ingest(ticker));
          showToast(`Update done for ${ticker}. Inserted ${j.totals.inserted}, updated ${j.totals.updated}.`, "success");
        } catch (e) {
          showToast(`Update failed: ${e.message}`, "warning");
        } finally {
          els.ingestBtn.disabled = false;
          els.ingestBtn.textContent = "Update data";
        }
      }

      (async function main() {
        try {
          await loadUniverse();
          // Ensure the whole prototype universe has data at least once.
          // This prevents "No data yet" when users switch tickers.
          try {
            els.chartStatus.textContent = "Preparing data…";
            await fetchJson(api.ingest());
          } catch (e) {
            // Non-fatal: per-ticker auto-ingest still exists.
            showToast(`Background update failed: ${e.message}`, "secondary");
          }
          await loadChartAndPrediction();
          els.refreshBtn.addEventListener("click", () => loadChartAndPrediction().catch(e => showToast(e.message, "warning")));
          els.ingestBtn.addEventListener("click", () => ingestSelected().then(() => loadChartAndPrediction()).catch(e => showToast(e.message, "warning")));
          els.tickerSelect.addEventListener("change", () => loadChartAndPrediction().catch(e => showToast(e.message, "warning")));
        } catch (e) {
          showToast(`Failed to initialize: ${e.message}`, "warning");
        }
      })();
    </script>
  </body>
</html>

