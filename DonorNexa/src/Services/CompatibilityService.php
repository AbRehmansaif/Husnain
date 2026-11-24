<?php
declare(strict_types=1);

namespace DonorNexa\Services;

final class CompatibilityService
{
    private const MAP = [
        'O-' => ['O-', 'O+', 'A-', 'A+', 'B-', 'B+', 'AB-', 'AB+'],
        'O+' => ['O+', 'A+', 'B+', 'AB+'],
        'A-' => ['A-', 'A+', 'AB-', 'AB+'],
        'A+' => ['A+', 'AB+'],
        'B-' => ['B-', 'B+', 'AB-', 'AB+'],
        'B+' => ['B+', 'AB+'],
        'AB-' => ['AB-', 'AB+'],
        'AB+' => ['AB+'],
    ];

    public function canDonateTo(string $donorGroup, string $recipientGroup): bool
    {
        return in_array($recipientGroup, self::MAP[$donorGroup] ?? [], true);
    }
}
