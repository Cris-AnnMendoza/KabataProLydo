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

$members = $pdo->prepare('
    SELECT u.id, u.first_name, u.last_name, om.role, u.status
    FROM organization_members om
    JOIN youth_users u ON om.user_id = u.id
    WHERE om.organization_id = ? AND om.is_active = 1
    ORDER BY om.role DESC, u.first_name
');
$members->execute([$orgId]);
$result = $members->fetchAll();

echo json_encode($result);
