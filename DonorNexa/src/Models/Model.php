<?php
declare(strict_types=1);

namespace DonorNexa\Models;

use DonorNexa\Config\Database;
use PDO;

abstract class Model
{
    use Timestamped;

    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }
}
