<?php
/**
 * Direct Groq API Test Script
 * Tests Groq API connection without database dependencies
 * Outputs comprehensive debugging information
 */

echo "=== Groq API Direct Connection Test ===\n\n";

// Step 1: Get API Key from environment
echo "Step 1: Retrieving API Key\n";
echo "-----------------------------------\n";

$api_key = null;

// Try getenv() first
if (function_exists('getenv')) {
    $api_key = getenv('GROQ_API_KEY');
    echo "getenv('GROQ_API_KEY'): " . ($api_key ? "Found (length: " . strlen($api_key) . ")" : "Not found") . "\n";
}

// Try $_ENV if not found
if (!$api_key && isset($_ENV['GROQ_API_KEY'])) {
    $api_key = $_ENV['GROQ_API_KEY'];
    echo "\$_ENV['GROQ_API_KEY']: Found (length: " . strlen($api_key) . ")\n";
} elseif (!$api_key) {
    echo "\$_ENV['GROQ_API_KEY']: Not found\n";
}

// Try $_SERVER as fallback
if (!$api_key && isset($_SERVER['GROQ_API_KEY'])) {
    $api_key = $_SERVER['GROQ_API_KEY'];
    echo "\$_SERVER['GROQ_API_KEY']: Found (length: " . strlen($api_key) . ")\n";
} elseif (!$api_key) {
    echo "\$_SERVER['GROQ_API_KEY']: Not found\n";
}

echo "\nAPI Key Status: " . ($api_key ? "✓ Ready" : "✗ NOT FOUND") . "\n\n";

if (!$api_key) {
    echo "ERROR: GROQ_API_KEY not found in environment variables!\n";
    echo "Please set it using:\n";
    echo "  export GROQ_API_KEY='your-key-here'\n";
    echo "  OR in your .env file\n";
    exit(1);
}

// Step 2: Prepare API request
echo "Step 2: Preparing API Request\n";
echo "-----------------------------------\n";

$url = "https://api.groq.com/openai/v1/chat/completions";
echo "URL: $url\n";

$payload = [
    "model" => "mixtral-8x7b-32768",
    "messages" => [
        [
            "role" => "user",
            "content" => "Hello"
        ]
    ],
    "max_tokens" => 100,
    "temperature" => 0.7
];

echo "Model: " . $payload['model'] . "\n";
echo "Message: " . $payload['messages'][0]['content'] . "\n";
echo "Max Tokens: " . $payload['max_tokens'] . "\n";
echo "Temperature: " . $payload['temperature'] . "\n\n";

// Step 3: Make API call
echo "Step 3: Making API Request\n";
echo "-----------------------------------\n";

$headers = [
    "Content-Type: application/json",
    "Authorization: Bearer " . $api_key
];

echo "Headers sent:\n";
foreach ($headers as $header) {
    if (strpos($header, 'Bearer') !== false) {
        echo "  Authorization: Bearer [HIDDEN]\n";
    } else {
        echo "  $header\n";
    }
}
echo "\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

// Capture response headers
$response_headers = [];
curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$response_headers) {
    $len = strlen($header);
    $header = explode(':', $header, 2);
    if (count($header) < 2) return $len;
    $name = strtolower(trim($header[0]));
    $value = trim($header[1]);
    $response_headers[$name] = $value;
    return $len;
});

echo "Sending request...\n";
$start_time = microtime(true);
$response = curl_exec($ch);
$elapsed = microtime(true) - $start_time;

$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_errno = curl_errno($ch);
$curl_error = curl_error($ch);

curl_close($ch);

echo "Request completed in " . number_format($elapsed, 3) . " seconds\n\n";

// Step 4: Show results
echo "Step 4: Response Analysis\n";
echo "-----------------------------------\n";

echo "HTTP Status Code: " . $http_code . "\n";
echo "Curl Error Code: " . ($curl_errno ? $curl_errno : "None") . "\n";
echo "Curl Error Message: " . ($curl_error ? $curl_error : "None") . "\n\n";

echo "Response Headers:\n";
if (!empty($response_headers)) {
    foreach ($response_headers as $name => $value) {
        if (strlen($value) > 100) {
            echo "  $name: " . substr($value, 0, 100) . "...\n";
        } else {
            echo "  $name: $value\n";
        }
    }
} else {
    echo "  (No headers captured)\n";
}
echo "\n";

echo "Response Body:\n";
echo "-----------------------------------\n";

if ($response) {
    $decoded = json_decode($response, true);
    if ($decoded) {
        echo json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    } else {
        echo $response . "\n";
    }
} else {
    echo "(Empty response)\n";
}

echo "\n";

// Step 5: Determine success/failure
echo "Step 5: Result Summary\n";
echo "-----------------------------------\n";

if ($curl_errno) {
    echo "✗ FAILED - cURL Error: $curl_error\n";
    exit(1);
} elseif ($http_code >= 200 && $http_code < 300) {
    echo "✓ SUCCESS - API connection successful!\n";
    $decoded = json_decode($response, true);
    if ($decoded && isset($decoded['choices'][0]['message']['content'])) {
        echo "Response Message: " . $decoded['choices'][0]['message']['content'] . "\n";
    }
    exit(0);
} elseif ($http_code >= 400) {
    echo "✗ FAILED - HTTP Error $http_code\n";
    $decoded = json_decode($response, true);
    if ($decoded) {
        if (isset($decoded['error'])) {
            echo "Error: " . $decoded['error']['message'] . "\n";
        } else {
            echo "Response: " . json_encode($decoded) . "\n";
        }
    }
    exit(1);
} else {
    echo "⚠ UNKNOWN - HTTP Code $http_code (unexpected)\n";
    exit(1);
}
?>
