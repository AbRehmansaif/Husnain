<?php $pageTitle = 'Create an account'; ?>
<section class="auth-section auth-register">
    <div class="container auth-layout">
        <div class="auth-story">
            <span class="eyebrow eyebrow-light">A community that cares</span>
            <h1>There is good<br><em>in showing up.</em></h1>
            <p>Whether you can give or you care for a community, there is a place for you in DonorNexa.</p>
            <ul class="auth-points"><li><span>✓</span> Verified hospital requests</li><li><span>✓</span> Thoughtful eligibility checks</li><li><span>✓</span> Privacy at every step</li></ul>
        </div>
        <div class="auth-card register-card">
            <span class="eyebrow">Your first step</span>
            <h2>Create your account</h2>
            <p class="muted">Choose how you would like to take part.</p>
            <form method="post" action="<?= e(url('register')) ?>" enctype="multipart/form-data" class="form-stack" data-register-form>
                <?= csrf_field() ?>
                <fieldset class="role-picker">
                    <legend>I am joining as a</legend>
                    <label class="role-option"><input type="radio" name="role" value="donor" <?= (($_GET['role'] ?? '') !== 'hospital') ? 'checked' : '' ?>><span><strong>Blood donor</strong><small>I'm ready to help someone</small></span></label>
                    <label class="role-option"><input type="radio" name="role" value="hospital" <?= (($_GET['role'] ?? '') === 'hospital') ? 'checked' : '' ?>><span><strong>Hospital</strong><small>I coordinate patient care</small></span></label>
                </fieldset>
                <label>Your name<input name="name" autocomplete="name" required maxlength="120" placeholder="Full name"></label>
                <label>Email address<input type="email" name="email" autocomplete="email" required maxlength="190" placeholder="you@example.com"></label>
                <div class="field-grid" data-donor-fields>
                    <label>Blood group<select name="blood_group" required><option value="">Choose group</option><?php foreach (['O-', 'O+', 'A-', 'A+', 'B-', 'B+', 'AB-', 'AB+'] as $group): ?><option><?= e($group) ?></option><?php endforeach; ?></select></label>
                    <label>Date of birth<input type="date" name="dob" required min="<?= e(date('Y-m-d', strtotime('-60 years'))) ?>" max="<?= e(date('Y-m-d', strtotime('-18 years'))) ?>"></label>
                    <label>Weight (kg)<input type="number" name="weight" min="50" max="250" step="0.1" required placeholder="e.g. 64"></label>
                    <label class="field-span">Last donation date <span class="optional">(optional)</span><input type="date" name="last_donation_date" max="<?= e(date('Y-m-d')) ?>" aria-describedby="last-donation-hint"><small id="last-donation-hint" class="field-hint">Helps us respect your recovery period if you donated outside DonorNexa.</small></label>
                    <label>CNIC<input name="cnic" required inputmode="numeric" pattern="\d{5}-\d{7}-\d" placeholder="35202-1234567-1" data-cnic></label>
                    <label class="field-span">Mobile number<input name="phone" required inputmode="tel" pattern="03\d{2}-\d{7}" placeholder="0300-1234567" data-phone></label>
                </div>
                <div class="field-grid" data-hospital-fields hidden>
                    <label class="field-span">Hospital name<input name="hospital_name" required maxlength="160" placeholder="Registered hospital name"></label>
                    <label>Licence number<input name="licence_no" required maxlength="80" placeholder="Licence number"></label>
                    <label>Contact mobile<input name="phone" required inputmode="tel" pattern="03\d{2}-\d{7}" placeholder="0300-1234567" data-phone></label>
                    <label class="field-span">Hospital licence (PDF)<input type="file" name="licence_file" accept="application/pdf,.pdf" required data-upload="licence"></label>
                </div>
                <label>City
                    <select name="city_id" required><option value="">Choose city</option><?php foreach ($cities as $city): ?><option value="<?= e($city['id']) ?>"><?= e($city['city_name']) ?>, <?= e($city['province']) ?></option><?php endforeach; ?></select>
                </label>
                <label>Password<input type="password" name="password" autocomplete="new-password" minlength="10" required aria-describedby="password-hint"><small id="password-hint" class="field-hint">At least 10 characters, including letters and numbers.</small></label>
                <button class="button button-primary button-full" type="submit">Create account <span aria-hidden="true">→</span></button>
            </form>
            <p class="auth-switch">Already part of the community? <a href="<?= e(url('login')) ?>">Sign in</a></p>
        </div>
    </div>
</section>
