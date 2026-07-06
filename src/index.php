<?php
// index.php
include_once __DIR__ . '/../vendor/autoload.php';

$mongoClient = new MongoDB\Client($_ENV['MONGO_URI'] ?? 'mongodb://localhost:27017');
$settingsCollection = $mongoClient->selectDatabase($_ENV['DB_NAME'])->selectCollection($_ENV['COLLECTION_SETTINGS']);
$vokatraCollection = $mongoClient->selectDatabase($_ENV['DB_NAME'])->selectCollection($_ENV['COLLECTION_NAME']);
$subscriberCollection = $mongoClient->selectDatabase($_ENV['DB_NAME'])->selectCollection($_ENV['COLLECTION_USERS']);

// 1. Get default ID sent by SettingsController.php
$selectedEventId = array_get_default($_GET, 'last_id', '');

// Set default to last event if ID is empty
if (empty($selectedEventId)) {
    $latestEvent = $settingsCollection->findOne([], ['sort' => ['updated_at' => -1]]);
    if ($latestEvent) {
        $selectedEventId = (string) $latestEvent['_id'];
    }
}

$allEvents = $settingsCollection->find([], ['sort' => ['event_name' => 1]]);

// Fetch only the 5 latest printed records for the live sidebar widget
$sidebarLimit = 5;
$latestScansCursor = $vokatraCollection->find([], [
    'sort'  => ['printed_at' => -1],
    'limit' => $sidebarLimit
]);
$latestScans = $latestScansCursor->toArray();

// Fetch only newer mpivavaka with created_at field
$newSubscriberCursor = $subscriberCollection->find(
    ['created_at' => ['$exists' => true]],
    [
        'sort'  => ['created_at' => -1],
        'limit' => $sidebarLimit
    ]
);
$newSubscriber = $newSubscriberCursor->toArray();

// 🚀 RENDER PIPELINE: Load the isolated presentation template layer
include_once __DIR__ . '/../views/entry.form.php';