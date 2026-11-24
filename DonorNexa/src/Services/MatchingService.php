<?php
declare(strict_types=1);

namespace DonorNexa\Services;

use DonorNexa\Config\Database;
use DonorNexa\Exceptions\ValidationException;
use PDO;

final class MatchingService
{
    private PDO $db;
    private CompatibilityService $compatibility;
    private AuditService $audit;

    public function __construct()
    {
        $this->db = Database::connection();
        $this->compatibility = new CompatibilityService();
        $this->audit = new AuditService();
    }

    public function rankedDonors(int $requestId): array
    {
        $request = $this->request($requestId);
        $statement = $this->db->prepare(
            'SELECT d.*, c.city_name, TIMESTAMPDIFF(YEAR, d.dob, CURDATE()) AS age,
                    COALESCE(r.response_rate, 0) AS response_rate,
                    COALESCE(r.responses, 0) AS responses
             FROM v_eligible_donors d
             JOIN cities c ON c.id = d.city_id
             LEFT JOIN (
                 SELECT donor_id,
                        COUNT(*) AS responses,
                        SUM(status IN (\'accepted\', \'declined\', \'donated\')) / COUNT(*) AS response_rate
                 FROM request_matches
                 WHERE status <> \'cancelled\'
                 GROUP BY donor_id
             ) r ON r.donor_id = d.donor_id
             WHERE NOT EXISTS (
                 SELECT 1 FROM request_matches prior
                 WHERE prior.request_id = :request_id AND prior.donor_id = d.donor_id
             )'
        );
        $statement->execute(['request_id' => $requestId]);
        $donors = [];

        foreach ($statement->fetchAll() as $donor) {
            if (!$this->compatibility->canDonateTo($donor['blood_group'], $request['blood_group'])) {
                continue;
            }

            $daysSinceDonation = empty($donor['last_donation_date'])
                ? 365
                : min(365, max(0, (int) ((time() - strtotime($donor['last_donation_date'])) / 86400)));
            $score = ($donor['blood_group'] === $request['blood_group'] ? 35 : 0)
                + ((int) $donor['city_id'] === (int) $request['city_id'] ? 25 : 0)
                + ($daysSinceDonation / 365 * 20)
                + ((float) $donor['response_rate'] * 20);
            $donor['score'] = round($score, 2);
            $donors[] = $donor;
        }

        usort($donors, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);
        return $donors;
    }

    public function notifyTopDonors(int $requestId, ?int $actorId): int
    {
        $request = $this->request($requestId);
        $ranked = $this->rankedDonors($requestId);
        $setting = $this->db->query(
            "SELECT setting_value FROM system_settings WHERE setting_key = 'match_top_n'"
        )->fetchColumn();
        $limit = max(1, (int) ($setting ?: 20));
        if ($request['urgency'] === 'critical') {
            $limit *= 2;
        }
        $ranked = array_slice($ranked, 0, $limit);

        if ($ranked === []) {
            return 0;
        }

        $this->db->beginTransaction();
        try {
            $insertMatch = $this->db->prepare(
                'INSERT INTO request_matches (request_id, donor_id, score, status)
                 VALUES (:request_id, :donor_id, :score, \'notified\')'
            );
            $insertNotification = $this->db->prepare(
                'INSERT INTO notifications (user_id, message) VALUES (:user_id, :message)'
            );
            foreach ($ranked as $donor) {
                $insertMatch->execute([
                    'request_id' => $requestId,
                    'donor_id' => $donor['donor_id'],
                    'score' => $donor['score'],
                ]);
                $matchId = (int) $this->db->lastInsertId();
                $insertNotification->execute([
                    'user_id' => $donor['donor_id'],
                    'message' => 'A ' . $request['blood_group'] . ' ' . $request['urgency']
                        . ' blood request is available in ' . $request['city_name'] . '.',
                ]);
                $this->audit->record(
                    $actorId,
                    'match.notified',
                    'request_match',
                    $matchId,
                    null,
                    ['request_id' => $requestId, 'donor_id' => (int) $donor['donor_id'], 'score' => $donor['score']]
                );
            }
            $this->db->commit();
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }

        return count($ranked);
    }

    private function request(int $requestId): array
    {
        $statement = $this->db->prepare(
            'SELECT r.*, h.city_id, c.city_name
             FROM blood_requests r
             JOIN hospitals h ON h.user_id = r.hospital_id
             JOIN cities c ON c.id = h.city_id
             WHERE r.id = :id'
        );
        $statement->execute(['id' => $requestId]);
        $request = $statement->fetch();
        if (!$request) {
            throw new ValidationException('Blood request not found.');
        }

        return $request;
    }
}
