<?php
declare(strict_types=1);

namespace DonorNexa\Services;

use DonorNexa\Config\Database;
use PDO;

final class RequestExpiryService
{
    private PDO $db;
    private AuditService $audit;

    public function __construct()
    {
        $this->db = Database::connection();
        $this->audit = new AuditService();
    }

    public function run(): void
    {
        $requests = $this->db->query(
            "SELECT id, status FROM blood_requests
             WHERE status IN ('open', 'partially_fulfilled') AND required_by < NOW()"
        )->fetchAll();

        foreach ($requests as $request) {
            $this->db->beginTransaction();
            try {
                $lock = $this->db->prepare('SELECT status FROM blood_requests WHERE id = :id FOR UPDATE');
                $lock->execute(['id' => $request['id']]);
                $oldStatus = $lock->fetchColumn();
                if (!in_array($oldStatus, ['open', 'partially_fulfilled'], true)) {
                    $this->db->commit();
                    continue;
                }

                $update = $this->db->prepare(
                    "UPDATE blood_requests SET status = 'expired' WHERE id = :id"
                );
                $update->execute(['id' => $request['id']]);
                $pendingMatches = $this->db->prepare(
                    "SELECT id, donor_id FROM request_matches WHERE request_id = :id AND status = 'notified'"
                );
                $pendingMatches->execute(['id' => $request['id']]);
                $matches = $pendingMatches->fetchAll();
                $noResponse = $this->db->prepare(
                    "UPDATE request_matches SET status = 'no_response', responded_at = NOW() WHERE id = :id"
                );
                foreach ($matches as $match) {
                    $noResponse->execute(['id' => $match['id']]);
                    $this->audit->record(
                        null,
                        'match.no_response',
                        'request_match',
                        (int) $match['id'],
                        ['status' => 'notified'],
                        ['status' => 'no_response', 'donor_id' => (int) $match['donor_id']]
                    );
                }
                $this->audit->record(
                    null,
                    'request.expired',
                    'blood_request',
                    (int) $request['id'],
                    ['status' => $oldStatus],
                    ['status' => 'expired']
                );
                $this->db->commit();
            } catch (\Throwable $exception) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                throw $exception;
            }
        }
    }
}
