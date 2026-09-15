<?php
/**
 * Chatbot API wrapper - forwards requests to wellbeing_ai.php
 * Ensures proper session handling and CORS compatibility
 */
session_start();
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once __DIR__ . '/shared/config.php';

try {
    // Forward the request to wellbeing_ai.php
    include __DIR__ . '/shared/youth/wellbeing_ai.php';
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
    exit;
}
?>
