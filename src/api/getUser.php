<?php
// src/api/getUser.php
header('Content-Type: application/json');

$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($search)) {
    echo json_encode([]);
    exit;
}

// Ensure these configuration parameters match your actual environment
$url = "url";
$apiKey = "key";
$idChampNom = 'field_406'; 

// Prepare the verified payload structure accepted by Rukovoditel API
$data = [
    'action'    => 'select',
    'key'       => $apiKey,
    'username'  => "user",
    'password'  => "password",
    'entity_id' => 42, 
    'filters'   => [
        $idChampNom => $search 
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

// Network optimization settings to handle potential routing/timeout issues
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // Time allowed to establish the connection
curl_setopt($ch, CURLOPT_TIMEOUT, 15);        // Total time allowed to execute the cURL request
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Ignore SSL verification issues for local environments

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Error handling: Check if cURL failed to connect or if the remote server failed
if ($response === false || $httpCode !== 200) {
    if ($response === false) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Network error or cURL connection failed: ' . $curlError
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Server responded with an error. HTTP Status: ' . $httpCode . ' Raw output: ' . strip_tags(substr($response, 0, 200))
        ]);
    }
    exit;
}

$result = json_decode($response, true);

// Parse and validate the response structure
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid JSON payload received. Raw response payload: ' . substr($response, 0, 200)
    ]);
    exit;
}

// Process the raw results and format them cleanly for the frontend mapping
if (isset($result['status']) && $result['status'] === 'success') {
    $formattedUsers = [];
    
    if (!empty($result['data'])) {
        foreach ($result['data'] as $item) {
            $formattedUsers[] = [
                'id'   => $item['id'],
                'name' => $item[$idChampNom] ?? 'Unknown Name' 
            ];
        }
    }
    
    echo json_encode($formattedUsers);
} else {
    echo json_encode([
        'status'  => 'error',
        'message' => $result['message'] ?? 'Unknown Rukovoditel API error response structure'
    ]);
}