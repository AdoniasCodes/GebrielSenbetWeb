<?php

namespace App;

/**
 * Audit::log() — best-effort write to audit_log. Catches all errors so logging
 * never breaks a request (e.g., during the brief window where migration 006
 * has not yet been applied on production).
 */
class Audit
{
    public static function log(string $action, ?string $entityType = null, $entityId = null, array $metadata = []): void
    {
        try {
            $config = app_config();
            $db = new Database($config['db']);
            $pdo = $db->pdo();

            $actor = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
            $ip    = $_SERVER['REMOTE_ADDR'] ?? null;
            $ua    = isset($_SERVER['HTTP_USER_AGENT']) ? substr((string)$_SERVER['HTTP_USER_AGENT'], 0, 255) : null;
            $meta  = !empty($metadata) ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null;

            $stmt = $pdo->prepare(
                'INSERT INTO audit_log (actor_user_id, action, entity_type, entity_id, metadata, ip_addr, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $actor,
                $action,
                $entityType,
                $entityId !== null ? (int)$entityId : null,
                $meta,
                $ip,
                $ua,
            ]);
        } catch (\Throwable $e) {
            // Intentional: never let audit failures break the request.
        }
    }
}
