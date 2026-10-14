<?php

include_once __DIR__ . '/../../vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /vokatra');
    exit;
}

function reprintFail(string $message, int $status = 422)
{
    http_response_code($status);
    echo '<!DOCTYPE html><meta charset="UTF-8"><body style="font-family:sans-serif;padding:20px">'
       . '<h3 style="color:#c0392b">Réimpression impossible</h3>'
       . '<p>' . htmlspecialchars($message) . '</p>'
       . '<button onclick="window.close()">Fermer</button></body>';
    exit;
}

// 1. Validation de la sélection
$rawIds = $_POST['ids'] ?? [];
if (!is_array($rawIds) || empty($rawIds)) {
    reprintFail('Aucun code sélectionné.');
}
if (count($rawIds) > 200) {
    reprintFail('Maximum 200 étiquettes par réimpression.');
}

$objectIds = [];
foreach ($rawIds as $id) {
    if (!is_string($id) || !preg_match('/^[a-f0-9]{24}$/i', $id)) {
        reprintFail('Sélection invalide.');
    }
    $objectIds[] = new MongoDB\BSON\ObjectId($id);
}

// 2. Lecture des étiquettes existantes + trace de la réimpression
try {
    $mongoClient = new MongoDB\Client($_ENV['MONGO_URI'] ?? 'mongodb://localhost:27017');
    $collection  = $mongoClient->selectDatabase($_ENV['DB_NAME'])
                               ->selectCollection($_ENV['COLLECTION_NAME'] ?? 'vokatra');

    $filter = ['_id' => ['$in' => $objectIds]];
    $labelsToPrint = $collection->find($filter, ['sort' => ['product_code' => 1]])->toArray();

    if (empty($labelsToPrint)) {
        reprintFail('Aucune étiquette trouvée.', 404);
    }

    $collection->updateMany($filter, [
        '$inc' => ['reprint_count' => 1],
        '$set' => ['last_reprinted_at' => new MongoDB\BSON\UTCDateTime()],
    ]);
} catch (\Throwable $e) {
    error_log('Reprint error: ' . $e->getMessage());
    reprintFail('Base de données indisponible, réessayez.', 500);
}

// 3. Rendu direct (pas de session, pas de redirection)
include __DIR__ . '/../../views/reprint.php';
exit;