<?php

// Include utilities and the dedicated Barcode Service
include_once __DIR__ . '/../services/BarcodeService.php';
include_once __DIR__ . '/../../vendor/autoload.php';

use App\Services\BarcodeService;

function printFail(string $message, int $status = 422)
{
    http_response_code($status);
    echo '<!DOCTYPE html><meta charset="UTF-8"><body style="font-family:sans-serif;padding:20px">'
       . '<h3 style="color:#c0392b">Enregistrement impossible, rien n\'a été imprimé</h3>'
       . '<p>' . htmlspecialchars($message) . '</p>'
       . '<button onclick="window.close()">Fermer</button></body>';
    exit;
}

// Only allow POST requests to access this script
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../');
    exit;
}

// 1. Data retrieval and sanitization
$userId = array_get_default($_POST, 'user_id', 'Unknown');
$userName = array_get_default($_POST, 'user_name', 'Iza ?');
$productsInput = array_get_default($_POST, 'products', []);
$eventSettingId = array_get_default($_POST, 'global_event_setting_id', '');
$eventSettingId = $eventSettingId ? new MongoDB\BSON\ObjectId($eventSettingId) : null;
$date = date('d/m/Y H:i');

$receiptProducts = [];
$labelsToPrint = [];
$itemCounter = 1;
$productCounter = 1;

// 2. Validation côté serveur
if (!ctype_digit((string) $userId) || (int) $userId < 1) {
    printFail('Aucun mpivavaka sélectionné.');
}
if (!$eventSettingId) {
    printFail('Aucun événement sélectionné.');
}
if (!is_array($productsInput) || empty($productsInput)) {
    printFail('Aucun produit saisi.');
}

$totalLabels = 0;
foreach ($productsInput as $product) {
    if (!is_array($product)) {
        printFail('Données produit invalides.');
    }
    $qty = (int) array_get_default($product, 'qty', 0);
    if ($qty < 1) {
        printFail('Quantité invalide.');
    }
    $totalLabels += $qty;
}
if ($totalLabels > BarcodeService::MAX_LABELS_PER_MEMBER) {
    printFail('Trop d\'étiquettes pour une seule saisie (maximum ' . BarcodeService::MAX_LABELS_PER_MEMBER . ').');
}

$mongoClient = new MongoDB\Client($_ENV['MONGO_URI'] ?? 'mongodb://localhost:27017');
$db = $mongoClient->selectDatabase($_ENV['DB_NAME']);

$globalSettings = $db->selectCollection('settings')->findOne(['_id' => $eventSettingId]);
if (!$globalSettings) {
    printFail('Événement introuvable.');
}
$eventName = $globalSettings['event_name'];
$eventId   = $globalSettings['event_id'];

// 3. Réservation atomique des numéros (clé = préfixe du product_code)
try {
    $counterKey     = $eventId . str_pad($userId, 4, '0', STR_PAD_LEFT);
    $productCounter = BarcodeService::reserveSequence($db->selectCollection('counters'), $counterKey, $totalLabels);
} catch (\OverflowException $e) {
    printFail($e->getMessage());
} catch (\Throwable $e) {
    error_log('Counter error: ' . $e->getMessage());
    printFail('Base de données indisponible, réessayez.', 500);
}

// 4. Génération des étiquettes
$receiptProducts = [];
$labelsToPrint   = [];
foreach ($productsInput as $product) {
    $receiptProducts[] = [
        'name'  => array_get_default($product, 'name', 'Unknown'),
        'qty'   => (int) array_get_default($product, 'qty', 0),
        'price' => (float) array_get_default($product, 'price', 0),
    ];
    $labelsToPrint = array_merge(
        $labelsToPrint,
        BarcodeService::generateProductLabels($product, $userId, $productCounter, (array) $globalSettings)
    );
}

// 5. Persistance : si ça échoue, on n'imprime pas
try {
    $collection = $db->selectCollection($_ENV['COLLECTION_NAME']);
    $documentsToInsert = [];
    foreach ($labelsToPrint as $label) {
        $documentsToInsert[] = [
            'barcode'          => $label['barcode'],
            'product_code'     => $label['product_code'],
            'name'             => $label['name'],
            'price'            => (float) $label['price'],
            'printed_at'       => new MongoDB\BSON\UTCDateTime(),
            'user_id'          => $userId,
            'user_name'        => $userName,
            'event_id'         => $eventId,
            'event_setting_id' => $eventSettingId,
            'event_name'       => $eventName,
        ];
    }
    $collection->insertMany($documentsToInsert);
} catch (\Throwable $e) {
    error_log('Insert error: ' . $e->getMessage());
    printFail('Les codes n\'ont pas pu être enregistrés. Réessayez.', 500);
}

// 4. Session Persistence and Pattern POST-Redirect-GET
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pass all necessary view variables into the session payload
$_SESSION['ticket_data'] = [
    'date'            => $date,
    'userName'        => $userName,
    'eventName'       => $eventName,
    'userId'          => $userId,
    'receiptProducts' => $receiptProducts,
    'labelsToPrint'   => $labelsToPrint
];

// CRITICAL: Save and release the session file lock immediately 
// so concurrent requests like history.php can load without waiting.
session_write_close();

// Clear the HTTP POST state by redirecting to the template responder view
header('Location: ../../views/ticket.php');
exit;