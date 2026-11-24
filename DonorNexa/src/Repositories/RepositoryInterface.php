<?php
declare(strict_types=1);

namespace DonorNexa\Repositories;

interface RepositoryInterface
{
    public function find(int $id): ?array;
}
