<?php
declare(strict_types=1);

namespace DonorNexa\Models;

class User extends Model implements Authenticatable
{
    public function verifyPassword(string $plainText, string $hash): bool
    {
        return password_verify($plainText, $hash);
    }
}
