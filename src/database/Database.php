<?php
// src/database/Database.php

require_once __DIR__ . '/../../vendor/autoload.php';

class Database
{
    // Singleton for MongoDB client
    private static ?MongoDB\Client $instance = null;

    /**
     * Instancie et retourne une connexion client MongoDB unique (Pattern Singleton)
     * 
     * @return MongoDB\Client
     */
    public static function getConnection(): MongoDB\Client
    {
        if (self::$instance === null) {
            $host = getenv('DB_HOST') ?: 'mongodb';
            $port = getenv('DB_PORT') ?: '27017';

            try {
                $connectionUri = "mongodb://{$host}:{$port}";

                // OPTIMIZATION : Adding performance options for the Driver (Timeout at 3s)
                $driverOptions = [
                    'serverSelectionTimeoutMS' => 3000, 
                    'connectTimeoutMS'         => 3000
                ];

                self::$instance = new MongoDB\Client($connectionUri, [], $driverOptions);

                // Ping validation
                self::$instance->selectDatabase('admin')->command(['ping' => 1]);
            } catch (Exception $e) {
                error_log("MongoDB Connection Failure: " . $e->getMessage());

                // Avoid sending JSON headers if the script is called via CLI (ex: your sync scripts)
                if (php_sapi_name() !== 'cli') {
                    header('Content-Type: application/json', true, 500);
                    echo json_encode([
                        'status'  => 'error',
                        'message' => 'Internal Server Error: Unable to connect to the MongoDB storage engine.'
                    ]);
                    exit(1);

                } else {
                    fwrite(STDERR, "MongoDB Connection Failure: " . $e->getMessage() . "\n");
                    exit(1);
                }
            }
        }

        return self::$instance;
    }

    /**
     * Fast Helper to get the main database
     * 
     * @return MongoDB\Database
     */
    public static function getDb(): MongoDB\Database
    {
        // MAJOR CORRECTION : Replacement of the fallback by ruko-database to match with the .sh script
        $databaseName = getenv('DB_NAME') ?: 'ruko-database';
        return self::getConnection()->selectDatabase($databaseName);
    }
}