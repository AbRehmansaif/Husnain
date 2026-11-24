<?php
declare(strict_types=1);

namespace DonorNexa\Controllers;

use DonorNexa\Config\Database;
use DonorNexa\Core\BaseController;
use DonorNexa\Exceptions\ValidationException;
use DonorNexa\Services\AuditService;
use PDO;

final class AdminController extends BaseController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function dashboard(): void
    {
        require_role('admin');
        $stats = [
            'requests' => (int) $this->db->query('SELECT COUNT(*) FROM blood_requests')->fetchColumn(),
            'active' => (int) $this->db->query(
                "SELECT COUNT(*) FROM blood_requests WHERE status IN ('open', 'partially_fulfilled')"
            )->fetchColumn(),
            'donors' => (int) $this->db->query(
                "SELECT COUNT(*) FROM users WHERE role = 'donor' AND status = 'active'"
            )->fetchColumn(),
            'fulfilled' => (int) $this->db->query(
                'SELECT COALESCE(SUM(units_fulfilled), 0) FROM blood_requests'
            )->fetchColumn(),
            'fulfillment_rate' => (float) $this->db->query(
                'SELECT COALESCE(ROUND(100 * SUM(units_fulfilled) / NULLIF(SUM(units_needed), 0), 1), 0)
                 FROM blood_requests'
            )->fetchColumn(),
        ];
        $weekly = $this->db->query(
            "SELECT DATE_FORMAT(created_at, '%x-%v') AS week, COUNT(*) AS total
             FROM blood_requests WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK)
             GROUP BY DATE_FORMAT(created_at, '%x-%v') ORDER BY week"
        )->fetchAll();
        $demand = $this->db->query(
            'SELECT blood_group, SUM(units_needed) AS demand, SUM(units_fulfilled) AS supply
             FROM blood_requests GROUP BY blood_group ORDER BY FIELD(blood_group, \'O-\', \'O+\', \'A-\', \'A+\', \'B-\', \'B+\', \'AB-\', \'AB+\')'
        )->fetchAll();
        $pending = $this->db->query(
            "SELECT h.user_id, h.hospital_name, h.licence_no, h.licence_file, h.created_at,
                    c.city_name, u.email
             FROM hospitals h JOIN users u ON u.id = h.user_id
             JOIN cities c ON c.id = h.city_id
             WHERE h.verification_status = 'pending'
             ORDER BY h.created_at ASC LIMIT 8"
        )->fetchAll();
        $settings = $this->db->query('SELECT setting_key, setting_value FROM system_settings ORDER BY setting_key')->fetchAll();
        $this->render('admin/dashboard', [
            'stats' => $stats,
            'weekly' => $weekly,
            'demand' => $demand,
            'pending' => $pending,
            'settings' => $settings,
        ]);
    }

    public function users(): void
    {
        require_role('admin');
        $page = max(1, (int) ($_GET['p'] ?? 1));
        $search = trim((string) ($_GET['q'] ?? ''));
        $role = (string) ($_GET['role'] ?? '');
        $sorts = ['joined' => 'u.created_at', 'name' => 'u.name', 'status' => 'u.status'];
        $sort = isset($sorts[$_GET['sort'] ?? '']) ? (string) $_GET['sort'] : 'joined';
        $direction = strtoupper((string) ($_GET['direction'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
        $where = ['1 = 1'];
        $parameters = [];
        if ($search !== '') {
            $where[] = '(u.name LIKE :search_name OR u.email LIKE :search_email)';
            $parameters['search_name'] = '%' . $search . '%';
            $parameters['search_email'] = '%' . $search . '%';
        }
        if (in_array($role, ['admin', 'hospital', 'donor'], true)) {
            $where[] = 'u.role = :role';
            $parameters['role'] = $role;
        }
        $predicate = implode(' AND ', $where);
        $count = $this->db->prepare("SELECT COUNT(*) FROM users u WHERE {$predicate}");
        $count->execute($parameters);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / 12));
        $page = min($page, $pages);
        $users = $this->db->prepare(
            "SELECT u.id, u.name, u.email, u.role, u.status, u.created_at,
                    h.verification_status, h.hospital_name, d.blood_group
             FROM users u
             LEFT JOIN hospitals h ON h.user_id = u.id
             LEFT JOIN donors d ON d.user_id = u.id
             WHERE {$predicate}
             ORDER BY {$sorts[$sort]} {$direction} LIMIT 12 OFFSET " . (($page - 1) * 12)
        );
        $users->execute($parameters);
        $this->render('admin/users', [
            'users' => $users->fetchAll(),
            'page' => $page,
            'pages' => $pages,
            'search' => $search,
            'role' => $role,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function audit(): void
    {
        require_role('admin');
        $page = max(1, (int) ($_GET['p'] ?? 1));
        $search = trim((string) ($_GET['q'] ?? ''));
        $parameters = [];
        $where = '';
        if ($search !== '') {
            $where = 'WHERE a.action LIKE :search_action OR a.entity LIKE :search_entity OR u.name LIKE :search_actor';
            $parameters = [
                'search_action' => '%' . $search . '%',
                'search_entity' => '%' . $search . '%',
                'search_actor' => '%' . $search . '%',
            ];
        }
        $count = $this->db->prepare(
            "SELECT COUNT(*) FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id {$where}"
        );
        $count->execute($parameters);
        $pages = max(1, (int) ceil((int) $count->fetchColumn() / 15));
        $page = min($page, $pages);
        $query = $this->db->prepare(
            "SELECT a.*, u.name AS actor FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id {$where}
             ORDER BY a.created_at DESC, a.id DESC LIMIT 15 OFFSET " . (($page - 1) * 15)
        );
        $query->execute($parameters);
        $this->render('admin/audit', [
            'events' => $query->fetchAll(),
            'page' => $page,
            'pages' => $pages,
            'search' => $search,
        ]);
    }

    public function cities(): void
    {
        require_role('admin');
        $cities = $this->db->query(
            'SELECT c.id, c.city_name, c.province, COUNT(DISTINCT d.user_id) AS donors,
                    COUNT(DISTINCT h.user_id) AS hospitals
             FROM cities c
             LEFT JOIN donors d ON d.city_id = c.id
             LEFT JOIN hospitals h ON h.city_id = c.id
             GROUP BY c.id, c.city_name, c.province ORDER BY c.province, c.city_name'
        )->fetchAll();
        $this->render('admin/cities', ['cities' => $cities]);
    }

    public function requests(): void
    {
        require_role('admin');
        $page = max(1, (int) ($_GET['p'] ?? 1));
        $search = trim((string) ($_GET['q'] ?? ''));
        $status = (string) ($_GET['status'] ?? '');
        $sorts = [
            'urgency' => "FIELD(r.urgency, 'critical', 'urgent', 'normal')",
            'deadline' => 'r.required_by',
            'created' => 'r.created_at',
            'status' => 'r.status',
        ];
        $sort = isset($sorts[$_GET['sort'] ?? '']) ? (string) $_GET['sort'] : 'urgency';
        $direction = strtoupper((string) ($_GET['direction'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
        $where = ['1 = 1'];
        $parameters = [];
        if ($search !== '') {
            $where[] = '(r.blood_group LIKE :search_group OR r.ward LIKE :search_ward OR h.hospital_name LIKE :search_hospital)';
            $parameters['search_group'] = '%' . $search . '%';
            $parameters['search_ward'] = '%' . $search . '%';
            $parameters['search_hospital'] = '%' . $search . '%';
        }
        if (in_array($status, ['open', 'partially_fulfilled', 'fulfilled', 'cancelled', 'expired'], true)) {
            $where[] = 'r.status = :status';
            $parameters['status'] = $status;
        }
        $predicate = implode(' AND ', $where);
        $count = $this->db->prepare(
            "SELECT COUNT(*) FROM blood_requests r JOIN hospitals h ON h.user_id = r.hospital_id WHERE {$predicate}"
        );
        $count->execute($parameters);
        $pages = max(1, (int) ceil((int) $count->fetchColumn() / 12));
        $page = min($page, $pages);
        $query = $this->db->prepare(
            "SELECT r.*, h.hospital_name, c.city_name
             FROM blood_requests r JOIN hospitals h ON h.user_id = r.hospital_id
             JOIN cities c ON c.id = h.city_id WHERE {$predicate}
             ORDER BY {$sorts[$sort]} {$direction}, r.id DESC
             LIMIT 12 OFFSET " . (($page - 1) * 12)
        );
        $query->execute($parameters);
        $this->render('admin/requests', [
            'requests' => $query->fetchAll(),
            'page' => $page,
            'pages' => $pages,
            'search' => $search,
            'status' => $status,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function addCity(): void
    {
        $admin = require_role('admin');
        verify_csrf();
        $name = trim((string) ($_POST['city_name'] ?? ''));
        $province = trim((string) ($_POST['province'] ?? ''));
        if ($name === '' || strlen($name) > 80 || $province === '' || strlen($province) > 80) {
            throw new ValidationException('Enter a city and province name (80 characters maximum).');
        }
        try {
            $insert = $this->db->prepare('INSERT INTO cities (city_name, province) VALUES (:city, :province)');
            $insert->execute(['city' => $name, 'province' => $province]);
        } catch (\PDOException $exception) {
            if ($exception->getCode() === '23000') {
                throw new ValidationException('That city is already listed in this province.');
            }
            throw $exception;
        }
        (new AuditService())->record(
            $admin['id'],
            'city.created',
            'city',
            (int) $this->db->lastInsertId(),
            null,
            ['city_name' => $name, 'province' => $province]
        );
        flash('success', 'City added to the service area.');
        redirect('cities');
    }

    public function updateCity(): void
    {
        $admin = require_role('admin');
        verify_csrf();
        $cityId = filter_var($_POST['city_id'] ?? null, FILTER_VALIDATE_INT);
        $name = trim((string) ($_POST['city_name'] ?? ''));
        $province = trim((string) ($_POST['province'] ?? ''));
        if (!$cityId || $name === '' || strlen($name) > 80 || $province === '' || strlen($province) > 80) {
            throw new ValidationException('Enter a valid city and province name.');
        }

        $select = $this->db->prepare('SELECT city_name, province FROM cities WHERE id = :id');
        $select->execute(['id' => $cityId]);
        $old = $select->fetch();
        if (!$old) {
            throw new ValidationException('City not found.', 404);
        }
        try {
            $update = $this->db->prepare(
                'UPDATE cities SET city_name = :city, province = :province WHERE id = :id'
            );
            $update->execute(['city' => $name, 'province' => $province, 'id' => $cityId]);
        } catch (\PDOException $exception) {
            if ($exception->getCode() === '23000') {
                throw new ValidationException('That city is already listed in this province.');
            }
            throw $exception;
        }
        (new AuditService())->record(
            $admin['id'],
            'city.updated',
            'city',
            (int) $cityId,
            $old,
            ['city_name' => $name, 'province' => $province]
        );
        flash('success', 'City details updated.');
        redirect('cities');
    }

    public function verifyHospital(): void
    {
        $admin = require_role('admin');
        verify_csrf();
        $userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
        $decision = (string) ($_POST['decision'] ?? '');
        if (!$userId || !in_array($decision, ['verified', 'rejected'], true)) {
            throw new ValidationException('Choose a valid hospital and verification decision.');
        }

        $this->db->beginTransaction();
        try {
            $select = $this->db->prepare(
                'SELECT verification_status FROM hospitals WHERE user_id = :id FOR UPDATE'
            );
            $select->execute(['id' => $userId]);
            $oldStatus = $select->fetchColumn();
            if ($oldStatus === false) {
                throw new ValidationException('Hospital not found.', 404);
            }
            $this->db->prepare('UPDATE hospitals SET verification_status = :status WHERE user_id = :id')
                ->execute(['status' => $decision, 'id' => $userId]);
            $this->db->prepare('UPDATE users SET status = :status WHERE id = :id')
                ->execute(['status' => $decision === 'verified' ? 'active' : 'pending', 'id' => $userId]);
            (new AuditService())->record(
                $admin['id'],
                'hospital.' . $decision,
                'hospital',
                (int) $userId,
                ['verification_status' => $oldStatus],
                ['verification_status' => $decision]
            );
            $this->db->commit();
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }

        flash('success', 'Hospital verification has been updated.');
        redirect('admin');
    }

    public function viewLicence(): never
    {
        $admin = require_role('admin');
        $userId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$userId) {
            throw new ValidationException('Choose a valid hospital licence.', 404);
        }
        $statement = $this->db->prepare('SELECT licence_file FROM hospitals WHERE user_id = :id');
        $statement->execute(['id' => $userId]);
        $relativePath = $statement->fetchColumn();
        $licenceDirectory = realpath(APP_ROOT . '/storage/licences');
        $filePath = $relativePath ? realpath(APP_ROOT . '/' . $relativePath) : false;
        if (!$licenceDirectory || !$filePath
            || !str_starts_with($filePath, $licenceDirectory . DIRECTORY_SEPARATOR)
            || !is_file($filePath)) {
            throw new ValidationException('This hospital has no accessible licence document.', 404);
        }

        (new AuditService())->record(
            $admin['id'],
            'hospital.licence_viewed',
            'hospital',
            (int) $userId,
            null,
            ['document' => 'licence.pdf']
        );
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="hospital-licence-' . (int) $userId . '.pdf"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        readfile($filePath);
        exit;
    }

    public function toggleUser(): void
    {
        $admin = require_role('admin');
        verify_csrf();
        $userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$userId || $userId === $admin['id']) {
            throw new ValidationException('You cannot change your own account status.');
        }

        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare('SELECT status, role FROM users WHERE id = :id FOR UPDATE');
            $statement->execute(['id' => $userId]);
            $account = $statement->fetch();
            if (!$account || $account['role'] === 'admin') {
                throw new ValidationException('This account cannot be changed.');
            }
            $newStatus = $account['status'] === 'blocked' ? 'active' : 'blocked';
            $this->db->prepare('UPDATE users SET status = :status WHERE id = :id')
                ->execute(['status' => $newStatus, 'id' => $userId]);
            (new AuditService())->record(
                $admin['id'],
                'user.status_changed',
                'user',
                (int) $userId,
                ['status' => $account['status']],
                ['status' => $newStatus]
            );
            $this->db->commit();
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }

        flash('success', 'Account status updated.');
        redirect('users');
    }

    public function updateSettings(): void
    {
        $admin = require_role('admin');
        verify_csrf();
        $cooldown = filter_var($_POST['cooldown_days'] ?? null, FILTER_VALIDATE_INT);
        $topN = filter_var($_POST['match_top_n'] ?? null, FILTER_VALIDATE_INT);
        if (!$cooldown || $cooldown < 30 || $cooldown > 180 || !$topN || $topN < 5 || $topN > 100) {
            throw new ValidationException('Cool-down must be 30–180 days and initial match size 5–100.');
        }
        $upsert = $this->db->prepare(
            'INSERT INTO system_settings (setting_key, setting_value) VALUES (:key, :value)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        $upsert->execute(['key' => 'cooldown_days', 'value' => (string) $cooldown]);
        $upsert->execute(['key' => 'match_top_n', 'value' => (string) $topN]);
        (new AuditService())->record(
            $admin['id'],
            'settings.updated',
            'system_settings',
            null,
            null,
            ['cooldown_days' => $cooldown, 'match_top_n' => $topN]
        );
        flash('success', 'System settings saved.');
        redirect('admin');
    }

    public function exportCsv(): never
    {
        require_role('admin');
        $type = (string) ($_GET['type'] ?? 'requests');
        $exports = [
            'requests' => [
                'filename' => 'donornexa-requests.csv',
                'sql' => 'SELECT r.id, h.hospital_name, c.city_name, r.blood_group, r.units_needed,
                                 r.units_fulfilled, r.urgency, r.required_by, r.status, r.created_at
                          FROM blood_requests r JOIN hospitals h ON h.user_id = r.hospital_id
                          JOIN cities c ON c.id = h.city_id ORDER BY r.created_at DESC',
            ],
            'donors' => [
                'filename' => 'donornexa-donors.csv',
                'sql' => 'SELECT u.name, d.blood_group, c.city_name, d.is_available, d.last_donation_date, u.status
                          FROM donors d JOIN users u ON u.id = d.user_id
                          JOIN cities c ON c.id = d.city_id ORDER BY c.city_name, u.name',
            ],
        ];
        if (!isset($exports[$type])) {
            throw new ValidationException('Unknown export type.', 404);
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $exports[$type]['filename'] . '"');
        header('X-Content-Type-Options: nosniff');
        $output = fopen('php://output', 'wb');
        if ($output === false) {
            throw new \RuntimeException('The report could not be exported.');
        }
        fwrite($output, "\xEF\xBB\xBF");
        $statement = $this->db->query($exports[$type]['sql']);
        $first = $statement->fetch(PDO::FETCH_ASSOC);
        $headers = $first ? array_keys($first) : [];
        fputcsv($output, $headers);
        if ($first) {
            fputcsv($output, array_map([$this, 'spreadsheetSafe'], array_values($first)));
        }
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, array_map([$this, 'spreadsheetSafe'], array_values($row)));
        }
        fclose($output);
        exit;
    }

    private function spreadsheetSafe(mixed $value): string
    {
        $cell = (string) $value;
        return preg_match('/^[\s]*[=+\-@]/', $cell) ? "'" . $cell : $cell;
    }
}
