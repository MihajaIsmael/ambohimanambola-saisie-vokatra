<?php

// Include utilities and the dedicated Barcode Service
include_once __DIR__ . '/../services/BarcodeService.php';
include_once __DIR__ . '/../../vendor/autoload.php';

use App\Services\BarcodeService;

// Only allow POST requests to access this script
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../index.php');
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

// 2. Process products input matrix and generate data
$mongoClient = new MongoDB\Client($_ENV['MONGO_URI'] ?? 'mongodb://localhost:27017');
$db = $mongoClient->selectDatabase($_ENV['DB_NAME']);
// Récupérer les données de l'événement spécifique depuis MongoDB
$settingsCollection = $db->selectCollection('settings');
$globalSettings = $settingsCollection->findOne(['_id' => $eventSettingId]);
$eventName = $globalSettings['event_name'];
$eventId = $globalSettings['event_id'];

foreach ($productsInput as $product) {
    $receiptProducts[] = [
        'name' => array_get_default($product, 'name', 'Unknown'),
        'qty'  => (int) array_get_default($product, 'qty', 0),
        'price'  => (float) array_get_default($product, 'price', 0),
    ];

    $generatedLabels = BarcodeService::generateProductLabels($product, $userId, $productCounter, (array)$globalSettings);
    $labelsToPrint = array_merge($labelsToPrint, $generatedLabels);
}

// 3. MongoDB Persistence Layer
if (! empty($labelsToPrint)) {
    try {
        // Establish connection to MongoDB
        $collection = $db->selectCollection($_ENV['COLLECTION_NAME']);

        $documentsToInsert = [];
        foreach ($labelsToPrint as $label) {
            $documentsToInsert[] = [
                'barcode'      => $label['barcode'],      // 13 digits string
                'product_code' => $label['product_code'], // 7 digits string
                'name'         => $label['name'],         // Product label / designation
                'price'        => (float) $label['price'], // Price numeric
                'printed_at'   => new MongoDB\BSON\UTCDateTime(),
                'user_id'      => $userId,
                'user_name'    => $userName,
                'event_id'     => $eventId,
                'event_setting_id' => $eventSettingId,
                'event_name'   => $eventName,
            ];
        }

        // Use 'ordered' => false so MongoDB continues inserting even if a duplicate barcode is hit
        $collection->insertMany($documentsToInsert, ['ordered' => false]);

    } catch (\MongoDB\Exception\BulkWriteException $e) {
        // A BulkWriteException is expected if a barcode already exists due to the unique index.
        // We catch it silently so the user still gets their print view without a crash.
        error_log("Some barcodes were already saved: " . $e->getMessage());
    } catch (\Throwable $e) {
        // General fallback for network or connection issues
        error_log("General MongoDB error: " . $e->getMessage());
    }
}

// 4. Render the view template
include __DIR__ . '/../../views/ticket.php';