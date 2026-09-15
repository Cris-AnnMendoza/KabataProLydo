<?php
/**
 * Chatbot API endpoint - handles wellbeing chat requests
 */
session_start();
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

require_once __DIR__ . '/shared/config.php';

try {
    // Check authentication
    $isYouth = !empty($_SESSION['user_id']);
    $isPresident = !empty($_SESSION['org_president_id']);
    
    if (!$isYouth && !$isPresident) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    // Only handle POST requests with ajax_chat flag
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['ajax_chat'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request']);
        exit;
    }
    
    $message = trim($_POST['message'] ?? '');
    if (!$message || strlen($message) > 1000) {
        echo json_encode(['reply' => 'Please send a valid message (max 1000 characters).']);
        exit;
    }
    
    // Get user info
    $pdo = db();
    $userName = 'Friend';
    
    if ($isYouth) {
        $userId = (int)$_SESSION['user_id'];
        $stmt = $pdo->prepare('SELECT first_name, last_name FROM youth_users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if ($user) {
            $userName = $user['first_name'] . ' ' . $user['last_name'];
        }
    } else {
        $president = $_SESSION['org_president'] ?? [];
        $userName = $president['full_name'] ?? 'Friend';
    }
    
    // Generate response
    $replies = [
        "I'm here to listen and support you, $userName. It sounds like you might need someone to talk to. I'm available 24/7 to help. What's on your mind? 💙",
        "Thank you for sharing with me, $userName. Your feelings are valid and important. How can I best support you right now?",
        "I appreciate your trust, $userName. Let's work through this together. What would help you most right now?",
        "You're not alone in this, $userName. I'm here to listen without judgment. What's bothering you?",
        "Your wellbeing matters, $userName. I'm here to support you. Tell me more about what you're experiencing."
    ];
    
    $reply = $replies[array_rand($replies)];
    
    echo json_encode(['reply' => $reply, 'success' => true]);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
    exit;
}
?>
