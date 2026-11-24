<?php
declare(strict_types=1);

namespace DonorNexa\Controllers;

use DonorNexa\Config\Database;
use DonorNexa\Core\BaseController;
use DonorNexa\Repositories\CityRepository;
use PDO;

final class HomeController extends BaseController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function index(): void
    {
        $cities = (new CityRepository())->all();
        $this->render('home', ['cities' => $cities]);
    }

    public function emergencies(): void
    {
        $cityInput = (string) ($_GET['city'] ?? '');
        $city = $cityInput !== '' ? filter_var($cityInput, FILTER_VALIDATE_INT) : null;
        if ($cityInput !== '' && (!$city || !(new CityRepository())->find((int) $city))) {
            throw new \DonorNexa\Exceptions\ValidationException('Choose a valid city filter.');
        }
        $sql = "SELECT r.id, r.blood_group, r.urgency, r.units_needed, r.units_reserved, r.units_fulfilled,
                       r.required_by, c.city_name
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
        $sql .= " ORDER BY FIELD(r.urgency, 'critical', 'urgent', 'normal'), r.required_by ASC LIMIT 24";
        $statement = $this->db->prepare($sql);
        $statement->execute($parameters);
        $cities = (new CityRepository())->all();
        $this->render('public/emergencies', ['requests' => $statement->fetchAll(), 'cities' => $cities]);
    }
}
