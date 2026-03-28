<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

header('Content-Type: application/json');

$user = AuthMiddleware::user();
if (!$user) {
    echo json_encode(['unread_count' => 0, 'new' => []]);
    exit;
}

$action = $_GET['action'] ?? 'poll';

if ($action === 'count') {
    $stmt = db()->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
    $stmt->execute([$user['id']]);
    echo json_encode(['unread_count' => (int)$stmt->fetchColumn()]);
    exit;
}

$since = $_GET['since'] ?? null;

if ($since) {
    $stmt = db()->prepare("
        SELECT n.id, n.message, n.created_at, n.incident_id,
               i.status AS incident_status
        FROM notifications n
        LEFT JOIN incidents i ON n.incident_id = i.id
        WHERE n.user_id = ? AND n.is_read = 0 AND n.created_at > ?
        ORDER BY n.created_at ASC
    ");
    $stmt->execute([$user['id'], date('Y-m-d H:i:s', (int)$since)]);
} else {
    $stmt = db()->prepare("
        SELECT n.id, n.message, n.created_at, n.incident_id,
               i.status AS incident_status
        FROM notifications n
        LEFT JOIN incidents i ON n.incident_id = i.id
        WHERE n.user_id = ? AND n.is_read = 0
        ORDER BY n.created_at ASC
        LIMIT 10
    ");
    $stmt->execute([$user['id']]);
}

$notifications = $stmt->fetchAll();

$cStmt = db()->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
$cStmt->execute([$user['id']]);
$unreadCount = (int)$cStmt->fetchColumn();

$iStmt = db()->prepare("SELECT id, status FROM incidents WHERE user_id=?");
$iStmt->execute([$user['id']]);
$incidentStatuses = $iStmt->fetchAll();

$statStmt = db()->prepare("
    SELECT status, COUNT(*) AS cnt
    FROM incidents
    WHERE user_id = ?
    GROUP BY status
");
$statStmt->execute([$user['id']]);
$statRows = $statStmt->fetchAll();
$statMap  = ['Open' => 0, 'In Progress' => 0, 'Resolved' => 0];
foreach ($statRows as $row) {
    if (isset($statMap[$row['status']])) {
        $statMap[$row['status']] = (int)$row['cnt'];
    }
}

echo json_encode([
    'unread_count'     => $unreadCount,
    'new'              => $notifications,
    'server_time'      => time(),
    'incident_statuses' => $incidentStatuses,
    'stat_counts'      => $statMap,
]);
exit;
