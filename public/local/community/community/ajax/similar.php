<?php
require('../../../config.php');
require_login();

header('Content-Type: application/json');

$q = required_param('q', PARAM_TEXT);

// FastAPI endpoint URL (adjust to your server/port)
$fastapi_url = 'http://127.0.0.1:8000/similar?q=' . urlencode($q);

// Call FastAPI
$options = [
    'http' => [
        'method' => 'GET',
        'header' => "Accept: application/json\r\n"
    ]
];
$context = stream_context_create($options);
$response = @file_get_contents($fastapi_url, false, $context);

if ($response === false) {
    echo json_encode(['error' => 'FastAPI service unavailable']);
    exit;
}

// Decode FastAPI response
$data = json_decode($response, true);

// Ensure we return a proper array
if (!is_array($data)) {
    echo json_encode(['error' => 'Invalid response from FastAPI']);
    exit;
}

echo json_encode($data);
