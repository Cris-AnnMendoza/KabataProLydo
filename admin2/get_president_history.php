<?php
require_once 'config.php';
requireLogin();

header('Content-Type: application/json');

$orgId = (int)($_GET['org_id'] ?? 0);
if (!$orgId) {
    http_response_code(400);
    echo json_encode(['error' => 'Organization ID required']);
    exit;
}

$pdo = db();

$history = $pdo->prepare('
    SELECT 
        h.id,
        CONCAT(u.first_name, " ", u.last_name) as president_name,
        u.email,
        h.started_at,
        h.ended_at,
        h.reason,
        CONCAT(a.full_name) as changed_by
    FROM organization_president_history h
    JOIN youth_users u ON h.president_id = u.id
    LEFT JOIN admin_users a ON h.changed_by = a.id
    WHERE h.organization_id = ?
    ORDER BY h.started_at DESC
');
$history->execute([$orgId]);
$result = $history->fetchAll();

echo json_encode($result);
