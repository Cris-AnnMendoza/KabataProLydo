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
            $userName = $user['first_name'];
        }
    } else {
        $president = $_SESSION['org_president'] ?? [];
        $userName = $president['full_name'] ?? 'Friend';
    }
    
    // Try to get AI response from Groq
    $reply = callGroqAI($message, $userName);
    
    // If API fails, use helpful wellbeing response templates
    if (!$reply) {
        $reply = generateWellbeingResponse($message, $userName);
    }
    
    echo json_encode(['reply' => $reply, 'success' => true]);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
    exit;
}

/**
 * Generate helpful wellbeing responses based on keywords
 */
function generateWellbeingResponse($message, $userName) {
    $msg = strtolower($message);
    
    // Dizziness/vertigo responses
    if (strpos($msg, 'dizzy') !== false || strpos($msg, 'dizziness') !== false || strpos($msg, 'vertigo') !== false) {
        return "I hear you, $userName. Dizziness can be uncomfortable. Try sitting or lying down for a moment, and breathe slowly. Stay hydrated and avoid sudden movements. If it persists, please consult a healthcare provider. How are you feeling now?";
    }
    
    // Stress/anxiety
    if (strpos($msg, 'stress') !== false || strpos($msg, 'anxious') !== false || strpos($msg, 'anxiety') !== false || strpos($msg, 'worried') !== false) {
        return "It's okay to feel stressed, $userName. Try some deep breathing: inhale for 4 counts, hold for 4, exhale for 4. Take breaks, move your body, or talk to someone you trust. You're not alone. What's causing the most stress right now?";
    }
    
    // Tired/exhausted
    if (strpos($msg, 'tired') !== false || strpos($msg, 'exhausted') !== false || strpos($msg, 'fatigue') !== false || strpos($msg, 'sleepy') !== false) {
        return "Rest is important, $userName. Make sure you're getting 7-9 hours of sleep. If you're consistently tired, it might help to check your sleep schedule and reduce screen time before bed. Is there something keeping you from resting well?";
    }
    
    // Sad/depressed
    if (strpos($msg, 'sad') !== false || strpos($msg, 'depressed') !== false || strpos($msg, 'unhappy') !== false || strpos($msg, 'down') !== false) {
        return "I'm sorry you're feeling down, $userName. It's okay to have these feelings. Consider doing something you enjoy, spending time with loved ones, or getting outside. If sadness persists, reaching out to a counselor can really help. What would make you feel better right now?";
    }
    
    // Headache/pain
    if (strpos($msg, 'headache') !== false || strpos($msg, 'head') !== false || strpos($msg, 'pain') !== false) {
        return "Sorry to hear you're in pain, $userName. Try resting in a quiet, dark place. Stay hydrated and avoid screens if possible. If headaches are frequent, consult a healthcare provider. Take care of yourself. What else can help?";
    }
    
    // Generic supportive response
    return "Thank you for sharing, $userName. I'm here to listen and support you. Remember that taking care of your mental health is just as important as your physical health. What would help you feel better right now?";
}

/**
 * Call Groq API for wellbeing assistant response
 */
function callGroqAI($userMessage, $userName) {
    $apiKey = getenv('GROQ_API_KEY');
    
    // Try different env var names
    if (!$apiKey) {
        $apiKey = getenv('AI_API_KEY');
    }
    
    if (!$apiKey) {
        return null; // No API key configured
    }
    
    $systemPrompt = "You are LYDO, a compassionate wellbeing assistant for Filipino youth. Provide supportive, empathetic responses. Keep it to 1-2 sentences max. Never diagnose - suggest professional help when needed. Use their name.";
    
    $payload = [
        'model' => 'mixtral-8x7b-32768',
        'messages' => [
            [
                'role' => 'system',
                'content' => $systemPrompt
            ],
            [
                'role' => 'user',
                'content' => "$userName says: $userMessage"
            ]
        ],
        'max_tokens' => 300,
        'temperature' => 0.7
    ];
    
    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
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
