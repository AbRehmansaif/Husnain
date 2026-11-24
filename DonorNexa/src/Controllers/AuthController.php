<?php
declare(strict_types=1);

namespace DonorNexa\Controllers;

use DonorNexa\Config\Database;
use DonorNexa\Core\BaseController;
use DonorNexa\Core\Session;
use DonorNexa\Exceptions\ValidationException;
use DonorNexa\Models\User;
use DonorNexa\Repositories\CityRepository;
use DonorNexa\Repositories\UserRepository;
use DonorNexa\Services\AuditService;
use DonorNexa\Services\RememberMeService;
use DonorNexa\Services\UploadService;
use PDO;

final class AuthController extends BaseController
{
    private PDO $db;
    private CityRepository $cities;
    private UserRepository $users;

    public function __construct()
    {
        $this->db = Database::connection();
        $this->cities = new CityRepository();
        $this->users = new UserRepository();
    }

    public function loginPage(): void
    {
        $this->render('auth/login');
    }

    public function login(): void
    {
        verify_csrf();
        $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
        $password = (string) ($_POST['password'] ?? '');
        if (!$email || $password === '') {
            throw new ValidationException('Enter a valid email address and password.');
        }

        $account = $this->users->findByEmail($email);
        if (!$account || !(new User())->verifyPassword($password, $account['password_hash'])) {
            throw new ValidationException('That email and password combination was not found.');
        }
        if ($account['status'] !== 'active' && !($account['role'] === 'hospital' && $account['status'] === 'pending')) {
            throw new ValidationException('This account is currently ' . $account['status'] . '. Contact support if you believe this is a mistake.');
        }

        Session::regenerate();
        $_SESSION['user'] = [
            'id' => (int) $account['id'],
            'name' => $account['name'],
            'email' => $account['email'],
            'role' => $account['role'],
        ];
        if (($_POST['remember'] ?? '') === '1') {
            (new RememberMeService())->remember((int) $account['id']);
        }
        flash('success', 'Welcome back, ' . $account['name'] . '.');
        redirect('dashboard');
    }

    public function registerPage(): void
    {
        $this->render('auth/register', ['cities' => $this->cities->all()]);
    }

