<?php
declare(strict_types=1);

namespace DonorNexa\Core;

abstract class BaseController
{
    protected function render(string $view, array $data = []): void
    {
        $viewFile = APP_ROOT . '/templates/' . $view . '.php';
        if (!is_file($viewFile)) {
            throw new \DonorNexa\Exceptions\ValidationException('The requested page could not be found.', 404);
        }

        extract($data, EXTR_SKIP);
        $pageTitle = [
            'home' => 'Connecting donors. Bridging lives. Saving hope.',
            'auth/login' => 'Sign in',
            'auth/register' => 'Create an account',
            'public/emergencies' => 'Active emergencies',
            'donor/dashboard' => 'My donor dashboard',
            'hospital/dashboard' => 'Hospital dashboard',
            'hospital/donors' => 'Matched donors',
            'admin/dashboard' => 'Admin dashboard',
            'admin/users' => 'People and accounts',
            'admin/audit' => 'Audit trail',
            'admin/cities' => 'Service area',
            'admin/requests' => 'All requests',
        ][$view] ?? 'DonorNexa';
        $flashes = take_flashes();
        $user = current_user();
        require APP_ROOT . '/templates/layout.php';
    }

    protected function fail(string $message, int $status = 422): never
    {
        throw new \DonorNexa\Exceptions\ValidationException($message, $status);
    }
}
