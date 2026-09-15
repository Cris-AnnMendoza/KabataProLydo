<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staffName = trim($_POST['staff_name'] ?? '');
    
    if (!$staffName) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Staff name is required']);
        exit;
    }
    
    // Store in session for this admin session
    $_SESSION['staff_name'] = $staffName;
    
    echo json_encode([
        'success' => true,
        'message' => 'Staff name set successfully',
        'staff_name' => $staffName
    ]);
    exit;
}

// Return current staff name if set
echo json_encode([
    'staff_name' => $_SESSION['staff_name'] ?? null,
    'success' => true
]);
