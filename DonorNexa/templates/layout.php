<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f8f7f4">
    <meta name="description" content="DonorNexa connects verified hospitals with eligible blood donors across Pakistan.">
    <title><?= e($pageTitle ?? 'Connecting donors. Bridging lives.') ?> · DonorNexa</title>
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/app.css">
    <script defer src="<?= e(APP_URL) ?>/assets/js/app.js"></script>
</head>
<body>
<a class="skip-link" href="#main-content">Skip to content</a>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= e(url('home')) ?>" aria-label="DonorNexa home">
            <span class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 40 40"><path d="M20 3.5c-3.8 6.3-11.4 14.4-11.4 22.1A11.4 11.4 0 0 0 20 37a11.4 11.4 0 0 0 11.4-11.4C31.4 17.9 23.8 9.8 20 3.5Z"/><path d="M14.4 23.4h4v-4h3.2v4h4v3.2h-4v4h-3.2v-4h-4z"/></svg>
            </span>
            <span class="brand-type">donor<span>nexa</span><small>CONNECTING HOPE</small></span>
        </a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-nav">
            <span></span><span></span><span class="sr-only">Toggle navigation</span>
        </button>
        <nav class="primary-nav" id="primary-nav" aria-label="Main navigation">
            <a href="<?= e(url('home')) ?>">Home</a>
            <a href="<?= e(url('emergencies')) ?>">Emergency board</a>
            <?php if ($user): ?>
                <?php if ($user['role'] === 'admin'): ?>
                    <a href="<?= e(url('admin')) ?>">Dashboard</a>
                    <a href="<?= e(url('users')) ?>">People</a>
                    <a href="<?= e(url('admin_requests')) ?>">Requests</a>
                    <a href="<?= e(url('cities')) ?>">Cities</a>
                    <a href="<?= e(url('audit')) ?>">Audit trail</a>
                <?php else: ?>
                    <a href="<?= e(url('dashboard')) ?>">My dashboard</a>
                <?php endif; ?>
                <form action="<?= e(url('logout')) ?>" method="post" class="nav-logout">
                    <?= csrf_field() ?>
                    <button class="button button-outline button-small" type="submit">Sign out</button>
                </form>
            <?php else: ?>
                <a class="nav-signin" href="<?= e(url('login')) ?>">Sign in</a>
                <a class="button button-primary button-small" href="<?= e(url('register')) ?>">Become a donor</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main id="main-content">
    <?php foreach ($flashes as $notice): ?>
        <div class="container flash-wrap">
            <div class="flash flash-<?= e($notice['type']) ?>" role="status"><?= e($notice['message']) ?></div>
        </div>
    <?php endforeach; ?>
    <?php require $viewFile; ?>
</main>

<footer class="site-footer">
    <div class="container footer-main">
        <div>
            <a class="brand brand-footer" href="<?= e(url('home')) ?>">
                <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 40 40"><path d="M20 3.5c-3.8 6.3-11.4 14.4-11.4 22.1A11.4 11.4 0 0 0 20 37a11.4 11.4 0 0 0 11.4-11.4C31.4 17.9 23.8 9.8 20 3.5Z"/><path d="M14.4 23.4h4v-4h3.2v4h4v3.2h-4v4h-3.2v-4h-4z"/></svg></span>
                <span class="brand-type">donor<span>nexa</span><small>CONNECTING HOPE</small></span>
            </a>
            <p class="footer-copy">A trusted connection between the people who need blood and the people ready to give it.</p>
        </div>
        <div class="footer-links">
            <strong>Explore</strong>
            <a href="<?= e(url('emergencies')) ?>">Emergency board</a>
            <a href="<?= e(url('register')) ?>">Join as a donor</a>
            <a href="<?= e(url('register', ['role' => 'hospital'])) ?>">For hospitals</a>
        </div>
        <div class="footer-note">
            <span class="eyebrow">Built around people</span>
            <p>Your contact details stay private until you choose to accept a match.</p>
        </div>
    </div>
    <div class="container footer-bottom">
        <span>© <?= date('Y') ?> DonorNexa · A Web Engineering CEP project</span>
        <span>Designed for care, built for trust.</span>
    </div>
</footer>
</body>
</html>
