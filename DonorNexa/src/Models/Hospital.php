<?php
declare(strict_types=1);

namespace DonorNexa\Models;

final class Hospital extends User
{
    public function isVerified(array $hospital): bool
    {
        return ($hospital['verification_status'] ?? '') === 'verified'
            && ($hospital['status'] ?? '') === 'active';
    }
}
