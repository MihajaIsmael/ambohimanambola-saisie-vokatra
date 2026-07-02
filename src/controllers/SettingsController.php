<?php

include_once __DIR__ . '/../../vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $mongoClient = new MongoDB\Client($_ENV['MONGO_URI'] ?? 'mongodb://localhost:27017');
        $collection = $mongoClient->selectDatabase($_ENV['DB_NAME'])->selectCollection('settings');

        // Cleaning and structuring received data
        $settingsData = [
            'country_code' => trim(array_get_default($_POST, 'country_code', '261')),
            'year_code'    => trim(array_get_default($_POST, 'year_code')),
            'event_id'     => trim(array_get_default($_POST, 'event_id')),
            'event_name'   => trim(array_get_default($_POST, 'event_name')),
            'updated_at'   => new MongoDB\BSON\UTCDateTime()
        ];

        // 1. save data settings
        $result = $collection->insertOne($settingsData);

        // 2. Get the new ID
        $insertedId = (string) $result->getInsertedId();

        // 3. Send ID to the form
        header("Location: ../index.php?last_id=" . $insertedId);
        exit;

    } catch (\Throwable $e) {
        die("Error saving settings: " . $e->getMessage());
    }
}