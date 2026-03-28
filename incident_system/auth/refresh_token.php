<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/jwt_helper.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

$userId = (int) $_SESSION['user_id'];

$stmt = db()->prepare("
    SELECT * FROM refresh_tokens
    WHERE user_id = ? AND expires_at > NOW()
    ORDER BY created_at DESC LIMIT 1
");
$stmt->execute([$userId]);
$tokenRow = $stmt->fetch();

if (!$tokenRow) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Refresh token expired. Please login again.']);
    exit;
}

try {
    JWTHelper::validateToken($tokenRow['token']);
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}

$stmt = db()->prepare("SELECT id, name, email, role FROM users WHERE id = ? AND is_blocked = 0");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'User not found or blocked.']);
    exit;
}

$payload = [
    'sub'   => $user['id'],
    'email' => $user['email'],
    'role'  => $user['role'],
    'name'  => $user['name'],
];
$newAccessToken = JWTHelper::generateAccessToken($payload);
$_SESSION['access_token'] = $newAccessToken;

echo json_encode([
    'status'       => 'success',
    'access_token' => $newAccessToken,
    'expires_in'   => JWT_ACCESS_EXPIRY,
]);
exit;
