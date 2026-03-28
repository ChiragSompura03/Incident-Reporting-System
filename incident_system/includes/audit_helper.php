<?php

require_once __DIR__ . '/../config/db.php';

class AuditHelper {
    
    public static function log(
        ?int    $userId,
        string  $action,
        string  $targetTable,
        ?int    $targetId    = null,
        ?string $description = null
    ): void {
        try {
            $ip   = self::getClientIP();
            $stmt = db()->prepare("
                INSERT INTO audit_logs
                    (user_id, action, target_table, target_id, description, ip_address, created_at)
                VALUES
                    (:user_id, :action, :target_table, :target_id, :description, :ip, NOW())
            ");
            $stmt->execute([
                ':user_id'      => $userId,
                ':action'       => $action,
                ':target_table' => $targetTable,
                ':target_id'    => $targetId,
                ':description'  => $description,
                ':ip'           => $ip,
            ]);
        } catch (PDOException $e) {
            error_log('AuditHelper error: ' . $e->getMessage());
        }
    }

    public static function getLogs(int $limit = 50, int $offset = 0, array $filters = []): array {
        $where  = [];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[]           = 'al.user_id = :user_id';
            $params[':user_id'] = $filters['user_id'];
        }

        if (!empty($filters['action'])) {
            $where[]          = 'al.action LIKE :action';
            $params[':action'] = '%' . $filters['action'] . '%';
        }

        if (!empty($filters['from'])) {
            $where[]        = 'DATE(al.created_at) >= :from';
            $params[':from'] = $filters['from'];
        }

        if (!empty($filters['to'])) {
            $where[]      = 'DATE(al.created_at) <= :to';
            $params[':to'] = $filters['to'];
        }

        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = db()->prepare("
            SELECT
                al.*,
                u.name  AS user_name,
                u.email AS user_email
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            $whereSQL
            ORDER BY al.created_at DESC
            LIMIT :limit OFFSET :offset
        ");

        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function countLogs(array $filters = []): int {
        $where  = [];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[]           = 'user_id = :user_id';
            $params[':user_id'] = $filters['user_id'];
        }

        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $stmt = db()->prepare("SELECT COUNT(*) FROM audit_logs $whereSQL");
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    private static function getClientIP(): string {
        $keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];

        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }
}