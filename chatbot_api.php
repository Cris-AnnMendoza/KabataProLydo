<?php
/**
 * Chatbot API endpoint - calls real AI for responses
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
            $userName = $user['first_name'];
        }
    } else {
        $president = $_SESSION['org_president'] ?? [];
        $userName = $president['full_name'] ?? 'Friend';
    }
    
    // Call actual AI
    $reply = callAI($message, $userName);
    
    if (!$reply) {
        $reply = "I'm here to listen. Could you tell me more about that?";
    }
    
    echo json_encode(['reply' => $reply, 'success' => true]);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
    exit;
}

/**
 * Call AI API for actual intelligent response
 */
function callAI($userMessage, $userName) {
    // Try Groq first (if configured)
    $reply = callGroqAI($userMessage, $userName);
    if ($reply) return $reply;
    
    // Try OpenRouter (free tier available)
    $reply = callOpenRouterAI($userMessage, $userName);
    if ($reply) return $reply;
    
    return null;
}

function callGroqAI($message, $userName) {
    $apiKey = getenv('GROQ_API_KEY') ?: getenv('AI_API_KEY');
    if (!$apiKey) return null;
    
    $payload = [
        'model' => 'mixtral-8x7b-32768',
        'messages' => [
            ['role' => 'system', 'content' => "You are a supportive wellbeing assistant for Filipino youth. Keep responses brief (1-2 sentences). Be empathetic and helpful."],
            ['role' => 'user', 'content' => $message]
        ],
        'max_tokens' => 300,
        'temperature' => 0.7
    ];
    
    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);
    
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($code === 200 && $response) {
        $data = json_decode($response, true);
        if (isset($data['choices'][0]['message']['content'])) {
            return trim($data['choices'][0]['message']['content']);
        }
    }
    return null;
}

function callOpenRouterAI($message, $userName) {
    $apiKey = getenv('OPENROUTER_API_KEY');
    if (!$apiKey) $apiKey = 'free-tier'; // OpenRouter allows some free calls
    
    $payload = [
        'model' => 'google/gemini-2.0-flash-lite:free',
        'messages' => [
            ['role' => 'system', 'content' => "You are LYDO, a supportive wellbeing assistant for Filipino youth. Give brief, helpful responses (2-3 sentences max). Be warm and empathetic."],
            ['role' => 'user', 'content' => $message]
        ],
        'temperature' => 0.7
    ];
    
    $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
            'X-Title: LYDO Wellbeing'
        ],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);
    
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($code === 200 && $response) {
        $data = json_decode($response, true);
        if (isset($data['choices'][0]['message']['content'])) {
            return trim($data['choices'][0]['message']['content']);
        }
    }
    return null;
}
?>
