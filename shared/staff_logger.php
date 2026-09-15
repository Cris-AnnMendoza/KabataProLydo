<?php
/**
 * Staff Activity Logger
 * Tracks which staff member made what changes
 */

function logStaffActivity($pdo, $admin_id, $staff_name, $action, $entity_type = null, $entity_id = null, $details = null) {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        
        $stmt = $pdo->prepare('
            INSERT INTO staff_activity_log (admin_id, staff_name, action, entity_type, entity_id, details, ip_address)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            $admin_id,
            $staff_name,
            $action,
            $entity_type,
            $entity_id,
            $details,
            $ip
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log('Staff activity logging error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get staff name from session or request
 */
function getStaffName($pdo) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    $staffName = $_SESSION['staff_name'] ?? null;
    
    if (!$staffName) {
        $staffName = $_POST['staff_name'] ?? $_GET['staff_name'] ?? 'Unknown Staff';
    }
    
    return trim($staffName);
}

/**
 * Get activity log for an entity
 */
function getActivityLog($pdo, $entity_type, $entity_id) {
    $stmt = $pdo->prepare('
        SELECT * FROM staff_activity_log
        WHERE entity_type = ? AND entity_id = ?
        ORDER BY created_at DESC
        LIMIT 20
    ');
    
    $stmt->execute([$entity_type, $entity_id]);
    return $stmt->fetchAll();
}

/**
 * Get recent staff activities
 */
function getRecentActivities($pdo, $limit = 50) {
    $stmt = $pdo->query('
        SELECT * FROM staff_activity_log
        ORDER BY created_at DESC
        LIMIT ' . intval($limit)
    );
    
    return $stmt->fetchAll();
}
