<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../includes/audit_helper.php';

$authUser = AuthMiddleware::requireRole('superadmin');
$id       = (int)($_GET['id'] ?? 0);

if ($id) {
    $stmt = db()->prepare("SELECT title FROM incidents WHERE id=?");
    $stmt->execute([$id]);
    $inc  = $stmt->fetch();

    if ($inc) {
        db()->prepare("DELETE FROM incidents WHERE id=?")->execute([$id]);
        AuditHelper::log($authUser['id'], 'Deleted Incident', 'incidents', $id,
            "Permanently deleted: \"{$inc['title']}\"");
        $_SESSION['flash_success'] = "Incident \"{$inc['title']}\" deleted.";
    }
}
header('Location: ' . BASE_URL . '/admin/incidents.php');
exit;