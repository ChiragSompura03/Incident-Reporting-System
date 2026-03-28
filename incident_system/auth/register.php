<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/audit_helper.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

if (AuthMiddleware::check()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error   = '';
$success = '';
$input   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input['name']     = trim($_POST['name']     ?? '');
    $input['email']    = trim($_POST['email']    ?? '');
    $input['password'] = trim($_POST['password'] ?? '');
    $input['confirm']  = trim($_POST['confirm']  ?? '');

    if (!$input['name'] || !$input['email'] || !$input['password'] || !$input['confirm']) {
        $error = 'All fields are required.';
    } elseif (strlen($input['name']) < 2 || strlen($input['name']) > 100) {
        $error = 'Name must be between 2 and 100 characters.';
    } elseif (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($input['password']) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif (!preg_match('/[A-Z]/', $input['password'])) {
        $error = 'Password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[0-9]/', $input['password'])) {
        $error = 'Password must contain at least one number.';
    } elseif ($input['password'] !== $input['confirm']) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = db()->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$input['email']]);
        if ($stmt->fetch()) {
            $error = 'Email is already registered. Please login.';
        } else {
            $hash = password_hash($input['password'], PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = db()->prepare("
                INSERT INTO users (name, email, password, role)
                VALUES (?, ?, ?, 'user')
            ");
            $stmt->execute([$input['name'], $input['email'], $hash]);
            $newId = (int) db()->lastInsertId();

            AuditHelper::log(
                $newId,
                'User Registered',
                'users',
                $newId,
                "{$input['name']} created a new account."
            );

            $_SESSION['flash_success'] = 'Account created! You can now login.';
            header('Location: ' . BASE_URL . '/auth/login.php');
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
    <title>Register — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>

<body>
    <div class="login-wrapper">
        <div class="login-card" style="width:460px">

            <div class="logo mb-1">
                <i class="fa-solid fa-shield-halved"></i> IRS
            </div>
            <p class="text-center text-muted mb-4" style="font-size:.9rem">Create your account</p>

            <?php if ($error): ?>
                <div class="alert flash-error rounded-3 py-2">
                    <i class="fa fa-circle-exclamation me-2"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate id="regForm">

                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fa fa-user text-muted"></i></span>
                        <input type="text" name="name" class="form-control"
                            placeholder="John Doe"
                            value="<?= htmlspecialchars($input['name'] ?? '') ?>"
                            required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fa fa-envelope text-muted"></i></span>
                        <input type="email" name="email" class="form-control"
                            placeholder="you@example.com"
                            value="<?= htmlspecialchars($input['email'] ?? '') ?>"
                            required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fa fa-lock text-muted"></i></span>
                        <input type="password" name="password" id="pw1"
                            class="form-control" placeholder="Min 8 chars, 1 uppercase, 1 number"
                            oninput="checkStrength(this.value)" required>
                        <button type="button" class="btn btn-light border" onclick="toggle('pw1','e1')">
                            <i class="fa fa-eye" id="e1"></i>
                        </button>
                    </div>
                    <div class="mt-1">
                        <div class="progress" style="height:4px">
                            <div id="strengthBar" class="progress-bar" style="width:0%"></div>
                        </div>
                        <small id="strengthText" class="text-muted"></small>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fa fa-lock text-muted"></i></span>
                        <input type="password" name="confirm" id="pw2"
                            class="form-control" placeholder="Re-enter password" required>
                        <button type="button" class="btn btn-light border" onclick="toggle('pw2','e2')">
                            <i class="fa fa-eye" id="e2"></i>
                        </button>
                    </div>
                    <small id="matchMsg" class="mt-1 d-block"></small>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                    <i class="fa fa-user-plus me-2"></i>Create Account
                </button>
            </form>

            <hr class="my-3">
            <p class="text-center text-muted mb-0" style="font-size:.88rem">
                Already have an account?
                <a href="<?= BASE_URL ?>/auth/login.php" class="text-primary fw-semibold">Login</a>
            </p>
        </div>
    </div>

    <script>
        function toggle(fieldId, iconId) {
            const f = document.getElementById(fieldId);
            const i = document.getElementById(iconId);
            f.type = f.type === 'password' ? 'text' : 'password';
            i.classList.toggle('fa-eye');
            i.classList.toggle('fa-eye-slash');
        }

        function checkStrength(val) {
            const bar = document.getElementById('strengthBar');
            const text = document.getElementById('strengthText');
            let score = 0;
            if (val.length >= 8) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            const levels = [{
                    pct: '0%',
                    cls: '',
                    label: ''
                },
                {
                    pct: '25%',
                    cls: 'bg-danger',
                    label: 'Weak'
                },
                {
                    pct: '50%',
                    cls: 'bg-warning',
                    label: 'Fair'
                },
                {
                    pct: '75%',
                    cls: 'bg-info',
                    label: 'Good'
                },
                {
                    pct: '100%',
                    cls: 'bg-success',
                    label: 'Strong'
                },
            ];
            bar.style.width = levels[score].pct;
            bar.className = 'progress-bar ' + levels[score].cls;
            text.textContent = levels[score].label;
            text.style.color = score < 2 ? 'red' : score < 4 ? 'orange' : 'green';
        }

        document.getElementById('pw2').addEventListener('input', function() {
            const msg = document.getElementById('matchMsg');
            const match = this.value === document.getElementById('pw1').value;
            msg.textContent = this.value ? (match ? '✔ Passwords match' : '✖ Passwords do not match') : '';
            msg.style.color = match ? 'green' : 'red';
        });
    </script>
</body>

</html>