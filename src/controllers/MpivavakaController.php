<?php
// controllers/MpivavakaController.php

// Note: vendor/autoload.php is already included by index.php, 
// but we keep this safely scoped just in case.
include_once __DIR__ . '/../../vendor/autoload.php';

header('Content-Type: application/json');

// Initialize MongoDB Connection securely using environment variables
$mongoClient = new MongoDB\Client($_ENV['MONGO_URI'] ?? 'mongodb://localhost:27017');
$db = $mongoClient->selectDatabase($_ENV['DB_NAME'] ?? 'ruko-database');
$usersCollection = $db->selectCollection($_ENV['COLLECTION_USERS']);

// OPTIMIZATION: Map the clean REST route to the internal controller action (from index.php)
$action = '';
if (isset($route)) {
    if ($route === '/mpivavaka/search') {
        $action = 'search';
    } elseif ($route === '/mpivavaka/create') {
        $action = 'create';
    }
}

try {
    switch ($action) {

        // 🔍 ACTION: SEARCH / AUTOCOMPLETE (Updated to fetch address)
        case 'search':
            $query = trim(array_get_default($_GET, 'q'));
            if (strlen($query) < 2) {
                echo json_encode([]);
                exit;
            }

            // Perform a case-insensitive regex search mapping across user documents
            $searchEscaped = preg_quote($query, '/');
            $filter = [
                'name' => [
                    '$regex'   => $searchEscaped,
                    '$options' => 'i'
                ]
            ];
            $options = [
                'sort' => [
                    'name' => 1
                ], 
                'limit' => 20
            ];
            $cursor = $usersCollection->find($filter, $options);

            $results = [];
            foreach ($cursor as $user) {
                $results[] = [
                    'id'      => (int)$user['id'],
                    'name'    => $user['name'],
                    'address' => $user['address'],
                ];
            }
            echo json_encode($results);
            break;

        // ➕ ACTION: QUICK CREATE WITH ADDRESS
        case 'create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method for execution pipeline.');
            }

            $name = strtoupper(trim(array_get_default($_POST, 'name')));
            $address = array_get_default($_POST, 'address');

            if (empty($name)) {
                throw new Exception('Name payload cannot be empty.');
            }

            // Auto-increment strategy: locate the highest numerical ID assigned
            $highestUser = $usersCollection->findOne([], [
                'sort' => [
                    'id' => -1
                ],
                'projection' => [
                    'id' => 1
                ]
            ]);

            $newId = 1;
            if ($highestUser && isset($highestUser['id'])) {
                $newId = (int)$highestUser['id'] + 1;
            }

            // Insert the new document data entry
            $usersCollection->insertOne([
                'id'         => $newId,
                'name'       => $name,
                'address'    => $address,
                'created_at' => new MongoDB\BSON\UTCDateTime()
            ]);

            echo json_encode([
                'success' => true,
                'id'      => $newId,
                'name'    => $name,
                'address' => $address
            ]);
            break;

        default:
            throw new Exception('Unknown or missing action route parameter logic.');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}