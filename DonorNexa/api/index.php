<?php
declare(strict_types=1);

$api = new \DonorNexa\Controllers\ApiController();
match ((string) ($_GET['api'] ?? '')) {
    'stats' => $api->stats(),
    'emergencies' => $api->emergencies(),
    'donors' => $api->donors(),
    default => throw new \DonorNexa\Exceptions\ValidationException('API endpoint not found.', 404),
};
