<?php
$companiesDir = __DIR__ . '/companies';
if (!is_dir($companiesDir)) {
    $companiesDir = __DIR__ . '/../companies';
}
$companies = [];
foreach (glob($companiesDir . '/*/company.php') as $file) {
    $data = include $file;
    if (is_array($data)) $companies[] = $data;
}
$count = count($companies);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bazaar — Student Marketplace Hub</title>

    <!-- All external links are absolute URLs — work on any server -->
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<style>
/* ============================================
   Design Tokens
   ============================================ */
:root {
    --surface:                   #f8f9fa;
    --surface-low:               #f1f4f6;
    --surface-lowest:            #ffffff;
    --surface-highest:           #dbe4e7;
    --on-surface:                #2b3437;
    --on-surface-variant:        #586064;
    --primary:                   #0057ce;
    --primary-dim:               #004cb6;
    --outline-variant:           #abb3b7;
    --swoosh: cubic-bezier(0.16, 1, 0.3, 1);
    --shadow: 0 24px 48px -12px rgba(43,52,55,0.08);
}

*, *::before, *::after { box-sizing: border-box; }

body {
    font-family: 'Inter', system-ui, sans-serif;
    background: var(--surface);
    color: var(--on-surface);
    margin: 0;
    padding-top: 72px;
    -webkit-font-smoothing: antialiased;
}

/* ============================================
   NAVBAR
   ============================================ */
.bz-nav {
    position: fixed;
    top: 0; left: 0; right: 0;
    z-index: 100;
    height: 72px;
    background: rgba(248,249,250,0.82);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(171,179,183,0.18);
    display: flex;
    align-items: center;
    padding: 0 2.5rem;
    justify-content: space-between;
}

.bz-brand {
    font-family: 'Manrope', sans-serif;
    font-size: 1.5rem;
    font-weight: 800;
    letter-spacing: -0.03em;
    color: var(--on-surface);
    text-decoration: none;
}
.bz-brand span { color: var(--primary); }

.bz-nav-links { display: flex; gap: 2.5rem; }

.bz-nav-links a {
    font-family: 'Manrope', sans-serif;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--on-surface-variant);
    text-decoration: none;
    transition: color 200ms;
}
.bz-nav-links a:hover { color: var(--primary); }
.bz-nav-links a.active {
    color: var(--primary);
    border-bottom: 2px solid var(--primary);
    padding-bottom: 2px;
}

/* ============================================
   HERO
   ============================================ */
.bz-hero {
    background: var(--surface-lowest);
    text-align: center;
    padding: 88px 1.5rem 100px;
}
.bz-hero-inner { max-width: 720px; margin: 0 auto; }

.bz-eyebrow {
    display: inline-block;
    font-family: 'Inter', sans-serif;
    font-size: 0.68rem;
    font-weight: 600;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--primary);
    margin-bottom: 1.5rem;
}

.bz-h1 {
    font-family: 'Manrope', sans-serif;
    font-size: clamp(2.8rem, 8vw, 5.5rem);
    font-weight: 800;
    line-height: 1.05;
    letter-spacing: -0.03em;
    color: var(--on-surface);
    margin: 0 0 1.5rem;
}

.bz-grad {
    background: linear-gradient(135deg, var(--primary) 0%, #588cff 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.bz-hero p {
    font-size: 1.05rem;
    color: var(--on-surface-variant);
    max-width: 500px;
    margin: 0 auto 2.5rem;
    line-height: 1.75;
}

.bz-ctas { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }

.btn-primary-bz {
    display: inline-flex;
    align-items: center;
    padding: 0.75rem 2rem;
    border-radius: 9999px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dim) 100%);
    color: #fff;
    font-family: 'Manrope', sans-serif;
    font-size: 0.875rem;
    font-weight: 700;
    text-decoration: none;
    box-shadow: 0 4px 20px rgba(0,87,206,0.25);
    transition: transform 400ms var(--swoosh), box-shadow 400ms var(--swoosh);
}
.btn-primary-bz:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 32px rgba(0,87,206,0.35);
    color: #fff;
}

