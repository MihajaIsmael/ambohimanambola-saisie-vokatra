<?php
// controllers/MpivavakaController.php

include_once __DIR__ . '/../../vendor/autoload.php';

header('Content-Type: application/json');

// Initialize MongoDB Connection securely using environment variables
$mongoClient = new MongoDB\Client($_ENV['MONGO_URI'] ?? 'mongodb://localhost:27017');
$db = $mongoClient->selectDatabase($_ENV['DB_NAME'] ?? 'ruko-database');
$usersCollection = $db->selectCollection($_ENV['COLLECTION_USERS']);

// Route requests based on the designated 'action' parameter
$action = array_get_default($_REQUEST, 'action');

try {
    switch ($action) {

        // 🔍 ACTION: SEARCH / AUTOCOMPLETE
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
                    'id'   => (int)$user['id'],
                    'name' => $user['name']
                ];
            }
            echo json_encode($results);
            break;

        // ➕ ACTION: QUICK CREATE WITH AUTO-INCREMENT
        case 'create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method for execution pipeline.');
            }

            $name = strtoupper(trim(array_get_default($_POST, 'name')));
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

            $newId = 1; // Fallback index if collection is empty
            if ($highestUser && isset($highestUser['id'])) {
                $newId = (int)$highestUser['id'] + 1;
            }

            // Insert the new document data entry
            $usersCollection->insertOne([
                'id'         => $newId,
                'name'       => $name,
                'created_at' => new MongoDB\BSON\UTCDateTime()
            ]);

            echo json_encode([
                'success' => true,
                'id'      => $newId,
                'name'    => $name
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