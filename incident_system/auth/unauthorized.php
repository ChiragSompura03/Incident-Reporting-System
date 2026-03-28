<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_middleware.php';
$user = AuthMiddleware::user();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Unauthorized — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f0f2f5;
        }

        .center-box {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .box {
            text-align: center;
            background: #fff;
            padding: 60px 50px;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, .1);
            max-width: 480px;
        }

        .icon {
            font-size: 5rem;
            color: #dc3545;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="center-box">
        <div class="box">
            <div class="icon"><i class="fa-solid fa-ban"></i></div>
            <h2 class="fw-bold mb-2">Access Denied</h2>
            <p class="text-muted mb-4">
                You don't have permission to view this page.<br>
                Please contact your administrator if you believe this is a mistake.
            </p>
            <?php if ($user): ?>
                <?php
                $back = match ($user['role']) {
                    'superadmin' => BASE_URL . '/superadmin/dashboard.php',
                    'admin'      => BASE_URL . '/admin/dashboard.php',
                    default      => BASE_URL . '/user/dashboard.php',
                };
                ?>
                <a href="<?= $back ?>" class="btn btn-primary px-4">
                    <i class="fa fa-house me-2"></i>Back to Dashboard
                </a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-primary px-4">
                    <i class="fa fa-right-to-bracket me-2"></i>Go to Login
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>