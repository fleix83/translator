#!/bin/bash
# Deployment script for translator app on LAMP server

# Create deployment directory
DEPLOY_DIR="./dist"
mkdir -p $DEPLOY_DIR

# Copy frontend files
cp -r ./public/* $DEPLOY_DIR/

# Create api directory
mkdir -p $DEPLOY_DIR/api

# Create PHP API proxy to handle Claude API calls
cat > $DEPLOY_DIR/api/claude.php << 'EOF'
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
EOF

# Create PHP test endpoint
cat > $DEPLOY_DIR/api/test.php << 'EOF'
<?php
header('Content-Type: application/json');
echo json_encode(['status' => 'ok', 'message' => 'Server is running correctly']);
EOF

# Create .htaccess file to handle API routes
cat > $DEPLOY_DIR/.htaccess << 'EOF'
# Enable rewrite engine
RewriteEngine On

# API endpoint for Claude proxy
RewriteRule ^api/claude$ api/claude.php [L]

# API endpoint for test
RewriteRule ^api/test$ api/test.php [L]

# Serve index.html for all other requests (SPA support)
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.html [L]
EOF

# Update frontend code to work with PHP backend
# Uses perl instead of sed for compatibility
perl -i -pe 's|new URL\("/api/claude", window.location.origin\).toString\(\)|"api/claude"|g' $DEPLOY_DIR/index.html
perl -i -pe 's|fetch\("/api/test"\)|fetch("api/test")|g' $DEPLOY_DIR/index.html

echo "Deployment files created in $DEPLOY_DIR directory"
echo "Copy these files to your LAMP server's web directory"
echo "Make sure PHP cURL extension is enabled on your server"
echo "Ensure the .htaccess file is processed (AllowOverride All)"