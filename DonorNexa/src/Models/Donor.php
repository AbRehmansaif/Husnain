<?php
declare(strict_types=1);

namespace DonorNexa\Models;

final class Donor extends User
{
    public function isEligible(array $donor, int $cooldownDays): bool
    {
        if ((int) $donor['age'] < 18 || (int) $donor['age'] > 60 || (float) $donor['weight'] < 50) {
            return false;
        }
        if (!(bool) $donor['is_available'] || $donor['status'] !== 'active') {
            return false;
        }

        return empty($donor['last_donation_date'])
            || strtotime((string) $donor['last_donation_date'] . " +{$cooldownDays} days") <= time();
    }
}
