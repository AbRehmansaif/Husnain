<?php
$pageTitle = 'My donor dashboard';
$badges = [];
foreach ([1 => 'First light', 5 => 'Five acts of kindness', 10 => 'Ten lives touched'] as $milestone => $label) {
    if ($donationCount >= $milestone) {
        $badges[] = $label;
    }
}
?>
<section class="dashboard-hero">
    <div class="container dashboard-hero-inner"><div class="donor-greeting"><?php if ($donor['photo']): ?><img class="donor-avatar" src="<?= e(url('photo')) ?>" alt="Your profile photo"><?php else: ?><span class="donor-avatar donor-avatar-placeholder" aria-hidden="true"><?= e(strtoupper(substr($user['name'], 0, 1))) ?></span><?php endif; ?><div><span class="eyebrow">Your donor space</span><h1>Good to see you, <em><?= e(explode(' ', $user['name'])[0]) ?>.</em></h1><p>Your generosity has the power to make someone's hardest day a little lighter.</p></div></div><div class="dashboard-blood"><span>Your blood group</span><strong><?= e($donor['blood_group']) ?></strong></div></div>
</section>
<section class="section dashboard-section">
    <div class="container">
        <div class="donor-summary-grid">
            <article class="summary-card summary-card-highlight">
                <span class="eyebrow">Your readiness</span>
                <div class="availability-row"><strong><?= $donor['is_available'] ? 'Available to help' : 'Taking a little rest' ?></strong><span class="availability-dot <?= $donor['is_available'] ? 'is-on' : '' ?>"></span></div>
                <p><?= $donor['is_available'] ? 'We’ll thoughtfully match you when you’re eligible.' : 'You can turn availability back on whenever you are ready.' ?></p>
                <?php if ($eligibleDate): ?><p class="eligibility-date">Eligible again on <strong><?= e(date('M j, Y', strtotime($eligibleDate))) ?></strong></p><?php endif; ?>
                <?php if ($eligibleNow): ?><p class="eligibility-date">You meet the current matching checks.</p><?php endif; ?>
                <form method="post" action="<?= e(url('profile')) ?>" class="availability-form">
                    <?= csrf_field() ?>
                    <label class="toggle-row"><span>Show me as available</span><span class="switch"><input type="checkbox" name="is_available" value="1" <?= $donor['is_available'] ? 'checked' : '' ?>><span class="switch-slider"></span></span></label>
                    <button class="button button-outline button-small" type="submit">Update availability</button>
                </form>
                <details class="profile-details"><summary>Edit donor details</summary>
                    <form method="post" action="<?= e(url('profile')) ?>" enctype="multipart/form-data" class="form-stack">
                        <?= csrf_field() ?><input type="hidden" name="is_available" value="<?= $donor['is_available'] ? '1' : '0' ?>">
                        <div class="form-stack">
                            <label>Mobile number<input name="phone" value="<?= e($donor['phone']) ?>" required inputmode="tel" pattern="03\d{2}-\d{7}" placeholder="0300-1234567"></label>
                            <label>Weight (kg)<input type="number" name="weight" value="<?= e($donor['weight']) ?>" min="20" max="250" step="0.1" required></label>
                            <label>City<select name="city_id" required><?php foreach ($cities as $city): ?><option value="<?= e($city['id']) ?>" <?= (int) $city['id'] === (int) $donor['city_id'] ? 'selected' : '' ?>><?= e($city['city_name']) ?></option><?php endforeach; ?></select></label>
                            <label>Last donation date<input type="date" name="last_donation_date" value="<?= e($donor['last_donation_date'] ?? '') ?>" max="<?= e(date('Y-m-d')) ?>"></label>
                            <label>Profile photo (JPG / PNG, max 2 MB)<input type="file" name="photo" accept="image/jpeg,image/png,.jpg,.jpeg,.png" data-upload="photo"></label>
                            <button class="button button-outline button-small" type="submit">Save profile</button>
                        </div>
                    </form>
                </details>
            </article>
            <article class="summary-card"><span class="eyebrow">Your impact</span><strong class="summary-number"><?= e($donationCount) ?></strong><h2>verified donation<?= $donationCount === 1 ? '' : 's' ?></h2><p>Each one is a moment of hope shared with someone who needed it.</p><div class="badge-list"><?php if ($badges): foreach ($badges as $badge): ?><span class="earned-badge">✳ <?= e($badge) ?></span><?php endforeach; else: ?><span class="badge-locked">Your first badge is waiting.</span><?php endif; ?></div></article>
            <article class="summary-card summary-card-soft"><span class="eyebrow">A gentle reminder</span><div class="rest-icon">☼</div><h2>Rest is part of giving.</h2><p>We check your last donation before matching. Your wellbeing always comes first.</p><span class="cooldown-pill"><?= e((int) $donor['age']) ?> years · <?= e($donor['city_name']) ?></span></article>
        </div>

        <div class="section-heading dashboard-heading"><div><span class="eyebrow">Thoughtful connections</span><h2>Requests matched <em>for you.</em></h2></div><span class="results-count"><?= count($matches) ?> recent</span></div>
        <div class="match-list">
            <?php $actionable = 0; foreach ($matches as $match): ?>
                <?php if ($match['match_status'] === 'notified' && in_array($match['request_status'], ['open', 'partially_fulfilled'], true)): $actionable++; ?>
                    <article class="match-card">
                        <div class="match-blood"><span class="blood-chip"><?= e($match['blood_group']) ?></span><span class="urgency urgency-<?= e($match['urgency']) ?>"><?= e(ucfirst($match['urgency'])) ?></span></div>
                        <div class="match-info"><h3><?= e($match['city_name']) ?> <span>· <?= e($match['hospital_name']) ?></span></h3><p><?= e($match['ward']) ?> · <?= e($match['units_needed']) ?> unit<?= (int) $match['units_needed'] === 1 ? '' : 's' ?> requested</p><div class="countdown" data-countdown="<?= e(date(DATE_ATOM, strtotime($match['required_by']))) ?>">Checking deadline…</div></div>
                        <div class="match-response">
                            <form method="post" action="<?= e(url('respond')) ?>" class="response-form">
                                <?= csrf_field() ?><input type="hidden" name="match_id" value="<?= e($match['match_id']) ?>">
                                <label class="sr-only" for="decision-<?= e($match['match_id']) ?>">Your response</label>
                                <select id="decision-<?= e($match['match_id']) ?>" name="decision"><option value="accept">I can help</option><option value="decline">Not this time</option></select>
                                <label class="sr-only" for="reason-<?= e($match['match_id']) ?>">Optional reason for declining</label>
                                <input id="reason-<?= e($match['match_id']) ?>" name="reason" maxlength="240" placeholder="Optional note">
                                <button class="button button-primary button-small" type="submit">Send response</button>
                            </form>
                        </div>
                    </article>
                <?php else: ?>
                    <article class="match-card match-card-answered">
                        <span class="blood-chip"><?= e($match['blood_group']) ?></span>
                        <div class="match-info"><h3><?= e($match['city_name']) ?> <span>· <?= e($match['hospital_name']) ?></span></h3><p>Request sent <?= e(date('M j, Y', strtotime($match['created_at']))) ?></p></div>
                        <span class="status-pill status-<?= e($match['match_status']) ?>"><?= e(str_replace('_', ' ', ucfirst($match['match_status']))) ?></span>
                    </article>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if ($matches === []): ?><div class="empty-state"><span class="empty-symbol">♡</span><h2>Your next good deed will find you.</h2><p>When a compatible, verified request comes in, we'll let you know here.</p><a class="button button-outline" href="<?= e(url('emergencies')) ?>">Explore active requests</a></div><?php endif; ?>
        </div>

        <?php if ($history): ?>
        <div class="section-heading dashboard-heading"><div><span class="eyebrow">A record of kindness</span><h2>Your donation <em>history.</em></h2></div></div>
        <div class="table-wrap"><table><thead><tr><th>Date</th><th>Hospital</th><th>Blood group</th><th>Units</th></tr></thead><tbody><?php foreach ($history as $donation): ?><tr><td><?= e(date('M j, Y', strtotime($donation['donated_on']))) ?></td><td><?= e($donation['hospital_name']) ?></td><td><span class="blood-chip blood-chip-small"><?= e($donation['blood_group']) ?></span></td><td><?= e($donation['units']) ?></td></tr><?php endforeach; ?></tbody></table></div>
        <?php endif; ?>
    </div>
</section>
