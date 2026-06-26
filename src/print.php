<?php

// Include utilities and the dedicated Barcode Service
include_once __DIR__ . '/utils.php';
include_once __DIR__ . '/services/BarcodeService.php';

use App\Services\BarcodeService;

// Only allow POST requests to access this script
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

// 1. Data retrieval and sanitization
$userId = array_get_default($_POST, 'user_id', 'Unknown');
$userName = array_get_default($_POST, 'user_name', 'Unknown');
$productsInput = array_get_default($_POST, 'products', []);
$date = date('d/m/Y H:i');

// Hardcoded Event ID for the current session mapping (aligned with your barcode rule)
// TODO For future version, user can make this dynamic
$eventId = "06"; 

$receiptProducts = []; // Stores aggregated data for the global supplier receipt
$labelsToPrint = [];   // Stores individual item data for the price tags pile

// Global sequential counter to ensure structural EAN-13 uniqueness within this batch
$itemCounter = 1;

// 2. Process products input matrix
foreach ($productsInput as $prod) {
    // Populate the global receipt overview array
    $receiptProducts[] = [
        'name' => array_get_default($prod, 'name', 'Unknown'),
        'qty'  => (int)array_get_default($prod, 'qty', 0)
    ];

    // Delegate label generation to the unified BarcodeService architecture
    // This injects both 'product_code' (7 digits) and 'barcode' (13 digits) to each label
    $generatedLabels = BarcodeService::generateProductLabels(
        $prod,
        $eventId,
        $userId,
        $itemCounter
    );
    
    // Merge the newly generated labels into our main printing queue pile
    $labelsToPrint = array_merge($labelsToPrint, $generatedLabels);
}

// 3. Render the view template
// All variables defined above ($userId, $receiptProducts, $labelsToPrint, etc.) are available in the view.
include __DIR__ . '/../views/ticket.php';