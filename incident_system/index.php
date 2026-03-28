<?php

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth_middleware.php';

if (AuthMiddleware::check()) {
    $user = AuthMiddleware::user();

    switch ($user['role']) {
        case ROLE_SUPERADMIN:
            header('Location: ' . BASE_URL . '/superadmin/dashboard.php');
            break;
        case ROLE_ADMIN:
            header('Location: ' . BASE_URL . '/admin/dashboard.php');
            break;
        default:
            header('Location: ' . BASE_URL . '/user/dashboard.php');
            break;
    }
    exit;
}

header('Location: ' . BASE_URL . '/auth/login.php');
exit;