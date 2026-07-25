<?php
include_once __DIR__ . '/vendor/autoload.php';

ini_set('display_errors', 1);
error_reporting(E_ERROR);
ini_set("log_errors", 1);
ini_set("error_log", "logs/error.log");

// 1. get the url requested by the user
$request = $_SERVER['REQUEST_URI'];

// 2. clean the url to remove the parameters
$route = strtok($request, '?');

// Check dynamic routes
if (preg_match('#^/vokatra/export/event(?:/([0-9a-fA-F]{24}))?/?$#', $route, $matches)) {

    // The captured integer ID is automatically stored in $matches[1]
    $eventId = isset($matches[1]) && !empty($matches[1]) ? $matches[1] : null;
    include_once 'src/controllers/ExportController.php';

} else {
    // 3. Routing system
    switch ($route) {
        case '/':
            include_once 'src/index.php';
            break;

        case '/vokatra':
            include_once 'src/history.php';
            break;

        case '/settings':
            include_once 'src/controllers/SettingsController.php';
            break;

        case '/print':
            include_once 'src/controllers/PrintController.php';
            break;

        case '/mpivavaka/search':
        case '/mpivavaka/create':
            include_once 'src/controllers/MpivavakaController.php';
            break;

        default:
            http_response_code(404);
            include 'views/error404.php';
            break;
    }
}