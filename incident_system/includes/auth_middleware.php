<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/jwt_helper.php';

class AuthMiddleware
{

    public static function require(): array
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

        $token = JWTHelper::getBearerToken();

        if (!$token) {
            self::redirectToLogin('Please login to continue.');
        }

        try {
            $payload = JWTHelper::validateToken($token);
        } catch (Exception $e) {
            self::clearSession();
            self::redirectToLogin('Session expired. Please login again.');
        }

        $stmt = db()->prepare("SELECT id, name, email, role, is_blocked FROM users WHERE id = ?");
        $stmt->execute([$payload['sub']]);
        $user = $stmt->fetch();

        if (!$user) {
            self::clearSession();
            self::redirectToLogin('Account not found.');
        }

        if ($user['is_blocked']) {
            self::clearSession();
            self::redirectToLogin('Your account has been blocked. Contact administrator.');
        }

        return $user;
    }

    public static function requireRole(string|array $roles): array
    {
        $user  = self::require();
        $roles = (array) $roles;

        if (!in_array($user['role'], $roles, true)) {
            self::redirectUnauthorized();
        }

        return $user;
    }

    public static function check(): bool
    {
        if (empty($_SESSION['access_token']) || empty($_SESSION['user_id'])) {
            return false;
        }

        $token = $_SESSION['access_token'];
        try {
            JWTHelper::validateToken($token);
            return true;
        } catch (Exception $e) {
            $_SESSION = [];
            return false;
        }
    }

    public static function user(): ?array
    {
        $token = JWTHelper::getBearerToken();
        if (!$token) return null;

        try {
            $payload = JWTHelper::validateToken($token);
            $stmt = db()->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
            $stmt->execute([$payload['sub']]);
            return $stmt->fetch() ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    private static function redirectToLogin(string $message = ''): void
    {
        if ($message) {
            $_SESSION['flash_error'] = $message;
        }
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }

    private static function redirectUnauthorized(): void
    {
        header('Location: ' . BASE_URL . '/auth/unauthorized.php');
        exit;
    }

    private static function clearSession(): void
    {
        unset($_SESSION['access_token']);
        unset($_SESSION['user_id']);
        unset($_SESSION['user_role']);
    }
}
