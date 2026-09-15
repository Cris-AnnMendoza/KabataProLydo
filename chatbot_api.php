<?php
/**
 * Chatbot API endpoint - handles wellbeing chat requests with Groq AI
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
    
    // Try to get AI response from Groq
    $reply = callGroqAI($message, $userName);
    
    if (!$reply) {
        // Fallback if API fails
        $reply = "I'm here to listen and support you, $userName. I'm having trouble processing that right now. Please try again or reach out to our support team.";
    }
    
    echo json_encode(['reply' => $reply, 'success' => true]);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
    exit;
}

/**
 * Call Groq API for wellbeing assistant response
 */
function callGroqAI($userMessage, $userName) {
    $apiKey = getenv('GROQ_API_KEY');
    
    // Try different env var names that might be set
    if (!$apiKey) {
        $apiKey = getenv('AI_API_KEY');
    }
    
    if (!$apiKey) {
        return null; // No API key configured
    }
    
    $systemPrompt = "You are LYDO, a compassionate wellbeing assistant for Filipino youth. You provide supportive, empathetic responses to mental health and wellness concerns. Keep responses concise (2-3 sentences max), friendly, and encouraging. Never provide medical diagnosis - suggest professional help when appropriate. Use the person's name when responding. Always respond in English or Tagalog as appropriate.";
    
    $payload = [
        'model' => 'mixtral-8x7b-32768',
        'messages' => [
            [
                'role' => 'system',
                'content' => $systemPrompt
            ],
            [
                'role' => 'user',
                'content' => "User name: $userName\n\nUser: $userMessage"
            ]
        ],
        'max_tokens' => 500,
        'temperature' => 0.7
    ];
    
    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || !$response) {
        return null;
    }
    
    $data = json_decode($response, true);
    
    if (isset($data['choices'][0]['message']['content'])) {
        return trim($data['choices'][0]['message']['content']);
    }
    
    return null;
}
?>
