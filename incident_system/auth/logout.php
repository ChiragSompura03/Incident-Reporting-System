<?php

if (session_status() === PHP_SESSION_NONE) {
    session_name('incident_sess');
    session_start();
}

try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/db.php';

    if (!empty($_SESSION['user_id'])) {
        $uid  = (int)$_SESSION['user_id'];
        $name = $_SESSION['user_name'] ?? 'Unknown';

        db()->prepare("DELETE FROM refresh_tokens WHERE user_id = ?")
            ->execute([$uid]);

        db()->prepare("
            INSERT INTO audit_logs
                (user_id, action, target_table, target_id, description, ip_address, created_at)
            VALUES (?, 'User Logout', 'users', ?, ?, ?, NOW())
        ")->execute([$uid, $uid, "$name logged out.", $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0']);
    }
} catch (Exception $e) {
    error_log("Logout error: " . $e->getMessage());
}

$_SESSION = [];

$cookieName = session_name();
$cookieParams = session_get_cookie_params();

setcookie($cookieName, '', [
    'expires'  => time() - 3600,
    'path'     => $cookieParams['path']     ?: '/',
    'domain'   => $cookieParams['domain']   ?: '',
    'secure'   => $cookieParams['secure']   ?? false,
    'httponly' => $cookieParams['httponly'] ?? true,
    'samesite' => 'Strict',
]);

setcookie($cookieName, '', time() - 3600, '/');

session_regenerate_id(true);
session_destroy();

unset($_COOKIE[$cookieName]);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

$host     = $_SERVER['HTTP_HOST'];
$script   = $_SERVER['PHP_SELF'];
$base     = str_replace('/auth/logout.php', '', $script);
$loginUrl = 'http://' . $host . $base . '/auth/login.php';

header('Location: ' . $loginUrl);
exit;
