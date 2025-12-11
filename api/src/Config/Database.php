<?php
namespace App\Config;

use PDO;
use PDOException;

class Database {
    private PDO $pdo;

    public function __construct(array $config) {
        $dsn = sprintf("mysql:host=%s;dbname=%s;charset=%s", $config['host'], $config['name'], $config['charset']);
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        $this->pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
    }

    public function pdo(): PDO {
        return $this->pdo;
    }
}
