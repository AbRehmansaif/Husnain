<?php
declare(strict_types=1);

namespace DonorNexa\Controllers;

use DonorNexa\Config\Database;
use DonorNexa\Core\BaseController;
use DonorNexa\Exceptions\ValidationException;
use DonorNexa\Models\Hospital;
use DonorNexa\Services\AuditService;
use DonorNexa\Services\MatchingService;
use DonorNexa\Services\UploadService;
use PDO;

final class PortalController extends BaseController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function dashboard(): void
    {
        $user = require_role('donor', 'hospital', 'admin');
        if ($user['role'] === 'admin') {
            redirect('admin');
        }
        if ($user['role'] === 'donor') {
            $this->donorDashboard($user);
            return;
        }
        $this->hospitalDashboard($user);
    }

    public function respond(): void
    {
        $user = require_role('donor');
        verify_csrf();
        $matchId = filter_var($_POST['match_id'] ?? null, FILTER_VALIDATE_INT);
        $action = (string) ($_POST['decision'] ?? '');
        if (!$matchId || !in_array($action, ['accept', 'decline'], true)) {
            throw new ValidationException('Choose a valid response to this request.');
        }

        $this->db->beginTransaction();
        try {
            $request = $this->lockRequest($this->matchRequestId((int) $matchId));
            $match = $this->lockMatch((int) $matchId);
            if ((int) $match['donor_id'] !== $user['id'] || $match['match_status'] !== 'notified') {
                throw new ValidationException('This request has already been answered or is no longer available.');
            }

            if ($action === 'accept') {
                if (!in_array($request['status'], ['open', 'partially_fulfilled'], true)
                    || (int) $request['units_reserved'] + (int) $request['units_fulfilled'] >= (int) $request['units_needed']) {
                    throw new ValidationException('All available units for this request are already reserved.');
                }
                $donorLock = $this->db->prepare(
                    'SELECT is_available FROM donors WHERE user_id = :id FOR UPDATE'
                );
                $donorLock->execute(['id' => $user['id']]);
                if (!(bool) $donorLock->fetchColumn()) {
                    throw new ValidationException('Your availability is off. Turn it on before accepting a request.');
                }

                $this->db->prepare(
                    "UPDATE request_matches SET status = 'accepted', responded_at = NOW() WHERE id = :id"
                )->execute(['id' => $matchId]);
                $newReserved = (int) $request['units_reserved'] + 1;
                $nextStatus = $newReserved + (int) $request['units_fulfilled'] >= (int) $request['units_needed']
                    ? 'partially_fulfilled'
                    : $request['status'];
                $this->db->prepare(
                    'UPDATE blood_requests SET units_reserved = :reserved, status = :status WHERE id = :id'
                )->execute(['reserved' => $newReserved, 'status' => $nextStatus, 'id' => $request['id']]);
                (new AuditService())->record(
                    $user['id'],
                    'request.reservation_updated',
                    'blood_request',
                    (int) $request['id'],
                    ['status' => $request['status'], 'units_reserved' => (int) $request['units_reserved']],
                    ['status' => $nextStatus, 'units_reserved' => $newReserved]
                );
                $this->db->prepare('UPDATE donors SET is_available = 0 WHERE user_id = :id')
                    ->execute(['id' => $user['id']]);
                $this->db->prepare(
                    'INSERT INTO notifications (user_id, message) VALUES (:user_id, :message)'
                )->execute([
                    'user_id' => $request['hospital_id'],
                    'message' => 'A matched donor accepted your ' . $request['blood_group'] . ' request. Contact details are now available.',
                ]);
                (new AuditService())->record(
                    $user['id'],
                    'match.accepted',
                    'request_match',
                    (int) $matchId,
                    ['status' => 'notified'],
                    ['status' => 'accepted', 'request_id' => (int) $request['id']]
                );
                (new AuditService())->record(
                    $user['id'],
                    'donor.contact_reveal_authorized',
                    'request_match',
                    (int) $matchId,
                    ['phone_visible' => false],
                    ['phone_visible' => true, 'hospital_id' => (int) $request['hospital_id']]
                );
            } else {
                $reason = trim((string) ($_POST['reason'] ?? ''));
                if (strlen($reason) > 240) {
                    throw new ValidationException('The decline note must be 240 characters or fewer.');
                }
                $this->db->prepare(
                    "UPDATE request_matches SET status = 'declined', decline_reason = :reason, responded_at = NOW()
                     WHERE id = :id"
                )->execute(['reason' => $reason !== '' ? $reason : null, 'id' => $matchId]);
                (new AuditService())->record(
                    $user['id'],
                    'match.declined',
                    'request_match',
                    (int) $matchId,
                    ['status' => 'notified'],
                    ['status' => 'declined', 'reason' => $reason]
                );
            }
            $this->db->commit();
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }

        flash('success', $action === 'accept'
            ? 'You accepted the request. The hospital can now see your contact details.'
            : 'Your response has been sent.');
        redirect('dashboard');
    }

    public function updateProfile(): void
    {
        $user = require_role('donor');
        verify_csrf();
        if (!array_key_exists('phone', $_POST)) {
            $available = ($_POST['is_available'] ?? '') === '1' ? 1 : 0;
            $this->db->beginTransaction();
            try {
                $current = $this->db->prepare('SELECT is_available FROM donors WHERE user_id = :id FOR UPDATE');
                $current->execute(['id' => $user['id']]);
                $oldAvailability = (bool) $current->fetchColumn();
                $this->db->prepare('UPDATE donors SET is_available = :available WHERE user_id = :id')
                    ->execute(['available' => $available, 'id' => $user['id']]);
                (new AuditService())->record(
                    $user['id'],
                    'donor.availability_changed',
                    'donor',
                    $user['id'],
                    ['is_available' => $oldAvailability],
                    ['is_available' => (bool) $available]
                );
                $this->db->commit();
            } catch (\Throwable $exception) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                throw $exception;
            }
            flash('success', $available ? 'Your availability is on.' : 'Your availability is off. Take the time you need.');
            redirect('dashboard');
        }

        $phone = trim((string) ($_POST['phone'] ?? ''));
        $weight = filter_var($_POST['weight'] ?? null, FILTER_VALIDATE_FLOAT);
        $cityId = filter_var($_POST['city_id'] ?? null, FILTER_VALIDATE_INT);
        $lastDonation = trim((string) ($_POST['last_donation_date'] ?? ''));
        $available = ($_POST['is_available'] ?? '') === '1' ? 1 : 0;
        if (!preg_match('/^03\d{2}-\d{7}$/', $phone) || $weight === false || $weight < 20
            || $weight > 250 || !$cityId || !$this->optionalPastDate($lastDonation)) {
            throw new ValidationException('Enter a valid mobile, weight, city and past last-donation date.');
        }
        $cityCheck = $this->db->prepare('SELECT 1 FROM cities WHERE id = :id');
        $cityCheck->execute(['id' => $cityId]);
        if (!$cityCheck->fetchColumn()) {
            throw new ValidationException('Choose a valid city.');
        }

        $photoPath = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $photoPath = (new UploadService())->save($_FILES['photo'], 'photo');
        }

        $sql = 'UPDATE donors SET phone = :phone, weight = :weight, city_id = :city_id,
                last_donation_date = :last_donation_date, is_available = :available';
        $parameters = [
            'phone' => $phone,
            'weight' => $weight,
            'city_id' => $cityId,
            'last_donation_date' => $lastDonation !== '' ? $lastDonation : null,
            'available' => $available,
            'id' => $user['id'],
        ];
        if ($photoPath !== null) {
            $sql .= ', photo = :photo';
            $parameters['photo'] = $photoPath;
        }
        $sql .= ' WHERE user_id = :id';
        $this->db->beginTransaction();
        try {
            $current = $this->db->prepare('SELECT is_available FROM donors WHERE user_id = :id FOR UPDATE');
            $current->execute(['id' => $user['id']]);
            $oldAvailability = (bool) $current->fetchColumn();
            $this->db->prepare($sql)->execute($parameters);
            (new AuditService())->record(
                $user['id'],
                'donor.profile_updated',
                'donor',
                $user['id'],
                ['is_available' => $oldAvailability],
                ['is_available' => (bool) $available, 'photo_updated' => $photoPath !== null]
            );
            $this->db->commit();
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
        flash('success', 'Your profile has been updated.');
        redirect('dashboard');
    }

    public function viewPhoto(): never
    {
        $user = require_role('donor');
        $statement = $this->db->prepare('SELECT photo FROM donors WHERE user_id = :id');
        $statement->execute(['id' => $user['id']]);
        $relativePath = $statement->fetchColumn();
        $photoDirectory = realpath(APP_ROOT . '/storage/donors');
        $filePath = $relativePath ? realpath(APP_ROOT . '/' . $relativePath) : false;
        if (!$photoDirectory || !$filePath
            || !str_starts_with($filePath, $photoDirectory . DIRECTORY_SEPARATOR)
            || !is_file($filePath)) {
            throw new ValidationException('Profile photo not found.', 404);
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($filePath);
        if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
            throw new ValidationException('Profile photo is not available.', 404);
        }
        header('Content-Type: ' . $mime);
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        readfile($filePath);
        exit;
    }

    public function createRequest(): void
    {
        $user = require_role('hospital');
        verify_csrf();
        $hospital = $this->hospitalProfile($user['id']);
        if (!(new Hospital())->isVerified($hospital)) {
            throw new ValidationException('Hospital verification is required before creating a request.', 403);
        }

        $bloodGroup = (string) ($_POST['blood_group'] ?? '');
        $units = filter_var($_POST['units_needed'] ?? null, FILTER_VALIDATE_INT);
        $urgency = (string) ($_POST['urgency'] ?? '');
        $requiredBy = trim((string) ($_POST['required_by'] ?? ''));
        $ward = trim((string) ($_POST['ward'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));
        if (!in_array($bloodGroup, ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'], true)
            || !$units || $units < 1 || $units > 20
            || !in_array($urgency, ['critical', 'urgent', 'normal'], true)
            || !$this->validDateTime($requiredBy)
            || strtotime($requiredBy) <= time()
            || $ward === '' || strlen($ward) > 100 || strlen($notes) > 1000) {
            throw new ValidationException('Check the blood group, unit count, urgency, future deadline and ward details.');
        }

        $this->db->beginTransaction();
        try {
            $this->db->prepare('SELECT user_id FROM hospitals WHERE user_id = :id FOR UPDATE')
                ->execute(['id' => $user['id']]);
            if ($urgency === 'critical') {
                $count = $this->db->prepare(
                    "SELECT COUNT(*) FROM blood_requests
                     WHERE hospital_id = :id AND urgency = 'critical'
                       AND status IN ('open', 'partially_fulfilled') AND required_by >= NOW()"
                );
                $count->execute(['id' => $user['id']]);
                if ((int) $count->fetchColumn() >= 3) {
                    throw new ValidationException('You can have at most 3 open Critical requests at once.');
                }
            }

            $insert = $this->db->prepare(
                'INSERT INTO blood_requests
                    (hospital_id, blood_group, units_needed, urgency, required_by, ward, notes)
                 VALUES (:hospital_id, :blood_group, :units_needed, :urgency, :required_by, :ward, :notes)'
            );
            $insert->execute([
                'hospital_id' => $user['id'],
                'blood_group' => $bloodGroup,
                'units_needed' => $units,
                'urgency' => $urgency,
                'required_by' => date('Y-m-d H:i:s', strtotime($requiredBy)),
                'ward' => $ward,
                'notes' => $notes !== '' ? $notes : null,
            ]);
            $requestId = (int) $this->db->lastInsertId();
            (new AuditService())->record(
                $user['id'],
                'request.created',
                'blood_request',
                $requestId,
                null,
                ['blood_group' => $bloodGroup, 'units' => $units, 'urgency' => $urgency]
            );
            $this->db->commit();
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }

        $notified = (new MatchingService())->notifyTopDonors($requestId, $user['id']);
        flash('success', "Request published. {$notified} eligible donors have been notified.");
        redirect('dashboard');
    }

    public function requestDonors(): void
    {
        $user = require_role('hospital');
        $requestId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$requestId) {
            throw new ValidationException('Choose a valid request.', 404);
        }
        $request = $this->ownedRequest((int) $requestId, $user['id']);
        $donors = (new MatchingService())->rankedDonors((int) $requestId);
        $matches = $this->db->prepare(
            'SELECT m.id AS match_id, m.status AS match_status, m.score, m.responded_at,
                    u.name, u.id AS donor_id, d.blood_group, d.phone, c.city_name
             FROM request_matches m
             JOIN donors d ON d.user_id = m.donor_id
             JOIN users u ON u.id = d.user_id
             JOIN cities c ON c.id = d.city_id
             WHERE m.request_id = :id
             ORDER BY FIELD(m.status, \'accepted\', \'donated\', \'notified\', \'declined\', \'no_response\'), m.score DESC'
        );
        $matches->execute(['id' => $requestId]);
        $matchedDonors = $matches->fetchAll();
        foreach ($matchedDonors as $match) {
            if (in_array($match['match_status'], ['accepted', 'donated'], true)) {
                (new AuditService())->record(
                    $user['id'],
                    'donor.contact_revealed',
                    'request_match',
                    (int) $match['match_id'],
                    ['phone_visible' => false],
                    ['phone_visible' => true, 'request_id' => (int) $requestId]
                );
            }
        }
        $this->render('hospital/donors', [
            'request' => $request,
            'donors' => $donors,
            'matchedDonors' => $matchedDonors,
        ]);
    }

    public function completeDonation(): void
    {
        $user = require_role('hospital');
        verify_csrf();
        $matchId = filter_var($_POST['match_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$matchId) {
            throw new ValidationException('Choose a valid donation.');
        }

        $this->db->beginTransaction();
        try {
            $requestId = $this->matchRequestId((int) $matchId);
            $request = $this->lockRequest($requestId);
            $match = $this->lockMatch((int) $matchId);
            if ((int) $match['hospital_id'] !== $user['id'] || $match['match_status'] !== 'accepted') {
                throw new ValidationException('Only an accepted donor match belonging to your hospital can be completed.');
            }
            $this->db->prepare(
                "UPDATE request_matches SET status = 'donated', responded_at = NOW() WHERE id = :id"
            )->execute(['id' => $matchId]);
            (new AuditService())->record(
                $user['id'],
                'match.donated',
                'request_match',
                (int) $matchId,
                ['status' => 'accepted'],
                ['status' => 'donated']
            );
            $this->db->prepare(
                'INSERT INTO donations (match_id, units, verified_by) VALUES (:match_id, 1, :verified_by)'
            )->execute(['match_id' => $matchId, 'verified_by' => $user['id']]);
            $donationId = (int) $this->db->lastInsertId();
            $fulfilled = (int) $request['units_fulfilled'] + 1;
            $reserved = max(0, (int) $request['units_reserved'] - 1);
            $status = $request['status'] === 'expired'
                ? 'expired'
                : ($fulfilled >= (int) $request['units_needed']
                    ? 'fulfilled'
                    : (($reserved + $fulfilled) > 0 ? 'partially_fulfilled' : 'open'));
            $this->db->prepare(
                'UPDATE blood_requests SET units_reserved = :reserved, units_fulfilled = :fulfilled, status = :status
                 WHERE id = :id'
            )->execute([
                'reserved' => $reserved,
                'fulfilled' => $fulfilled,
                'status' => $status,
                'id' => $request['id'],
            ]);
            (new AuditService())->record(
                $user['id'],
                'request.fulfillment_updated',
                'blood_request',
                (int) $request['id'],
                [
                    'status' => $request['status'],
                    'units_reserved' => (int) $request['units_reserved'],
                    'units_fulfilled' => (int) $request['units_fulfilled'],
                ],
                ['status' => $status, 'units_reserved' => $reserved, 'units_fulfilled' => $fulfilled]
            );
            $this->db->prepare(
                'INSERT INTO blood_inventory (hospital_id, blood_group, units_available)
                 VALUES (:hospital_id, :blood_group, 1)
                 ON DUPLICATE KEY UPDATE units_available = units_available + 1'
            )->execute(['hospital_id' => $user['id'], 'blood_group' => $request['blood_group']]);
            $this->db->prepare(
                'INSERT INTO notifications (user_id, message) VALUES (:user_id, :message)'
            )->execute([
                'user_id' => $match['donor_id'],
                'message' => 'Your donation has been verified. Thank you for helping save a life.',
            ]);
            (new AuditService())->record(
                $user['id'],
                'donation.completed',
                'donation',
                $donationId,
                ['match_status' => 'accepted'],
                ['match_status' => 'donated', 'units' => 1]
            );
            $this->db->commit();
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }

        flash('success', 'Donation verified and hospital inventory updated.');
        redirect('request_donors', ['id' => (int) $match['request_id']]);
    }

    public function cancelRequest(): void
    {
        $user = require_role('hospital');
        verify_csrf();
        $requestId = filter_var($_POST['request_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$requestId) {
            throw new ValidationException('Choose a valid request.');
        }

        $this->db->beginTransaction();
        try {
            $request = $this->lockRequest((int) $requestId);
            if ((int) $request['hospital_id'] !== $user['id']
                || !in_array($request['status'], ['open', 'partially_fulfilled'], true)) {
                throw new ValidationException('This request cannot be cancelled.');
            }
            $oldStatus = $request['status'];
            $accepted = $this->db->prepare(
                "SELECT COUNT(*) FROM request_matches WHERE request_id = :id AND status = 'accepted'"
            );
            $accepted->execute(['id' => $requestId]);
            $reserved = max(0, (int) $request['units_reserved'] - (int) $accepted->fetchColumn());
            $changedMatches = $this->db->prepare(
                "SELECT id, donor_id, status FROM request_matches
                 WHERE request_id = :id AND status IN ('notified', 'accepted')"
            );
            $changedMatches->execute(['id' => $requestId]);
            $matchesToCancel = $changedMatches->fetchAll();
            $this->db->prepare(
                "UPDATE request_matches SET status = 'cancelled', responded_at = NOW()
                 WHERE request_id = :id AND status IN ('notified', 'accepted')"
            )->execute(['id' => $requestId]);
            foreach ($matchesToCancel as $match) {
                (new AuditService())->record(
                    $user['id'],
                    'match.cancelled',
                    'request_match',
                    (int) $match['id'],
                    ['status' => $match['status']],
                    ['status' => 'cancelled', 'donor_id' => (int) $match['donor_id']]
                );
            }
            $this->db->prepare(
                "UPDATE blood_requests SET status = 'cancelled', units_reserved = :reserved WHERE id = :id"
            )->execute(['reserved' => $reserved, 'id' => $requestId]);
            $this->db->prepare(
                'UPDATE hospitals SET trust_score = GREATEST(0, trust_score - 5) WHERE user_id = :id'
            )->execute(['id' => $user['id']]);
            (new AuditService())->record(
                $user['id'],
                'request.cancelled',
                'blood_request',
                (int) $requestId,
                ['status' => $oldStatus],
                ['status' => 'cancelled', 'trust_penalty' => 5]
            );
            $this->db->commit();
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }

        flash('success', 'Request cancelled. Repeated cancellations reduce hospital trust.');
        redirect('dashboard');
    }

    private function donorDashboard(array $user): void
    {
        $profile = $this->db->prepare(
            'SELECT d.*, c.city_name, TIMESTAMPDIFF(YEAR, d.dob, CURDATE()) AS age
             FROM donors d JOIN cities c ON c.id = d.city_id WHERE d.user_id = :id'
        );
        $profile->execute(['id' => $user['id']]);
        $donor = $profile->fetch();
        $matches = $this->db->prepare(
            'SELECT m.id AS match_id, m.status AS match_status, m.score, m.created_at,
                    r.id AS request_id, r.blood_group, r.urgency, r.units_needed, r.required_by,
                    r.ward, r.status AS request_status, h.hospital_name, c.city_name
             FROM request_matches m
             JOIN blood_requests r ON r.id = m.request_id
             JOIN hospitals h ON h.user_id = r.hospital_id
             JOIN cities c ON c.id = h.city_id
             WHERE m.donor_id = :id ORDER BY m.created_at DESC LIMIT 50'
        );
        $matches->execute(['id' => $user['id']]);
        $history = $this->db->prepare(
            'SELECT dn.donated_on, dn.units, r.blood_group, h.hospital_name
             FROM donations dn
             JOIN request_matches m ON m.id = dn.match_id
             JOIN blood_requests r ON r.id = m.request_id
             JOIN hospitals h ON h.user_id = r.hospital_id
             WHERE m.donor_id = :id ORDER BY dn.donated_on DESC'
        );
        $history->execute(['id' => $user['id']]);
        $donationRows = $history->fetchAll();
        $donationCount = count($donationRows);
        $cooldown = (int) $this->db->query(
            "SELECT setting_value FROM system_settings WHERE setting_key = 'cooldown_days'"
        )->fetchColumn();
        $eligibleDate = null;
        if (!empty($donor['last_donation_date'])) {
            $nextDate = date('Y-m-d', strtotime($donor['last_donation_date'] . " +{$cooldown} days"));
            $eligibleDate = $nextDate > date('Y-m-d') ? $nextDate : null;
        }
        $eligibility = $this->db->prepare('SELECT 1 FROM v_eligible_donors WHERE donor_id = :id');
        $eligibility->execute(['id' => $user['id']]);
        $cities = $this->db->query('SELECT id, city_name, province FROM cities ORDER BY city_name')->fetchAll();
        $this->render('donor/dashboard', [
            'donor' => $donor,
            'matches' => $matches->fetchAll(),
            'history' => $donationRows,
            'donationCount' => $donationCount,
            'eligibleDate' => $eligibleDate,
            'eligibleNow' => (bool) $eligibility->fetchColumn(),
            'cities' => $cities,
        ]);
    }

    private function hospitalDashboard(array $user): void
    {
        $hospital = $this->hospitalProfile($user['id']);
        $requestRows = [];
        $inventory = [];
        if ($hospital['verification_status'] === 'verified') {
            $page = max(1, (int) ($_GET['p'] ?? 1));
            $search = trim((string) ($_GET['q'] ?? ''));
            $urgency = (string) ($_GET['urgency'] ?? '');
            $where = ['r.hospital_id = :hospital_id'];
            $parameters = ['hospital_id' => $user['id']];
            if ($search !== '') {
                $where[] = '(r.blood_group LIKE :search_group OR r.ward LIKE :search_ward)';
                $parameters['search_group'] = '%' . $search . '%';
                $parameters['search_ward'] = '%' . $search . '%';
            }
            if (in_array($urgency, ['critical', 'urgent', 'normal'], true)) {
                $where[] = 'r.urgency = :urgency';
                $parameters['urgency'] = $urgency;
            }
            $predicate = implode(' AND ', $where);
            $count = $this->db->prepare("SELECT COUNT(*) FROM blood_requests r WHERE {$predicate}");
            $count->execute($parameters);
            $total = (int) $count->fetchColumn();
            $page = min($page, max(1, (int) ceil($total / 8)));
            $query = $this->db->prepare(
                "SELECT r.* FROM blood_requests r WHERE {$predicate}
                 ORDER BY FIELD(r.urgency, 'critical', 'urgent', 'normal'), r.required_by ASC
                 LIMIT 8 OFFSET " . (($page - 1) * 8)
            );
            $query->execute($parameters);
            $requestRows = $query->fetchAll();
            $hospital['request_page'] = $page;
            $hospital['request_pages'] = max(1, (int) ceil($total / 8));
            $inventoryQuery = $this->db->prepare(
                'SELECT blood_group, units_available FROM blood_inventory WHERE hospital_id = :id ORDER BY blood_group'
            );
            $inventoryQuery->execute(['id' => $user['id']]);
            $inventory = $inventoryQuery->fetchAll();
        }
        $cities = $this->db->query('SELECT id, city_name, province FROM cities ORDER BY city_name')->fetchAll();
        $this->render('hospital/dashboard', [
            'hospital' => $hospital,
            'requests' => $requestRows,
            'inventory' => $inventory,
            'cities' => $cities,
        ]);
    }

    private function hospitalProfile(int $id): array
    {
        $statement = $this->db->prepare(
            'SELECT h.*, c.city_name, u.name, u.status
             FROM hospitals h JOIN cities c ON c.id = h.city_id JOIN users u ON u.id = h.user_id
             WHERE h.user_id = :id'
        );
        $statement->execute(['id' => $id]);
        $hospital = $statement->fetch();
        if (!$hospital) {
            throw new ValidationException('Hospital profile not found.', 404);
        }

        return $hospital;
    }

    private function ownedRequest(int $requestId, int $hospitalId): array
    {
        $statement = $this->db->prepare(
            'SELECT r.*, h.city_id, c.city_name, h.hospital_name
             FROM blood_requests r
             JOIN hospitals h ON h.user_id = r.hospital_id
             JOIN cities c ON c.id = h.city_id
             WHERE r.id = :request_id AND r.hospital_id = :hospital_id'
        );
        $statement->execute(['request_id' => $requestId, 'hospital_id' => $hospitalId]);
        $request = $statement->fetch();
        if (!$request) {
            throw new ValidationException('Request not found.', 404);
        }

        return $request;
    }

    private function lockRequest(int $requestId): array
    {
        $statement = $this->db->prepare('SELECT * FROM blood_requests WHERE id = :id FOR UPDATE');
        $statement->execute(['id' => $requestId]);
        $request = $statement->fetch();
        if (!$request) {
            throw new ValidationException('Request not found.', 404);
        }

        return $request;
    }

    private function lockMatch(int $matchId): array
    {
        $statement = $this->db->prepare(
            'SELECT m.id, m.request_id, m.donor_id, m.status AS match_status, r.hospital_id, r.blood_group, r.status
             FROM request_matches m JOIN blood_requests r ON r.id = m.request_id
             WHERE m.id = :id FOR UPDATE'
        );
        $statement->execute(['id' => $matchId]);
        $match = $statement->fetch();
        if (!$match) {
            throw new ValidationException('Match not found.', 404);
        }

        return $match;
    }

    private function matchRequestId(int $matchId): int
    {
        $statement = $this->db->prepare('SELECT request_id FROM request_matches WHERE id = :id');
        $statement->execute(['id' => $matchId]);
        $requestId = $statement->fetchColumn();
        if ($requestId === false) {
            throw new ValidationException('Match not found.', 404);
        }

        return (int) $requestId;
    }

    private function validDateTime(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $value);
        return $date !== false && $date->format('Y-m-d\TH:i') === $value;
    }

    private function optionalPastDate(string $value): bool
    {
        if ($value === '') {
            return true;
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value && $value <= date('Y-m-d');
    }
}
