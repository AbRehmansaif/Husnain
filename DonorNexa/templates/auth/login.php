<?php $pageTitle = 'Sign in'; ?>
<section class="auth-section">
    <div class="container auth-layout">
        <div class="auth-story">
            <span class="eyebrow eyebrow-light">Welcome back</span>
            <h1>Good to have<br><em>you here.</em></h1>
            <p>Every thoughtful connection starts with people like you. Sign in to pick up where you left off.</p>
            <div class="auth-quote"><span>“</span><p>Kindness is the bridge between a need and a new beginning.</p></div>
        </div>
        <div class="auth-card">
            <span class="eyebrow">Your DonorNexa account</span>
            <h2>Sign in</h2>
            <p class="muted">Enter the email and password linked to your account.</p>
            <form method="post" action="<?= e(url('login')) ?>" class="form-stack">
                <?= csrf_field() ?>
                <label>Email address<input type="email" name="email" autocomplete="email" required maxlength="190" placeholder="you@example.com"></label>
                <label>Password<input type="password" name="password" autocomplete="current-password" required></label>
                <label class="remember-row"><input type="checkbox" name="remember" value="1"><span>Keep me signed in on this device for 30 days</span></label>
                <button class="button button-primary button-full" type="submit">Continue to your account <span aria-hidden="true">→</span></button>
            </form>
            <p class="auth-switch">New to DonorNexa? <a href="<?= e(url('register')) ?>">Create an account</a></p>
            <div class="privacy-note"><span class="lock-icon" aria-hidden="true">◈</span><span>Your details are protected. We never publish donor phone numbers.</span></div>
        </div>
    </div>
</section>
