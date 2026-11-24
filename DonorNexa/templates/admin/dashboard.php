<?php
$pageTitle = 'Admin dashboard';
$weeklyJson = json_encode($weekly, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$demandJson = json_encode($demand, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>
<section class="dashboard-hero admin-hero">
    <div class="container dashboard-hero-inner"><div><span class="eyebrow">DonorNexa operations</span><h1>A clearer view of <em>care.</em></h1><p>Verify, support and keep every connection accountable.</p></div><div class="admin-hero-actions"><a class="button button-light button-small" href="<?= e(url('export', ['type' => 'requests'])) ?>">Export requests ↓</a><a class="button button-quiet button-small" href="<?= e(url('export', ['type' => 'donors'])) ?>">Export donors ↓</a></div></div>
</section>
<section class="section dashboard-section"><div class="container">
    <div class="metric-grid">
        <article class="metric-card"><span>Active requests</span><strong><?= e($stats['active']) ?></strong><small>Need a donor now</small><span class="metric-symbol">↗</span></article>
        <article class="metric-card"><span>Registered donors</span><strong><?= e($stats['donors']) ?></strong><small>Active community members</small><span class="metric-symbol">♡</span></article>
        <article class="metric-card"><span>Units fulfilled</span><strong><?= e($stats['fulfilled']) ?></strong><small>Verified donations</small><span class="metric-symbol">✳</span></article>
        <article class="metric-card metric-card-accent"><span>Fulfilment rate</span><strong><?= e(number_format($stats['fulfillment_rate'], 1)) ?><small>%</small></strong><small>Across all requested units</small><span class="metric-symbol">◌</span></article>
    </div>
    <div class="analytics-grid">
        <article class="panel-card chart-card"><div class="panel-heading"><div><span class="eyebrow">Activity over time</span><h2>Requests per week</h2></div><span class="chart-note">Last 8 weeks</span></div><div class="chart-bars" data-chart="weekly" data-values="<?= e($weeklyJson ?: '[]') ?>"><p class="chart-empty">Loading activity…</p></div></article>
        <article class="panel-card chart-card"><div class="panel-heading"><div><span class="eyebrow">Need and response</span><h2>Blood group demand</h2></div><span class="chart-note">Units · total</span></div><div class="demand-bars" data-chart="demand" data-values="<?= e($demandJson ?: '[]') ?>"><p class="chart-empty">Loading supply and demand…</p></div><div class="chart-legend"><span><i class="legend-demand"></i> Requested</span><span><i class="legend-supply"></i> Fulfilled</span></div></article>
    </div>

    <div class="section-heading dashboard-heading"><div><span class="eyebrow">A careful first step</span><h2>Hospitals awaiting <em>verification.</em></h2></div><span class="results-count"><?= count($pending) ?> pending</span></div>
    <?php if ($pending): ?><div class="verification-list"><?php foreach ($pending as $hospital): ?><article class="verification-row"><div class="verification-initial"><?= e(strtoupper(substr($hospital['hospital_name'], 0, 1))) ?></div><div class="verification-info"><strong><?= e($hospital['hospital_name']) ?></strong><span><?= e($hospital['city_name']) ?> · <?= e($hospital['email']) ?></span><small>Licence <?= e($hospital['licence_no']) ?> · Submitted <?= e(date('M j, Y', strtotime($hospital['created_at']))) ?></small></div><div class="verification-actions"><?php if ($hospital['licence_file']): ?><a class="text-link" target="_blank" rel="noopener" href="<?= e(url('licence', ['id' => $hospital['user_id']])) ?>">View licence ↗</a><?php endif; ?><form method="post" action="<?= e(url('verify_hospital')) ?>"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= e($hospital['user_id']) ?>"><button class="button button-primary button-small" type="submit" name="decision" value="verified">Verify</button><button class="button button-outline button-small" type="submit" name="decision" value="rejected">Reject</button></form></div></article><?php endforeach; ?></div><?php else: ?><div class="quiet-empty"><span>✳</span><p>You're all caught up. No hospital verification is waiting.</p></div><?php endif; ?>

    <div class="admin-bottom-grid">
        <article class="panel-card settings-card"><div class="panel-heading"><div><span class="eyebrow">Keep the system thoughtful</span><h2>System settings</h2></div><span class="panel-icon">⚙</span></div><form method="post" action="<?= e(url('settings')) ?>" class="form-stack">
            <?= csrf_field() ?>
            <?php $settingsMap = array_column($settings, 'setting_value', 'setting_key'); ?>
            <label>Donor cool-down period <span class="input-suffix"><input type="number" name="cooldown_days" min="30" max="180" required value="<?= e($settingsMap['cooldown_days'] ?? 90) ?>"><span>days</span></span><small class="field-hint">Default 90 days; eligibility is recalculated from this value.</small></label>
            <label>Initial donor match size <span class="input-suffix"><input type="number" name="match_top_n" min="5" max="100" required value="<?= e($settingsMap['match_top_n'] ?? 20) ?>"><span>donors</span></span><small class="field-hint">Critical requests automatically notify a wider pool.</small></label>
            <button class="button button-primary button-small" type="submit">Save settings</button>
        </form></article>
        <article class="panel-card admin-links-card"><span class="eyebrow">Your oversight tools</span><h2>Keep every detail in view.</h2><a href="<?= e(url('users')) ?>"><span><strong>People & accounts</strong><small>Search, filter and manage roles</small></span><b>→</b></a><a href="<?= e(url('admin_requests')) ?>"><span><strong>All requests</strong><small>Search, sort and review activity</small></span><b>→</b></a><a href="<?= e(url('cities')) ?>"><span><strong>Service area</strong><small>Manage cities and provinces</small></span><b>→</b></a><a href="<?= e(url('audit')) ?>"><span><strong>Audit trail</strong><small>Review every important change</small></span><b>→</b></a></article>
    </div>
</div></section>
