<?php $pageTitle = 'Hospital dashboard'; ?>
<section class="dashboard-hero">
    <div class="container dashboard-hero-inner"><div><span class="eyebrow">Hospital coordination</span><h1><?= e($hospital['hospital_name']) ?></h1><p><?= e($hospital['city_name']) ?> · Registered with DonorNexa</p></div><div class="verification-badge verification-<?= e($hospital['verification_status']) ?>"><span class="verification-dot"></span><?= e(ucfirst($hospital['verification_status'])) ?> account</div></div>
</section>
<section class="section dashboard-section"><div class="container">
    <?php if ($hospital['verification_status'] !== 'verified'): ?>
        <div class="pending-panel"><div class="pending-symbol">✳</div><div><span class="eyebrow">One careful step</span><h2><?= $hospital['verification_status'] === 'rejected' ? 'We need to review your details.' : 'Your licence is being reviewed.' ?></h2><p><?= $hospital['verification_status'] === 'rejected' ? 'Please contact the DonorNexa administrator to update your registration details.' : 'Your hospital profile is submitted. An administrator will verify your licence document. Request creation will be available as soon as your account is approved.' ?></p><span class="status-pill status-pending"><?= e(ucfirst($hospital['verification_status'])) ?> verification</span></div></div>
    <?php else: ?>
        <div class="hospital-top-grid">
            <article class="panel-card request-create-card"><div class="panel-heading"><div><span class="eyebrow">Reach the right people</span><h2>Create a blood request</h2></div><span class="panel-icon">+</span></div>
                <form method="post" action="<?= e(url('create_request')) ?>" class="form-stack" data-validate>
                    <?= csrf_field() ?>
                    <div class="field-grid">
                        <label>Blood group<select name="blood_group" required><option value="">Choose group</option><?php foreach (['O-', 'O+', 'A-', 'A+', 'B-', 'B+', 'AB-', 'AB+'] as $group): ?><option><?= e($group) ?></option><?php endforeach; ?></select></label>
                        <label>Units needed<input type="number" name="units_needed" min="1" max="20" required value="1"></label>
                        <label>Urgency<select name="urgency" required><option value="normal">Normal</option><option value="urgent">Urgent</option><option value="critical">Critical</option></select></label>
                        <label>Needed by<input type="datetime-local" name="required_by" min="<?= e(date('Y-m-d\TH:i', strtotime('+15 minutes'))) ?>" required></label>
                        <label class="field-span">Ward / department<input name="ward" required maxlength="100" placeholder="e.g. Emergency ward"></label>
                        <label class="field-span">Care notes <span class="optional">(optional)</span><textarea name="notes" rows="3" maxlength="1000" placeholder="Share helpful coordination information. Avoid patient-identifying details."></textarea></label>
                    </div>
                    <button class="button button-primary" type="submit">Publish verified request <span aria-hidden="true">→</span></button>
                    <small class="form-footnote">Maximum 3 open Critical requests. Donor contact stays private until they accept.</small>
                </form>
            </article>
            <aside class="inventory-card"><span class="eyebrow">Recorded donations</span><h2>Blood inventory</h2><p>Inventory updates when you verify a completed donation.</p><?php if ($inventory): ?><div class="inventory-list"><?php foreach ($inventory as $item): ?><div><span class="blood-chip blood-chip-small"><?= e($item['blood_group']) ?></span><strong><?= e($item['units_available']) ?></strong><small>units</small></div><?php endforeach; ?></div><?php else: ?><div class="inventory-empty">No donations recorded yet.</div><?php endif; ?><div class="trust-score"><span>Hospital trust score</span><strong><?= e($hospital['trust_score']) ?><small>/100</small></strong><div class="trust-meter"><span style="width: <?= e($hospital['trust_score']) ?>%"></span></div><small>Repeated cancellations reduce trust.</small></div></aside>
        </div>

        <div class="section-heading dashboard-heading"><div><span class="eyebrow">Your coordination desk</span><h2>Recent <em>requests.</em></h2></div><span class="results-count"><?= count($requests) ?> requests</span></div>
        <form class="table-filters" method="get" action="<?= e(APP_URL) ?>/index.php">
            <input type="hidden" name="page" value="dashboard">
            <label class="sr-only" for="request-search">Search requests</label><input id="request-search" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Search blood group or ward">
            <label class="sr-only" for="request-urgency">Filter urgency</label><select id="request-urgency" name="urgency"><option value="">All urgency levels</option><?php foreach (['critical', 'urgent', 'normal'] as $value): ?><option value="<?= e($value) ?>" <?= ($_GET['urgency'] ?? '') === $value ? 'selected' : '' ?>><?= e(ucfirst($value)) ?></option><?php endforeach; ?></select>
            <button class="button button-outline button-small" type="submit">Filter</button>
        </form>
        <div class="table-wrap"><table><thead><tr><th>Request</th><th>Urgency</th><th>Deadline</th><th>Progress</th><th>Status</th><th>Manage</th></tr></thead><tbody>
            <?php foreach ($requests as $request): ?><tr>
                <td><span class="blood-chip blood-chip-small"><?= e($request['blood_group']) ?></span><strong class="table-primary"><?= e($request['ward']) ?></strong></td>
                <td><span class="urgency urgency-<?= e($request['urgency']) ?>"><?= e(ucfirst($request['urgency'])) ?></span></td>
                <td><?= e(date('M j, H:i', strtotime($request['required_by']))) ?></td>
                <td><?= e($request['units_fulfilled']) ?> / <?= e($request['units_needed']) ?> units<?php if ((int) $request['units_reserved']): ?><small class="reserved-label"><?= e($request['units_reserved']) ?> reserved</small><?php endif; ?></td>
                <td><span class="status-pill status-<?= e($request['status']) ?>"><?= e(str_replace('_', ' ', ucfirst($request['status']))) ?></span></td>
                <td><div class="row-actions"><a class="text-link" href="<?= e(url('request_donors', ['id' => $request['id']])) ?>">View matches</a><?php if (in_array($request['status'], ['open', 'partially_fulfilled'], true)): ?><form method="post" action="<?= e(url('cancel_request')) ?>" data-confirm="Cancel this request? Accepted donor reservations will be released."><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="request_id" value="<?= e($request['id']) ?>"><button class="text-button text-danger" type="submit">Cancel</button></form><?php endif; ?></div></td>
            </tr><?php endforeach; ?>
            <?php if ($requests === []): ?><tr><td colspan="6" class="table-empty">No requests match those filters yet.</td></tr><?php endif; ?>
        </tbody></table></div>
        <?php if (($hospital['request_pages'] ?? 1) > 1): ?>
            <nav class="pagination" aria-label="Requests pages"><?php for ($number = 1; $number <= $hospital['request_pages']; $number++): ?><a href="<?= e(url('dashboard', ['p' => $number, 'q' => $_GET['q'] ?? '', 'urgency' => $_GET['urgency'] ?? ''])) ?>" class="<?= $number === $hospital['request_page'] ? 'active' : '' ?>"><?= e($number) ?></a><?php endfor; ?></nav>
        <?php endif; ?>
    <?php endif; ?>
</div></section>
