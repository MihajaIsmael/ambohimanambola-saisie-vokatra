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
    // Acquire the single professional PDO connection instance
    $pdo = Database::getConnection();

    // Prepare an optimized SQL query leveraging the name index
    $stmt = $pdo->prepare("SELECT id, name FROM mpivavaka WHERE name LIKE :search LIMIT 20");
    
    // Explicit binding prevents structural SQL Injection vulnerabilities
    $stmt->execute(['search' => '%' . $search . '%']);
    $results = $stmt->fetchAll();

    // Standard structural response mapping for the client frontend
    echo json_encode($results);

} catch (PDOException $e) {
    error_log("Query Execution Error: " . $e->getMessage());
    
    header('Content-Type: application/json', true, 500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'An automated database lookup error occurred while searching.'
    ]);
}