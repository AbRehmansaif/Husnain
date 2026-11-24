<?php
declare(strict_types=1);

require dirname(__DIR__) . '/config/app.php';
require APP_ROOT . '/src/Core/Autoloader.php';
\DonorNexa\Core\Autoloader::register();

$logDirectory = APP_ROOT . '/logs';
if (!is_dir($logDirectory)) {
    mkdir($logDirectory, 0750, true);
}
ini_set('log_errors', '1');
ini_set('error_log', $logDirectory . '/error.log');

$page = (string) ($_GET['page'] ?? 'home');
$isApi = isset($_GET['api']);
if ($isApi) {
    define('API_REQUEST', true);
}
try {
    (new \DonorNexa\Services\RememberMeService())->restoreIfNeeded();
    refresh_user_session();
    (new \DonorNexa\Services\RequestExpiryService())->run();

    if ($isApi) {
        require APP_ROOT . '/api/index.php';
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $handler = match ($page) {
            'login' => [new \DonorNexa\Controllers\AuthController(), 'login'],
            'register' => [new \DonorNexa\Controllers\AuthController(), 'register'],
            'logout' => [new \DonorNexa\Controllers\AuthController(), 'logout'],
            'respond' => [new \DonorNexa\Controllers\PortalController(), 'respond'],
            'profile' => [new \DonorNexa\Controllers\PortalController(), 'updateProfile'],
            'create_request' => [new \DonorNexa\Controllers\PortalController(), 'createRequest'],
            'complete_donation' => [new \DonorNexa\Controllers\PortalController(), 'completeDonation'],
            'cancel_request' => [new \DonorNexa\Controllers\PortalController(), 'cancelRequest'],
            'verify_hospital' => [new \DonorNexa\Controllers\AdminController(), 'verifyHospital'],
            'toggle_user' => [new \DonorNexa\Controllers\AdminController(), 'toggleUser'],
            'settings' => [new \DonorNexa\Controllers\AdminController(), 'updateSettings'],
            'add_city' => [new \DonorNexa\Controllers\AdminController(), 'addCity'],
            'update_city' => [new \DonorNexa\Controllers\AdminController(), 'updateCity'],
            default => throw new \DonorNexa\Exceptions\ValidationException('Page not found.', 404),
        };
        $handler();
    }

    match ($page) {
        'home' => (new \DonorNexa\Controllers\HomeController())->index(),
        'login' => (new \DonorNexa\Controllers\AuthController())->loginPage(),
        'register' => (new \DonorNexa\Controllers\AuthController())->registerPage(),
        'dashboard' => (new \DonorNexa\Controllers\PortalController())->dashboard(),
        'emergencies' => (new \DonorNexa\Controllers\HomeController())->emergencies(),
        'request_donors' => (new \DonorNexa\Controllers\PortalController())->requestDonors(),
        'photo' => (new \DonorNexa\Controllers\PortalController())->viewPhoto(),
        'admin' => (new \DonorNexa\Controllers\AdminController())->dashboard(),
        'users' => (new \DonorNexa\Controllers\AdminController())->users(),
        'audit' => (new \DonorNexa\Controllers\AdminController())->audit(),
        'admin_requests' => (new \DonorNexa\Controllers\AdminController())->requests(),
        'cities' => (new \DonorNexa\Controllers\AdminController())->cities(),
        'export' => (new \DonorNexa\Controllers\AdminController())->exportCsv(),
        'licence' => (new \DonorNexa\Controllers\AdminController())->viewLicence(),
        default => throw new \DonorNexa\Exceptions\ValidationException('Page not found.', 404),
    };
} catch (\Throwable $exception) {
    error_log(sprintf(
        '[DonorNexa] %s: %s in %s:%d',
        get_class($exception),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine()
    ));
    $exceptionStatus = $exception->getCode() >= 400 && $exception->getCode() <= 599
        ? $exception->getCode()
        : ($exception instanceof \DonorNexa\Exceptions\ValidationException
            ? 422
            : ($exception instanceof \PDOException ? 503 : 500));
    if ($isApi) {
        http_response_code($exceptionStatus);
        header('Content-Type: application/json; charset=utf-8');
        $message = $exception instanceof \PDOException
            ? 'The database is temporarily unavailable.'
            : $exception->getMessage();
        echo json_encode(['error' => $message]);
        exit;
    }

    $status = $exceptionStatus;
    http_response_code($status);
    $errorMessage = $exception instanceof \PDOException
        ? 'The database could not be reached. Check the setup instructions and try again.'
        : $exception->getMessage();
    $errorCode = $status;
    require APP_ROOT . '/templates/error.php';
}
