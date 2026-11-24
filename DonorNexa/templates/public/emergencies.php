<?php $pageTitle = 'Active emergencies'; ?>
<section class="page-hero">
    <div class="container page-hero-inner"><span class="eyebrow">Verified hospital requests</span><h1>Help is needed <em>right now.</em></h1><p>See active blood requests across your community. Patient names and contact details are never shown on this public board.</p></div>
</section>
<section class="section section-board">
    <div class="container">
        <div class="board-toolbar">
            <p><span class="live-indicator"></span> Time-sensitive requests from verified hospitals</p>
            <label class="filter-label" for="emergency-city-filter">Filter by city
                <select id="emergency-city-filter" data-emergency-filter>
                    <option value="">All cities</option>
                <?php foreach ($cities as $city): ?><option value="<?= e($city['id']) ?>"><?= e($city['city_name']) ?></option><?php endforeach; ?>
                </select>
            </label>
        </div>
        <div class="emergency-grid emergency-grid-wide" data-emergency-board data-api="<?= e(APP_URL) ?>/index.php?api=emergencies">
            <?php foreach ($requests as $request): ?>
                <article class="emergency-card" data-city="<?= e($request['city_name']) ?>">
                    <div class="emergency-card-top"><span class="blood-chip"><?= e($request['blood_group']) ?></span><span class="urgency urgency-<?= e($request['urgency']) ?>"><?= e(ucfirst($request['urgency'])) ?></span></div>
                    <h2><?= e($request['city_name']) ?></h2>
                    <p><?= e((int) $request['units_needed'] - (int) $request['units_fulfilled'] - (int) $request['units_reserved']) ?> unit<?= ((int) $request['units_needed'] - (int) $request['units_fulfilled'] - (int) $request['units_reserved']) === 1 ? '' : 's' ?> still needed</p>
                    <div class="countdown" data-countdown="<?= e(date(DATE_ATOM, strtotime($request['required_by']))) ?>">Checking deadline…</div>
                    <a class="text-link" href="<?= e(url('register')) ?>">See how you can help <span aria-hidden="true">→</span></a>
                </article>
            <?php endforeach; ?>
            <?php if ($requests === []): ?><div class="empty-state"><span class="empty-symbol">♡</span><h2>All quiet for now.</h2><p>There are no active requests in this area. Check back soon or join as a donor to be matched when one comes in.</p><a class="button button-primary" href="<?= e(url('register')) ?>">Join as a donor</a></div><?php endif; ?>
        </div>
        <p class="privacy-caption">This board intentionally shares no patient names, ward notes, or phone numbers.</p>
    </div>
</section>
