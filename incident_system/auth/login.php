<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/jwt_helper.php';
require_once __DIR__ . '/../includes/audit_helper.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

if (AuthMiddleware::check()) {
    $u = AuthMiddleware::user();
    $map = [
        ROLE_SUPERADMIN => BASE_URL . '/superadmin/dashboard.php',
        ROLE_ADMIN      => BASE_URL . '/admin/dashboard.php',
        ROLE_USER       => BASE_URL . '/user/dashboard.php',
    ];
    header('Location: ' . $map[$u['role']] ?? BASE_URL . '/user/dashboard.php');
    exit;
}

$error   = '';
$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Email and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        $stmt = db()->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $error = 'Invalid email or password.';
            AuditHelper::log(
                null,
                'Failed Login Attempt',
                'users',
                null,
                "Email tried: $email"
            );
        } elseif ($user['is_blocked']) {
            $error = 'Your account is blocked. Contact administrator.';
        } else {
            $payload = [
                'sub'   => $user['id'],
                'email' => $user['email'],
                'role'  => $user['role'],
                'name'  => $user['name'],
            ];

            $accessToken  = JWTHelper::generateAccessToken($payload);
            $refreshToken = JWTHelper::generateRefreshToken($payload);

            $exp  = date('Y-m-d H:i:s', time() + JWT_REFRESH_EXPIRY);
            $stmt = db()->prepare("
                INSERT INTO refresh_tokens (user_id, token, expires_at)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$user['id'], $refreshToken, $exp]);

            $_SESSION['access_token'] = $accessToken;
            $_SESSION['user_id']      = $user['id'];
            $_SESSION['user_role']    = $user['role'];
            $_SESSION['user_name']    = $user['name'];

            AuditHelper::log(
                $user['id'],
                'User Login',
                'users',
                $user['id'],
                "{$user['name']} logged in."
            );

            $redirect = match ($user['role']) {
                ROLE_SUPERADMIN => BASE_URL . '/superadmin/dashboard.php',
                ROLE_ADMIN      => BASE_URL . '/admin/dashboard.php',
                default         => BASE_URL . '/user/dashboard.php',
            };
            header("Location: $redirect");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>

<body>
    <div class="login-wrapper">
        <div class="login-card">

            <div class="logo mb-1">
                <i class="fa-solid fa-shield-halved"></i> IRS
            </div>
            <p class="text-center text-muted mb-4" style="font-size:.9rem">
                <?= APP_NAME ?>
            </p>

            <?php if ($error): ?>
                <div class="alert flash-error rounded-3 py-2">
                    <i class="fa fa-circle-exclamation me-2"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert flash-success rounded-3 py-2">
                    <i class="fa fa-circle-check me-2"></i><?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" novalidate>
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fa fa-envelope text-muted"></i></span>
                        <input
                            type="email" name="email"
                            class="form-control"
                            placeholder="you@example.com"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fa fa-lock text-muted"></i></span>
                        <input type="password" name="password" id="passwordField"
                            class="form-control" placeholder="••••••••" required>
                        <button type="button" class="btn btn-light border"
                            onclick="togglePassword()">
                            <i class="fa fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                    <i class="fa fa-right-to-bracket me-2"></i>Sign In
                </button>
            </form>

            <hr class="my-4">
            <p class="text-center text-muted mb-0" style="font-size:.88rem">
                Don't have an account?
                <a href="<?= BASE_URL ?>/auth/register.php" class="text-primary fw-semibold">Register</a>
            </p>

            <div class="mt-3 p-2 rounded" style="background:#f8f9fa;font-size:.78rem;color:#666">
                <strong>Demo:</strong><br>
                superadmin@system.com / Password@123<br>
                admin@system.com &nbsp;&nbsp;&nbsp;/ Password@123<br>
                user@system.com &nbsp;&nbsp;&nbsp;&nbsp;/ Password@123
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const f = document.getElementById('passwordField');
            const i = document.getElementById('eyeIcon');
            if (f.type === 'password') {
                f.type = 'text';
                i.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                f.type = 'password';
                i.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>

</html>