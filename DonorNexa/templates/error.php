<?php
$user = current_user();
$flashes = [];
$viewFile = '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($errorCode) ?> · DonorNexa</title>
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/app.css">
</head>
<body>
<main class="error-page">
    <div class="error-card">
        <span class="error-code"><?= e($errorCode) ?></span>
        <span class="eyebrow">A small pause</span>
        <h1>We couldn't complete that.</h1>
        <p><?= e($errorMessage) ?></p>
        <a class="button button-primary" href="<?= e(url($user ? 'dashboard' : 'home')) ?>">Back to safety</a>
    </div>
</main>
</body>
</html>
