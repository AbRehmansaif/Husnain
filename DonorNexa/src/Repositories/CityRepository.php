<?php
declare(strict_types=1);

namespace DonorNexa\Repositories;

use DonorNexa\Config\Database;
use PDO;

final class CityRepository implements RepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT id, city_name, province FROM cities WHERE id = :id');
        $statement->execute(['id' => $id]);
        $city = $statement->fetch();
        return $city ?: null;
    }

    public function all(): array
    {
        return $this->db->query(
            'SELECT id, city_name, province FROM cities ORDER BY city_name'
        )->fetchAll();
    }
}
