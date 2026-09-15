<?php
/**
 * AJAX endpoint for wellbeing AI chat
 * Used by popup and mobile versions
 */
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

try {
    error_log('DEBUG: wellbeing_ai.php starting');
    
    // Allow both youth users and organization presidents
    $isYouth = !empty($_SESSION['user_id']);
    $isPresident = !empty($_SESSION['org_president_id']);
    
    error_log("DEBUG: isYouth=$isYouth, isPresident=$isPresident");

    if (!$isYouth && !$isPresident) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    error_log('DEBUG: User authenticated');
    $pdo = db();
    error_log('DEBUG: Database connected');

    // Get user info based on login type
    if ($isPresident) {
        $president = $_SESSION['org_president'];
        $userName = $president['full_name'];
        $userType = 'president';
        $userId = $president['id'];
        $user = $president;
    } else {
        $userId = (int)$_SESSION['user_id'];
        error_log("DEBUG: Youth user ID: $userId");
        $uStmt = $pdo->prepare('SELECT * FROM youth_users WHERE id=? LIMIT 1');
        $uStmt->execute([$userId]);
        $user = $uStmt->fetch();
        if (!$user) {
            error_log("DEBUG: Youth user not found: $userId");
            http_response_code(401);
            echo json_encode(['error' => 'User not found']);
            exit;
        }
        $userName = $user['first_name'] . ' ' . $user['last_name'];
        $userType = 'youth';
        error_log("DEBUG: Youth user loaded: $userName");
    }

    // AJAX: process message
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_chat'])) {
        error_log('DEBUG: AJAX chat request received');
        
        $msg = trim($_POST['message'] ?? '');
        error_log("DEBUG: Message: $msg");
        
        if (!$msg || mb_strlen($msg) > 1000) {
            echo json_encode(['reply' => 'Please send a valid message (max 1000 characters).']);
            exit;
        }

        $firstName = $user['first_name'] ?? $user['full_name'] ?? 'Friend';
        error_log("DEBUG: First name: $firstName");
        
        // For now, return a fallback response while we debug
        $reply = "I'm here to listen and support you, $firstName. It sounds like you might need someone to talk to. I'm available 24/7 to help. What's on your mind? 💙";

        echo json_encode(['reply' => $reply, 'success' => true]);
        exit;
    }
    
    error_log('DEBUG: Not a POST request or missing ajax_chat');
    echo json_encode(['error' => 'Invalid request']);

} catch (Throwable $e) {
    error_log('ERROR in wellbeing_ai.php: ' . $e->getMessage());
    error_log('ERROR trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    exit;
}
