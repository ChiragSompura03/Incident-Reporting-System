<?php

require_once __DIR__ . '/../config/db.php';

class NotificationHelper
{

    public static function send(int $userId, int $incidentId, string $message): void
    {
        try {
            $stmt = db()->prepare("
                INSERT INTO notifications (user_id, incident_id, message, is_read, created_at)
                VALUES (:user_id, :incident_id, :message, 0, NOW())
            ");
            $stmt->execute([
                ':user_id'     => $userId,
                ':incident_id' => $incidentId,
                ':message'     => $message,
            ]);
        } catch (PDOException $e) {
            error_log('NotificationHelper error: ' . $e->getMessage());
        }
    }

    public static function getUnread(int $userId): array
    {
        $stmt = db()->prepare("
            SELECT n.*, i.title AS incident_title
            FROM notifications n
            JOIN incidents i ON n.incident_id = i.id
            WHERE n.user_id = :user_id AND n.is_read = 0
            ORDER BY n.created_at DESC
        ");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public static function getAll(int $userId, int $limit = 20): array
    {
        $stmt = db()->prepare("
            SELECT n.*, i.title AS incident_title
            FROM notifications n
            JOIN incidents i ON n.incident_id = i.id
            WHERE n.user_id = :user_id
            ORDER BY n.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit',   $limit,  PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function countUnread(int $userId): int
    {
        $stmt = db()->prepare("
            SELECT COUNT(*) FROM notifications
            WHERE user_id = :user_id AND is_read = 0
        ");
        $stmt->execute([':user_id' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public static function markRead(int $notificationId, int $userId): void
    {
        $stmt = db()->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE id = :id AND user_id = :user_id
        ");
        $stmt->execute([':id' => $notificationId, ':user_id' => $userId]);
    }

    public static function markAllRead(int $userId): void
    {
        $stmt = db()->prepare("
            UPDATE notifications SET is_read = 1 WHERE user_id = :user_id
        ");
        $stmt->execute([':user_id' => $userId]);
    }

    public static function jsonUnreadCount(int $userId): void
    {
        $count = self::countUnread($userId);
        header('Content-Type: application/json');
        echo json_encode(['unread_count' => $count]);
        exit;
    }
}