.btn-ghost-bz {
    display: inline-flex;
    align-items: center;
    padding: 0.75rem 2rem;
    border-radius: 9999px;
    background: transparent;
    color: var(--on-surface);
    font-family: 'Manrope', sans-serif;
    font-size: 0.875rem;
    font-weight: 700;
    text-decoration: none;
    border: 1px solid rgba(171,179,183,0.5);
    transition: background 300ms, transform 300ms var(--swoosh);
}
.btn-ghost-bz:hover {
    background: var(--surface-low);
    transform: translateY(-2px);
    color: var(--on-surface);
}

/* ============================================
   SLIDER
   ============================================ */
.bz-slider-section {
    background: var(--surface);
    padding: 64px 0 48px;
}

.bz-slider-wrap {
    position: relative;
    max-width: 1400px;
    margin: 0 auto;
}

/* Scrollable track */
.bz-track {
    display: flex;
    gap: 2rem;
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    scroll-behavior: smooth;
    padding: 2rem 10% 3rem;
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.bz-track::-webkit-scrollbar { display: none; }

/* Each slide */
.bz-slide {
    min-width: 80%;
    flex-shrink: 0;
    scroll-snap-align: center;
}

.bz-slide-link {
    display: block;
    text-decoration: none;
    color: inherit;
}

/* Browser frame */
.bz-frame {
    background: var(--surface-lowest);
    border-radius: 0.75rem;
    overflow: hidden;
    box-shadow: var(--shadow);
    transition: transform 600ms var(--swoosh), box-shadow 600ms var(--swoosh);
}
.bz-slide-link:hover .bz-frame {
    transform: translateY(-8px);
    box-shadow: 0 40px 80px -12px rgba(43,52,55,0.14);
}

.bz-frame-bar {
    background: var(--surface-highest);
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 8px;
    border-bottom: 1px solid rgba(171,179,183,0.12);
}
.bz-dots-row { display: flex; gap: 6px; flex-shrink: 0; }
.bz-dot-circle {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: rgba(171,179,183,0.4);
    display: inline-block;
}
.bz-url {
    flex: 1;
    background: var(--surface-low);
    border-radius: 0.375rem;
    padding: 0 10px;
    height: 24px;
    display: flex;
    align-items: center;
    font-size: 0.68rem;
    color: var(--on-surface-variant);
    font-family: monospace;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.bz-url-icon { font-size: 0.7rem; color: var(--outline-variant); flex-shrink: 0; }

/* Preview area */
.bz-preview {
    position: relative;
    aspect-ratio: 16 / 9;
    overflow: hidden;
    background: var(--surface-low);
    line-height: 0;
}
.bz-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: top center;
    display: block;
}
.bz-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,87,206,0);
    transition: background 600ms var(--swoosh);
    pointer-events: none;
}
.bz-slide-link:hover .bz-overlay { background: rgba(0,87,206,0.05); }

/* Fallback */
.bz-fallback {
    position: absolute;
    inset: 0;
    display: none;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 14px;
}
.bz-preview.no-img img          { display: none; }
.bz-preview.no-img .bz-fallback { display: flex; }

/* Info below frame */
.bz-info {
    margin-top: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 1rem;
}
.bz-name {
    font-family: 'Manrope', sans-serif;
    font-size: 2rem;
    font-weight: 800;
    letter-spacing: -0.03em;
    color: var(--on-surface);
    margin: 0 0 0.4rem;
    line-height: 1.1;
}
.bz-tagline {
    font-family: 'Inter', sans-serif;
    font-size: 0.9rem;
    color: var(--on-surface-variant);
    margin: 0 0 1rem;
}
.bz-tags { display: flex; flex-wrap: wrap; gap: 6px; }
.bz-tag {
    font-family: 'Inter', sans-serif;
    font-size: 0.68rem;
    font-weight: 500;
    padding: 3px 10px;
    border-radius: 9999px;
}
.bz-arrow-icon {
    font-size: 2.25rem;
    color: var(--primary);
    opacity: 0;
    transform: translateX(-1rem);
    transition: opacity 600ms var(--swoosh), transform 600ms var(--swoosh);
    flex-shrink: 0;
}
.bz-slide-link:hover .bz-arrow-icon { opacity: 1; transform: translateX(0); }

