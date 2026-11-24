<section class="hero">
    <div class="hero-orb hero-orb-one"></div>
    <div class="hero-orb hero-orb-two"></div>
    <div class="container hero-grid">
        <div class="hero-copy">
            <div class="hero-label"><span class="pulse-dot"></span> A better way to show up</div>
            <h1>One generous act.<br><em>A lifetime of hope.</em></h1>
            <p class="hero-lede">When every minute matters, the right connection changes everything. DonorNexa brings eligible donors and verified hospitals together with care, clarity, and trust.</p>
            <div class="hero-actions">
                <a class="button button-light" href="<?= e(url('register')) ?>">Become a donor <span aria-hidden="true">→</span></a>
                <a class="button button-quiet" href="<?= e(url('emergencies')) ?>">View active requests</a>
            </div>
            <div class="trust-row">
                <div class="trust-avatars" aria-hidden="true"><span>+</span><span>♥</span><span>+</span></div>
                <p><strong>Care, without the noise.</strong><br>Verified requests. Private by design.</p>
            </div>
        </div>
        <div class="hero-visual" aria-label="Illustration of the donor and hospital connection">
            <div class="hero-visual-glow"></div>
            <div class="hero-badge"><span class="badge-icon">✳</span><span><strong>Every match matters</strong><small>Built around donor wellbeing</small></span></div>
            <div class="hero-art">
                <div class="art-ring art-ring-one"></div>
                <div class="art-ring art-ring-two"></div>
                <div class="art-cross">+</div>
                <div class="art-drop">
                    <svg viewBox="0 0 180 220" role="img" aria-label="Blood drop symbol"><path d="M90 8C73 37 29 82 29 132a61 61 0 0 0 122 0C151 82 107 37 90 8Z"/><path class="drop-plus" d="M76 118h10v-10h8v10h10v8H94v10h-8v-10H76z"/></svg>
                </div>
                <div class="art-spark art-spark-a">✳</div><div class="art-spark art-spark-b">✳</div>
                <div class="hero-card-float"><span class="float-pulse"></span><span><strong>Hope is moving</strong><small>One community. One connection.</small></span></div>
            </div>
            <div class="hero-caption"><span class="caption-line"></span><span>CONNECTED BY CARE · PROTECTED BY TRUST</span></div>
        </div>
    </div>
    <div class="hero-bottom container">
        <span>Serving communities across Pakistan</span>
        <span class="scroll-hint"><span class="scroll-line"></span> Scroll to discover</span>
    </div>
</section>

<section class="stats-strip" aria-label="Live DonorNexa statistics" data-live-stats="<?= e(APP_URL) ?>/index.php?api=stats">
    <div class="container stats-grid">
        <div class="stat-intro"><span class="live-indicator"></span><span>Hope, in motion</span><small>Live community impact</small></div>
        <div class="stat-item"><strong data-stat="active_requests">—</strong><span>Active requests</span></div>
        <div class="stat-item"><strong data-stat="registered_donors">—</strong><span>Generous donors</span></div>
        <div class="stat-item"><strong data-stat="units_fulfilled">—</strong><span>Units fulfilled</span></div>
    </div>
</section>

<section class="section section-board" id="active-board">
    <div class="container">
        <div class="section-heading">
            <div><span class="eyebrow">A little closer to home</span><h2>Hope is needed <em>right now.</em></h2></div>
            <a class="text-link" href="<?= e(url('emergencies')) ?>">See all emergencies <span aria-hidden="true">↗</span></a>
        </div>
        <div class="board-toolbar">
            <p>Verified requests near you. No names, no numbers — just a chance to help.</p>
            <label class="filter-label" for="home-city-filter">Your city
                <select id="home-city-filter" data-emergency-filter>
                    <option value="">All cities</option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?= e($city['id']) ?>"><?= e($city['city_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <div class="emergency-grid" data-emergency-board data-api="<?= e(APP_URL) ?>/index.php?api=emergencies">
            <div class="loading-card"><span class="spinner"></span>Finding verified requests…</div>
        </div>
    </div>
</section>

<section class="section section-how">
    <div class="container how-grid">
        <div class="how-intro">
            <span class="eyebrow">A kinder way to coordinate</span>
            <h2>Fast when it matters.<br><em>Thoughtful by design.</em></h2>
            <p>Every connection is made with donor wellbeing and patient dignity in mind. No public phone numbers. No repeated calls after you're due for a rest.</p>
            <a class="text-link" href="<?= e(url('register')) ?>">Be part of the good <span aria-hidden="true">→</span></a>
        </div>
        <div class="how-steps">
            <article class="how-step"><span class="step-number">01</span><span class="step-symbol">⌕</span><div><h3>Find a real need</h3><p>Verified hospitals post clear requests, with urgency and a time window.</p></div></article>
            <article class="how-step"><span class="step-number">02</span><span class="step-symbol">✳</span><div><h3>Get thoughtfully matched</h3><p>Compatibility, eligibility, distance and donor response history guide every match.</p></div></article>
            <article class="how-step"><span class="step-number">03</span><span class="step-symbol">♡</span><div><h3>Choose to make a difference</h3><p>Your details stay private until you accept. Your choice is always yours.</p></div></article>
        </div>
    </div>
</section>

<section class="section section-cta">
    <div class="container cta-panel">
        <div><span class="eyebrow">Goodness starts with one person</span><h2>Be someone's reason<br>to hope again.</h2><p>Join a community that gives with heart — and protects one another along the way.</p></div>
        <div class="cta-actions"><a class="button button-light" href="<?= e(url('register')) ?>">Join as a donor <span aria-hidden="true">→</span></a><a href="<?= e(url('register', ['role' => 'hospital'])) ?>" class="cta-secondary">Register a hospital</a></div>
        <span class="cta-decoration" aria-hidden="true">+</span>
    </div>
</section>
