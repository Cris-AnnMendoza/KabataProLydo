<?php
require_once __DIR__ . '/shared/config.php';

header('Content-Type: application/json');

$pdo = db();
$type = $_GET['type'] ?? 'accredited'; // 'accredited' or 'all'

try {
    if ($type === 'accredited') {
        // Only active, accredited organizations
        // Remove is_active = 1 check since not all orgs have this field set
        $orgs = $pdo->query('
            SELECT id, name, category, barangay, adviser_name
            FROM organizations
            WHERE accreditation_status = "active"
            ORDER BY name
        ')->fetchAll();
    } else {
        // All organizations for youth member selection
        $orgs = $pdo->query('
            SELECT id, name, category, barangay, accreditation_status
            FROM organizations
            ORDER BY accreditation_status DESC, name
        ')->fetchAll();
    }
    
    echo json_encode(['success' => true, 'organizations' => $orgs ?: []]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

