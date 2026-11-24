<?php
declare(strict_types=1);

namespace DonorNexa\Services;

use DonorNexa\Config\Database;
use PDO;

final class AuditService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function record(
        ?int $userId,
        string $action,
        string $entity,
        ?int $entityId,
        mixed $oldValue = null,
        mixed $newValue = null
    ): void {
        $statement = $this->db->prepare(
            'INSERT INTO audit_logs (user_id, action, entity, entity_id, old_value, new_value, ip_address)
             VALUES (:user_id, :action, :entity, :entity_id, :old_value, :new_value, :ip_address)'
        );
        $statement->execute([
            'user_id' => $userId,
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'old_value' => $oldValue === null ? null : json_encode($oldValue, JSON_THROW_ON_ERROR),
            'new_value' => $newValue === null ? null : json_encode($newValue, JSON_THROW_ON_ERROR),
            'ip_address' => filter_var($_SERVER['REMOTE_ADDR'] ?? null, FILTER_VALIDATE_IP) ?: null,
        ]);
    }
}
