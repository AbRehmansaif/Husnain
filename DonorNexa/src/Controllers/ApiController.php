<?php
declare(strict_types=1);

namespace DonorNexa\Controllers;

use DonorNexa\Config\Database;
use DonorNexa\Services\MatchingService;
use PDO;

final class ApiController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function stats(): never
    {
        $data = [
            'active_requests' => (int) $this->db->query(
                "SELECT COUNT(*) FROM blood_requests
                 WHERE status IN ('open', 'partially_fulfilled') AND required_by > NOW()
                   AND units_needed > units_reserved + units_fulfilled"
            )->fetchColumn(),
            'registered_donors' => (int) $this->db->query(
                "SELECT COUNT(*) FROM users WHERE role = 'donor' AND status = 'active'"
            )->fetchColumn(),
            'units_fulfilled' => (int) $this->db->query(
                'SELECT COALESCE(SUM(units_fulfilled), 0) FROM blood_requests'
            )->fetchColumn(),
        ];
        $this->json($data);
    }

    public function emergencies(): never
    {
        $cityInput = (string) ($_GET['city'] ?? '');
        $city = $cityInput !== '' ? filter_var($cityInput, FILTER_VALIDATE_INT) : null;
        if ($cityInput !== '' && (!$city || !(new \DonorNexa\Repositories\CityRepository())->find((int) $city))) {
            throw new \DonorNexa\Exceptions\ValidationException('Choose a valid city filter.');
        }
        $sql = "SELECT r.id, r.blood_group, r.urgency, r.units_needed, r.units_reserved, r.units_fulfilled,
                       CONCAT(DATE_FORMAT(r.required_by, '%Y-%m-%dT%H:%i:%s'), '+05:00') AS required_by,
                       c.city_name
                FROM blood_requests r
                JOIN hospitals h ON h.user_id = r.hospital_id
                JOIN cities c ON c.id = h.city_id
                WHERE r.status IN ('open', 'partially_fulfilled')
                  AND r.required_by > NOW()
                  AND r.units_needed > r.units_reserved + r.units_fulfilled
                  AND h.verification_status = 'verified'";
        $parameters = [];
        if ($city !== null) {
            $sql .= ' AND h.city_id = :city_id';
            $parameters['city_id'] = $city;
        }
        $sql .= " ORDER BY FIELD(r.urgency, 'critical', 'urgent', 'normal'), r.required_by ASC LIMIT 12";
        $query = $this->db->prepare($sql);
        $query->execute($parameters);
        $this->json(['requests' => $query->fetchAll()]);
    }

    public function donors(): never
    {
        $hospital = require_role('hospital');
        $requestId = filter_var($_GET['request_id'] ?? null, FILTER_VALIDATE_INT);
        $term = trim((string) ($_GET['q'] ?? ''));
        if (!$requestId || strlen($term) < 2) {
            $this->json(['donors' => []]);
        }
        $owner = $this->db->prepare(
            'SELECT 1 FROM blood_requests WHERE id = :id AND hospital_id = :hospital_id'
        );
        $owner->execute(['id' => $requestId, 'hospital_id' => $hospital['id']]);
        if (!$owner->fetchColumn()) {
            http_response_code(404);
            $this->json(['error' => 'Request not found.']);
        }

        $ranked = (new MatchingService())->rankedDonors((int) $requestId);
        $term = mb_strtolower($term);
        $results = [];
        foreach ($ranked as $donor) {
            if (str_contains(mb_strtolower($donor['name']), $term)
                || str_contains(mb_strtolower($donor['city_name']), $term)
                || str_contains(mb_strtolower($donor['blood_group']), $term)) {
                $results[] = [
                    'name' => $donor['name'],
                    'blood_group' => $donor['blood_group'],
                    'city' => $donor['city_name'],
                    'score' => $donor['score'],
                ];
            }
        }
        $this->json(['donors' => array_slice($results, 0, 12)]);
    }

    private function json(array $data): never
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($data, JSON_THROW_ON_ERROR);
        exit;
    }
}
