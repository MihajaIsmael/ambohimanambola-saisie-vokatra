<?php
// src/database/Database.php

class Database 
{
    private static ?PDO $instance = null;

    /**
     * Instantiates and returns a secure PDO database connection instance (Singleton Pattern)
     *
     * @return PDO
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            // Docker internal network configurations
            $host     = getenv('DB_HOST') ?: 'db'; 
            $dbname   = getenv('DB_NAME');
            $username = getenv('DB_ROOT_USER');
            $password = getenv('DB_ROOT_PASSWORD');
            
            try {
                self::$instance = new PDO(
                    "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                    $username,
                    $password,
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false, // True prepared statements for security
                    ]
                );
            } catch (PDOException $e) {
                // Professional safety: Log the real error on the server, but hide credentials from the client
                error_log("Database Connection Failure: " . $e->getMessage());
                
                header('Content-Type: application/json', true, 500);
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'Internal Server Error: Unable to connect to the storage engine.'
                ]);
                exit;
            }
        }

        return self::$instance;
    }
}