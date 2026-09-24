<?php
declare(strict_types=1);

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $host = getenv('DB_HOST');
        if (!$host) {
            $host = getenv('RAILWAY_ENVIRONMENT') ? 'mysql.railway.internal' : 'db';
        }
        $port = getenv('DB_PORT') ?: '3306';
        $name = getenv('DB_NAME') ?: 'voting_system';
        $user = getenv('DB_USER') ?: 'vote_user';
        $pass = getenv('DB_PASS') ?: 'vote_pass';

        try {
            $pdo = new PDO(
                "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4",
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            die('Database connection failed.');
        }
    }
    return $pdo;
}