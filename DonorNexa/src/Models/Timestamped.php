<?php
declare(strict_types=1);

namespace DonorNexa\Models;

trait Timestamped
{
    protected function now(): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone(APP_TIMEZONE)))->format('Y-m-d H:i:s');
    }
}
