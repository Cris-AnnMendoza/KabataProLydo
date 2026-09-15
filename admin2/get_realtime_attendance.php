<?php
/**
 * Real-time Attendance API
 * Returns current check-ins for an event
 */
error_reporting(0);
ini_set('display_errors', 0);

require_once 'config.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

$eventId = (int)($_GET['event_id'] ?? 0);

if (!$eventId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Event ID required']);
    exit;
}

try {
    $pdo = db();

    // Get attendance list
    $stmt = $pdo->prepare('
        SELECT 
            y.id, 
            y.first_name, 
            y.last_name, 
            y.organization_name,
            c.checked_in_at, 
            c.checked_out_at,
            o.id as org_id
        FROM event_checkins c
        JOIN youth_users y ON y.id = c.user_id
        LEFT JOIN organizations o ON o.name = y.organization_name
        WHERE c.event_id = ?
        ORDER BY c.checked_in_at DESC
    ');
    $stmt->execute([$eventId]);
    $rows = $stmt->fetchAll();

    // Format attendance data
    $attendees = [];
    foreach ($rows as $row) {
        $attendees[] = [
            'id' => (int)$row['id'],
            'name' => trim($row['first_name'] . ' ' . $row['last_name']),
            'organization' => $row['organization_name'] ?: 'No Organization',
            'checked_in_at' => $row['checked_in_at'],
            'checked_in_time' => date('g:i A', strtotime($row['checked_in_at'])),
            'checked_out_at' => $row['checked_out_at'],
            'checked_out_time' => $row['checked_out_at'] ? date('g:i A', strtotime($row['checked_out_at'])) : null,
        ];
    }

    // Get totals
    $checkins = count($rows);
    $checkouts = count(array_filter($rows, function($r) { return !empty($r['checked_out_at']); }));
    $orgs = count(array_unique(array_filter(array_column($rows, 'org_id'))));

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'attendees' => $attendees,
        'totals' => [
            'checkins' => $checkins,
            'checkouts' => $checkouts,
            'orgs' => $orgs
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error'
    ]);
}