/* Glass arrows */
.bz-arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 20;
    width: 3.5rem;
    height: 3.5rem;
    border-radius: 50%;
    background: rgba(248,249,250,0.82);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: none;
    color: var(--primary);
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 8px 32px rgba(43,52,55,0.12);
    transition: transform 300ms var(--swoosh), box-shadow 300ms var(--swoosh);
    padding: 0;
}
.bz-arrow:hover { transform: translateY(-50%) scale(1.1); box-shadow: 0 12px 40px rgba(43,52,55,0.2); }
.bz-prev { left: 1.5rem; }
.bz-next { right: 1.5rem; }

/* Pagination dots */
.bz-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.625rem;
    padding: 0.5rem 1rem 1rem;
}
.bz-pip {
    height: 8px;
    width: 8px;
    border-radius: 9999px;
    background: rgba(171,179,183,0.35);
    border: none;
    cursor: pointer;
    padding: 0;
    transition: all 600ms var(--swoosh);
}
.bz-pip:hover { background: rgba(171,179,183,0.6); }
.bz-pip.active { width: 32px; background: var(--primary); }

/* ============================================
   ABOUT
   ============================================ */
.bz-about {
    background: var(--surface-low);
    padding: 96px 2rem;
}
.bz-about-inner {
    max-width: 1100px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 5rem;
    align-items: center;
}
.bz-section-title {
    font-family: 'Manrope', sans-serif;
    font-size: 2.4rem;
    font-weight: 800;
    letter-spacing: -0.03em;
    color: var(--on-surface);
    margin: 0.75rem 0 1.25rem;
    line-height: 1.1;
}
.bz-about p {
    font-size: 0.95rem;
    color: var(--on-surface-variant);
    line-height: 1.75;
    margin-bottom: 1rem;
}
.bz-co-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
}
.bz-co-card {
    background: var(--surface-lowest);
    border-radius: 0.75rem;
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
    color: inherit;
    box-shadow: 0 2px 10px rgba(43,52,55,0.05);
    transition: transform 400ms var(--swoosh), box-shadow 400ms var(--swoosh);
}
.bz-co-card:hover { transform: translateY(-3px); box-shadow: var(--shadow); color: inherit; }

.bz-co-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}
.bz-co-name { font-family: 'Manrope', sans-serif; font-weight: 700; font-size: 0.875rem; }
.bz-co-owner { font-family: 'Inter', sans-serif; font-size: 0.7rem; color: var(--on-surface-variant); margin-top: 1px; }
.bz-co-arr { color: var(--outline-variant); font-size: 0.85rem; flex-shrink: 0; margin-left: auto; }

/* ============================================
   FOOTER
   ============================================ */
.bz-footer {
    background: var(--surface-lowest);
    padding: 3rem 2rem;
    text-align: center;
    border-top: 1px solid rgba(171,179,183,0.18);
}
.bz-footer-inner { max-width: 1100px; margin: 0 auto; }
.bz-footer-links {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin-bottom: 0.75rem;
}
.bz-footer-links a {
    font-family: 'Inter', sans-serif;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--on-surface-variant);
    text-decoration: none;
    transition: color 200ms;
}
.bz-footer-links a:hover { color: var(--primary); }
.bz-sep { color: var(--outline-variant); font-size: 0.7rem; }
.bz-copy { font-family: 'Inter', sans-serif; font-size: 0.78rem; color: var(--outline-variant); margin: 0; }

/* ============================================
   RESPONSIVE
   ============================================ */
