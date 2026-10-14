<?php

include_once __DIR__ . '/../../vendor/autoload.php';

try {
    $mongoClient = new MongoDB\Client($_ENV['MONGO_URI'] ?? 'mongodb://localhost:27017');
    $db = $mongoClient->selectDatabase($_ENV['DB_NAME']);
    $collection = $db->selectCollection($_ENV['COLLECTION_NAME'] ?? 'vokatra');

    // Identifiant fourni par le routeur (index.php) ; absent ou null = export global
    $eventSettingId = (isset($eventId) && is_string($eventId) && preg_match('/^[a-f0-9]{24}$/i', $eventId))
        ? $eventId
        : '';

    $filter = [];
    $filename = "export_vokatra_global_" . date('Ymd_His') . ".csv";

    if ($eventSettingId !== '') {
        $filter['event_setting_id'] = new MongoDB\BSON\ObjectId($eventSettingId);
        $filename = "export_vokatra_event_" . $eventSettingId . "_" . date('Ymd_His') . ".csv";
    }

    // Extraction triée par date d'impression
    $cursor = $collection->find($filter, ['sort' => ['printed_at' => -1]]);

    // Headers configuration to force CSV file download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    // Open PHP standard output stream
    $output = fopen('php://output', 'w');

    // UTF-8 BOM for perfect and automatic opening under Microsoft Excel
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // 1. CSV header (Adapted for Dolibarr import columns)
    fputcsv($output, [
        'Code-barres (p.barcode)',
        'Réf.* (p.ref)',
        'Libellé* (p.label)',
        'Prix de vente HT (p.price_ht)',
        'Prix de vente TTC (p.price_ttc)',
        'Nanome* (extra.nanome)',
        'Produit* (ps.fk_product)',
        'Fotoana (extra.fotoana)',
        'Type* (p.fk_product_type)',
        'En vente* (p.tosell)',
        'En achat* (p.tobuy)',
        'Entrepôt*',
        'Stock* (ps.stock)'
    ], ';');

    // 2. Inject data rows
    foreach ($cursor as $doc) {
        // Convert BSON date to readable format
        $dateStr = '';
        if (isset($doc['printed_at']) && $doc['printed_at'] instanceof MongoDB\BSON\UTCDateTime) {
            $dateStr = $doc['printed_at']->toDateTime()->setTimezone(new DateTimeZone('Indian/Antananarivo'))->format('d/m/Y H:i:s');
        }

        fputcsv($output, [
            $doc['barcode'] ?? '',
            $doc['product_code'] ?? '',
            $doc['name'] ?? '',
            $doc['price'] ?? 0,
            $doc['price'] ?? 0,
            $doc['user_name'] ?? '',
            'type-uniq-id',
            $doc['event_name'] ?? '',
            0,
            1,
            0,
            1,
            1,
        ], ';');
    }

    fclose($output);

    exit;

} catch (\Throwable $e) {
    die("Erreur lors de la génération du CSV : " . $e->getMessage());
}
