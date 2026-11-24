<?php
declare(strict_types=1);

namespace DonorNexa\Models;

interface Authenticatable
{
    public function verifyPassword(string $plainText, string $hash): bool;
}