    public function register(): void
    {
        verify_csrf();
        $role = (string) ($_POST['role'] ?? '');
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
        $password = (string) ($_POST['password'] ?? '');
        $cityId = filter_var($_POST['city_id'] ?? null, FILTER_VALIDATE_INT);
        if (!in_array($role, ['donor', 'hospital'], true) || $name === '' || strlen($name) > 120 || !$email) {
            throw new ValidationException('Complete the required fields with valid information.');
        }
        if (strlen($password) < 10 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            throw new ValidationException('Use a password of at least 10 characters with letters and numbers.');
        }
        if (!$cityId || !$this->cityExists($cityId)) {
            throw new ValidationException('Choose a valid city.');
        }
        if ($role === 'donor') {
            $this->validateDonorRegistration();
        } else {
            $this->validateHospitalRegistration();
        }
        $this->assertEmailAvailable($email);

        $uploadPath = null;
        if ($role === 'hospital' && isset($_FILES['licence_file']) && $_FILES['licence_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadPath = (new UploadService())->save($_FILES['licence_file'], 'licence');
        }

        $this->db->beginTransaction();
        try {
            $status = $role === 'hospital' ? 'pending' : 'active';
            $createUser = $this->db->prepare(
                'INSERT INTO users (name, email, password_hash, role, status)
                 VALUES (:name, :email, :password_hash, :role, :status)'
            );
            $createUser->execute([
                'name' => $name,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => $role,
                'status' => $status,
            ]);
            $userId = (int) $this->db->lastInsertId();

            if ($role === 'donor') {
                $createProfile = $this->db->prepare(
                    'INSERT INTO donors (user_id, blood_group, dob, weight, city_id, phone, cnic, last_donation_date)
                     VALUES (:user_id, :blood_group, :dob, :weight, :city_id, :phone, :cnic, :last_donation_date)'
                );
                $createProfile->execute([
                    'user_id' => $userId,
                    'blood_group' => $_POST['blood_group'],
                    'dob' => $_POST['dob'],
                    'weight' => $_POST['weight'],
                    'city_id' => $cityId,
                    'phone' => trim((string) $_POST['phone']),
                    'cnic' => trim((string) $_POST['cnic']),
                    'last_donation_date' => trim((string) ($_POST['last_donation_date'] ?? '')) ?: null,
                ]);
            } else {
                $createProfile = $this->db->prepare(
                    'INSERT INTO hospitals
                        (user_id, hospital_name, licence_no, licence_file, city_id, contact_phone)
                     VALUES (:user_id, :hospital_name, :licence_no, :licence_file, :city_id, :contact_phone)'
                );
                $createProfile->execute([
                    'user_id' => $userId,
                    'hospital_name' => trim((string) $_POST['hospital_name']),
                    'licence_no' => trim((string) $_POST['licence_no']),
                    'licence_file' => $uploadPath,
                    'city_id' => $cityId,
                    'contact_phone' => trim((string) $_POST['phone']),
                ]);
            }

            (new AuditService())->record(
                $userId,
                'account.registered',
                'user',
                $userId,
                null,
                ['role' => $role, 'status' => $status]
            );
            $this->db->commit();
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            if ($exception instanceof \PDOException && $exception->getCode() === '23000') {
                throw new ValidationException('That email, CNIC, or licence number is already registered.');
            }
            throw $exception;
        }

        flash('success', $role === 'hospital'
            ? 'Your hospital profile is submitted. An administrator will verify your licence before requests are enabled.'
            : 'Your donor account is ready. Sign in to finish setting your availability.');
        redirect('login');
    }

    public function logout(): void
    {
        verify_csrf();
        (new RememberMeService())->forget();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $parameters = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $parameters['path'], '', $parameters['secure'], $parameters['httponly']);
        }
        session_destroy();
        redirect('home');
    }

    private function validateDonorRegistration(): void
    {
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $cnic = trim((string) ($_POST['cnic'] ?? ''));
        $dob = trim((string) ($_POST['dob'] ?? ''));
        $weight = filter_var($_POST['weight'] ?? null, FILTER_VALIDATE_FLOAT);
        $groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

        if (!in_array($_POST['blood_group'] ?? '', $groups, true)
            || !preg_match('/^03\d{2}-\d{7}$/', $phone)
            || !preg_match('/^\d{5}-\d{7}-\d$/', $cnic)
            || $weight === false || $weight < 50 || $weight > 250
            || !$this->validDate($dob)
            || $dob > (new \DateTimeImmutable('-18 years'))->format('Y-m-d')
            || $dob < (new \DateTimeImmutable('-60 years'))->format('Y-m-d')
            || !$this->optionalPastDate(trim((string) ($_POST['last_donation_date'] ?? '')))) {
            throw new ValidationException('Check your blood group, age (18–60), weight (at least 50 kg), last donation date, mobile (03XX-XXXXXXX) and CNIC (#####-#######-#).');
        }
    }

    private function validateHospitalRegistration(): void
    {
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $hospitalName = trim((string) ($_POST['hospital_name'] ?? ''));
        $licence = trim((string) ($_POST['licence_no'] ?? ''));
        if ($hospitalName === '' || strlen($hospitalName) > 160 || $licence === '' || strlen($licence) > 80
            || !preg_match('/^03\d{2}-\d{7}$/', $phone)) {
            throw new ValidationException('Enter a hospital name, licence number and valid mobile (03XX-XXXXXXX).');
        }
        if (isset($_FILES['licence_file']) && $_FILES['licence_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            return;
        }
        throw new ValidationException('A PDF hospital licence document is required.');
    }

    private function validDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function optionalPastDate(string $value): bool
    {
        return $value === ''
            || ($this->validDate($value) && $value <= date('Y-m-d'));
    }

    private function cityExists(int $cityId): bool
    {
        return $this->cities->find($cityId) !== null;
    }

    private function assertEmailAvailable(string $email): void
    {
        if ($this->users->findByEmail($email) !== null) {
            throw new ValidationException('An account already uses that email address.');
        }
    }
}