@media (max-width: 900px) {
    .bz-about-inner { grid-template-columns: 1fr; gap: 3rem; }
}
@media (max-width: 768px) {
    body { padding-top: 64px; }
    .bz-nav { height: 64px; padding: 0 1.25rem; }
    .bz-hero { padding: 64px 1.25rem 80px; }
    .bz-slide { min-width: 88%; }
    .bz-track { padding: 2rem 6%; gap: 1.25rem; }
    .bz-arrow { width: 2.75rem; height: 2.75rem; font-size: 1rem; }
    .bz-prev { left: 0.5rem; }
    .bz-next { right: 0.5rem; }
    .bz-name { font-size: 1.5rem; }
    .bz-about { padding: 64px 1.25rem; }
}
@media (max-width: 480px) {
    .bz-slide { min-width: 92%; }
    .bz-co-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="bz-nav">
    <a class="bz-brand" href="index.php">Bazaar<span>.</span></a>
    <div class="bz-nav-links">
        <a href="index.php" class="active">Companies</a>
        <a href="dashboard.php">Dashboard</a>
    </div>
</nav>

<!-- HERO -->
<section class="bz-hero">
    <div class="bz-hero-inner">
        <span class="bz-eyebrow">Curated Work &nbsp;·&nbsp; <?= $count ?> Companies &nbsp;·&nbsp; One Hub</span>
        <h1 class="bz-h1">The Student<br><span class="bz-grad">Marketplace Bazaar</span></h1>
        <p>Four teams. Four products. Browse live previews and jump straight to any company's site.</p>
        <div class="bz-ctas">
            <a href="#companies" class="btn-primary-bz">
                Browse Companies <i class="bi bi-arrow-down" style="margin-left:8px"></i>
            </a>
            <a href="dashboard.php" class="btn-ghost-bz">View Dashboard</a>
        </div>
    </div>
</section>

<!-- SLIDER -->
<section class="bz-slider-section" id="companies">
    <div class="bz-slider-wrap">

        <button class="bz-arrow bz-prev" id="arrowPrev" aria-label="Previous">
            <i class="bi bi-chevron-left"></i>
        </button>
        <button class="bz-arrow bz-next" id="arrowNext" aria-label="Next">
            <i class="bi bi-chevron-right"></i>
        </button>

        <div class="bz-track" id="sliderTrack">
            <?php foreach ($companies as $i => $co):
                $screenshot = 'https://image.thum.io/get/width/1280/crop/800/' . $co['url'];
            ?>
            <div class="bz-slide" id="slide-<?= $i ?>">
                <a class="bz-slide-link"
                   href="<?= htmlspecialchars($co['url']) ?>"
                   target="_blank"
                   rel="noopener noreferrer">

                    <div class="bz-frame">
                        <div class="bz-frame-bar">
                            <div class="bz-dots-row">
                                <span class="bz-dot-circle"></span>
                                <span class="bz-dot-circle"></span>
                                <span class="bz-dot-circle"></span>
                            </div>
                            <div class="bz-url"><?= htmlspecialchars($co['url']) ?></div>
                            <i class="bi bi-box-arrow-up-right bz-url-icon"></i>
                        </div>

                        <div class="bz-preview" id="pw-<?= $i ?>">
                            <img src="<?= htmlspecialchars($screenshot) ?>"
                                 alt="<?= htmlspecialchars($co['name']) ?> preview"
                                 loading="<?= $i === 0 ? 'eager' : 'lazy' ?>"
                                 onerror="document.getElementById('pw-<?= $i ?>').classList.add('no-img')">
                            <div class="bz-fallback"
                                 style="background:linear-gradient(135deg,<?= $co['color'] ?>22,<?= $co['color'] ?>55)">
                                <i class="bi <?= htmlspecialchars($co['icon']) ?>"
                                   style="font-size:4rem;color:<?= $co['color'] ?>"></i>
                                <div style="font-family:'Manrope',sans-serif;font-weight:700;font-size:1.1rem;color:#2b3437">
                                    <?= htmlspecialchars($co['name']) ?>
                                </div>
                                <small style="color:#586064">Click to visit the site</small>
                            </div>
                            <div class="bz-overlay"></div>
                        </div>
                    </div>

                    <div class="bz-info">
                        <div>
                            <h3 class="bz-name"><?= htmlspecialchars($co['name']) ?></h3>
                            <p class="bz-tagline"><?= htmlspecialchars($co['tagline']) ?></p>
                            <div class="bz-tags">
                                <?php foreach ($co['services'] as $svc): ?>
                                <span class="bz-tag"
                                      style="color:<?= $co['color'] ?>;background:<?= $co['color'] ?>18">
                                    <?= htmlspecialchars($svc) ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <i class="bi bi-arrow-up-right bz-arrow-icon"></i>
                    </div>

                </a>
            </div>
            <?php endforeach; ?>
        </div>

    </div>

    <div class="bz-pagination" id="pagination">
        <?php for ($i = 0; $i < $count; $i++): ?>
        <button class="bz-pip <?= $i === 0 ? 'active' : '' ?>"
                data-idx="<?= $i ?>"
                aria-label="Slide <?= $i + 1 ?>"></button>
        <?php endfor; ?>
    </div>
</section>

<!-- ABOUT -->
<section class="bz-about">
    <div class="bz-about-inner">
        <div>
            <span class="bz-eyebrow">About the Project</span>
            <h2 class="bz-section-title">What is Bazaar?</h2>
            <p>Bazaar is a shared hub built collaboratively by a group of four students, each of whom designed and hosted their own company website independently.</p>
            <p>Browse live previews of every company, read about what they offer, and jump directly to their site. The dashboard aggregates all users across all companies.</p>
            <a href="dashboard.php" class="btn-primary-bz" style="margin-top:1rem">
                <i class="bi bi-speedometer2" style="margin-right:8px"></i>View Aggregated Dashboard
            </a>
        </div>
        <div class="bz-co-grid">
            <?php foreach ($companies as $co): ?>
            <a href="<?= htmlspecialchars($co['url']) ?>"
               target="_blank"
               class="bz-co-card">
                <div class="bz-co-icon"
                     style="background:<?= $co['bg_color'] ?>;color:<?= $co['color'] ?>">
                    <i class="bi <?= htmlspecialchars($co['icon']) ?>"></i>
                </div>
                <div>
                    <div class="bz-co-name"><?= htmlspecialchars($co['name']) ?></div>
                    <div class="bz-co-owner"><?= htmlspecialchars($co['owner']) ?></div>
                </div>
                <i class="bi bi-arrow-up-right bz-co-arr"></i>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="bz-footer">
    <div class="bz-footer-inner">
        <div class="bz-footer-links">
            <?php foreach ($companies as $i => $co): ?>
            <a href="<?= htmlspecialchars($co['url']) ?>" target="_blank">
                <?= htmlspecialchars($co['name']) ?>
            </a>
            <?php if ($i < $count - 1): ?>
            <span class="bz-sep">·</span>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <p class="bz-copy">&copy; <?= date('Y') ?> Bazaar — A collaborative student project.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    const track  = document.getElementById('sliderTrack');
    const slides = Array.from(document.querySelectorAll('.bz-slide'));
    const pips   = Array.from(document.querySelectorAll('.bz-pip'));
    let current  = 0;

    function goTo(n) {
        n = Math.max(0, Math.min(slides.length - 1, n));
        slides[n].scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
    }

    function setActive(n) {
        pips.forEach((p, i) => p.classList.toggle('active', i === n));
        current = n;
    }

    const obs = new IntersectionObserver(entries => {
        entries.forEach(e => {
            if (e.isIntersecting && e.intersectionRatio >= 0.5)
                setActive(slides.indexOf(e.target));
        });
    }, { root: track, threshold: 0.5 });

    slides.forEach(s => obs.observe(s));

    document.getElementById('arrowPrev').addEventListener('click', () => goTo(current - 1));
    document.getElementById('arrowNext').addEventListener('click', () => goTo(current + 1));
    pips.forEach((p, i) => p.addEventListener('click', () => goTo(i)));

    document.addEventListener('keydown', e => {
        if (e.key === 'ArrowLeft')  goTo(current - 1);
        if (e.key === 'ArrowRight') goTo(current + 1);
    });

    let tx = 0;
    track.addEventListener('touchstart', e => { tx = e.touches[0].clientX; }, { passive: true });
    track.addEventListener('touchend',   e => {
        const dx = e.changedTouches[0].clientX - tx;
        if (Math.abs(dx) > 48) goTo(dx < 0 ? current + 1 : current - 1);
    });
}());
</script>
</body>
</html>
