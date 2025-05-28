<?php
// Set appropriate headers for JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Ensure this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Only POST requests are accepted.']);
    exit;
}

// Get the request body
$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

// Validate the request data
if (!$data || !isset($data['apiKey']) || !isset($data['prompt'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request. API key and prompt are required.']);
    exit;
}

// Extract data from the request
$apiKey = $data['apiKey'];
$requestedModel = isset($data['model']) ? $data['model'] : 'claude-3-haiku-20240307';
$prompt = $data['prompt'];
$enableWebSearch = isset($data['enableWebSearch']) ? $data['enableWebSearch'] : false;
$maxSearches = isset($data['maxSearches']) ? $data['maxSearches'] : 5;
$allowedDomains = isset($data['allowedDomains']) ? $data['allowedDomains'] : null;
$blockedDomains = isset($data['blockedDomains']) ? $data['blockedDomains'] : null;

// Use a fallback model hierarchy - try simpler models first
$modelFallbacks = [
    'claude-3-haiku-20240307',      // Try Haiku first - most likely to work 
    'claude-3-opus-20240229',       // Then Opus
    'claude-3-5-sonnet-20240620',   // Then Sonnet
    'claude-4'                      // Finally try Claude 4 (may work with some API keys)
];

// Make sure the requested model is at the front of the list
if (!in_array($requestedModel, $modelFallbacks)) {
    array_unshift($modelFallbacks, $requestedModel);
}
$modelFallbacks = array_unique($modelFallbacks);

// Prepare the request to Claude API
$claudeEndpoint = 'https://api.anthropic.com/v1/messages';

// Loop through models until one works
$finalResponse = null;
$errors = [];

foreach ($modelFallbacks as $model) {
    // Try with bearer token auth
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
        'anthropic-version: 2023-06-01'
    ];
    
    // Use newer API version for Claude 4 with web search
    if ($model === 'claude-4') {
        $headers[2] = 'anthropic-version: 2023-01-01';  // Update version for Claude 4
        $headers[] = 'anthropic-beta: tools-2023-11-07'; // Add beta access for tools
    }

    // Check if this is a web search request (if message starts with @chat and contains a web search indicator)
    $isWebSearch = false;
    $isDirectChat = false;
    
    // Check if this is a direct chat request that might contain web search
    if (strpos($prompt, '@chat') === 0) {
        $isDirectChat = true;
        // Further check if it's specifically requesting web search
        if (strpos(strtolower($prompt), 'search') !== false || 
            strpos(strtolower($prompt), 'look up') !== false ||
            strpos(strtolower($prompt), 'find information') !== false ||
            strpos(strtolower($prompt), 'latest') !== false ||
            strpos(strtolower($prompt), 'current') !== false ||
            strpos(strtolower($prompt), 'recent') !== false ||
            strpos(strtolower($prompt), 'news') !== false) {
            $isWebSearch = true;
        }
    }
    
    // Create the Claude API request data
    $claudeData = [
        'model' => $model,
        'max_tokens' => 1000,
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ]
    ];
    
    // Add web search tool if enabled
    if ($enableWebSearch) {
        $webSearchTool = [
            'type' => 'web_search_20250305',
            'name' => 'web_search',
            'max_uses' => $maxSearches
        ];
        
        // Add domain restrictions if specified
        if ($allowedDomains && !empty($allowedDomains)) {
            $webSearchTool['allowed_domains'] = $allowedDomains;
        }
        if ($blockedDomains && !empty($blockedDomains)) {
            $webSearchTool['blocked_domains'] = $blockedDomains;
        }
        
        $claudeData['tools'] = [$webSearchTool];
    }

    // Initialize cURL session
    $ch = curl_init($claudeEndpoint);

    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($claudeData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

    // Execute the request
    $response = curl_exec($ch);
    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // If successful, return the response
    if ($httpStatus === 200) {
        $finalResponse = $response;
        break;
    }
    
    // Try with x-api-key auth if bearer token failed
    $headers = [
        'Content-Type: application/json',
        'x-api-key: ' . $apiKey,
        'anthropic-version: 2023-06-01'
    ];
    
    // Use newer API version for Claude 4 with web search
    if ($model === 'claude-4') {
        $headers[2] = 'anthropic-version: 2023-01-01';  // Update version for Claude 4
        $headers[] = 'anthropic-beta: tools-2023-11-07'; // Add beta access for tools
    }
    
    // Create a new Claude data object for the second attempt
    // This is needed because we don't want to reuse the same object if it failed
    $claudeData2 = [
        'model' => $model,
        'max_tokens' => 1000,
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ]
    ];
    
    // Add tool use for web search when requested with Claude 4
    if ($isWebSearch && $model === 'claude-4') {
        $claudeData2['tools'] = [
            [
                'name' => 'web_search',
                'description' => 'Search the web for information'
            ]
        ];
        $claudeData2['tool_choice'] = 'auto';
    }
    
    // Initialize cURL session
    $ch = curl_init($claudeEndpoint);
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($claudeData2));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    
    // Execute the request
    $response = curl_exec($ch);
    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // If successful, return the response
    if ($httpStatus === 200) {
        $finalResponse = $response;
        break;
    }
    
    // Store the error for this model
    $responseData = json_decode($response, true);
    $errors[$model] = [
        'status' => $httpStatus,
        'error' => $responseData['error']['message'] ?? 'Unknown error'
    ];
}

// If we found a working model
if ($finalResponse) {
    echo $finalResponse;
    exit;
}

// If all models failed, return a structured error with helpful information
http_response_code(500);
echo json_encode([
    'error' => 'Failed to call Claude API with any model',
    'details' => $errors,
    'content' => [
        [
            'type' => 'text',
            'text' => "[API ERROR - FALLBACK TRANSLATION]\n\nTranslated message:\n" . substr($prompt, 0, 100) . "...\n\n---\n\nThis is a fallback translation because an error occurred when calling the Claude API. Please check your API key and internet connection, then try again."
        ]
    ]
]);
?>