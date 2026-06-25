<?php
// src/database/getUser.php
header('Content-Type: application/json');

// Include the centralized Database helper class
require_once __DIR__ . '/Database.php';

$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($search)) {
    echo json_encode([]);
    exit;
}

try {
    // Get MongoDB instance
    $db = Database::getDb();

    $searchEscaped = preg_quote($search, '/');

    $filter = [
        'name' => [
            '$regex'   => $searchEscaped,
            '$options' => 'i'
        ]
    ];

    // Limit results
    $options = [
        'limit' => 20
    ];

    // Execute search on "mpivavaka" collection
    $cursor = $db->mpivavaka->find($filter, $options);

    $results = [];
    foreach ($cursor as $document) {
        // Map the result to return 'id' and 'name'
        $results[] = [
            'id'   => (string)$document['id'],
            'name' => $document['name']
        ];
    }

    // Send structured response to frontend
    echo json_encode($results);

} catch (Exception $e) {
    error_log("MongoDB Query Execution Error: " . $e->getMessage());

    header('Content-Type: application/json', true, 500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'An automated database lookup error occurred while searching.'
    ]);
}
