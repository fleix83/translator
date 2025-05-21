<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get POST data
$requestData = json_decode(file_get_contents('php://input'), true);

// Validate API key
if (empty($requestData['apiKey'])) {
    http_response_code(400);
    echo json_encode(['error' => 'API key is required']);
    exit;
}

// Set up cURL request to Claude API
$ch = curl_init('https://api.anthropic.com/v1/messages');

// Prepare request data
$modelName = !empty($requestData['model']) ? $requestData['model'] : 'claude-3-7-sonnet-20250219';
$postData = [
    'model' => $modelName,
    'max_tokens' => 1000,
    'messages' => [
        ['role' => 'user', 'content' => $requestData['prompt']]
    ]
];

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'x-api-key: ' . $requestData['apiKey'],
    'anthropic-version: 2023-06-01'
]);

// Execute cURL request
$response = curl_exec($ch);
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Return response with same status code
http_response_code($statusCode);
echo $response;
