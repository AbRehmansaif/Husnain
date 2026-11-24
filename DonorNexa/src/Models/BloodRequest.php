<?php
declare(strict_types=1);

namespace DonorNexa\Models;

final class BloodRequest extends Model
{
    public const OPEN_STATUSES = ['open', 'partially_fulfilled'];
}
