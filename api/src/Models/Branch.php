<?php
namespace App\Models;

use PDO;

class Branch {
    public function __construct(private PDO $pdo) {}

    public function findByCode(string $code): ?array {
        $st = $this->pdo->prepare("SELECT * FROM branches WHERE code=?");
        $st->execute([$code]);
        return $st->fetch() ?: null;
    }

    public function all(): array {
        return $this->pdo->query("SELECT * FROM branches ORDER BY name")->fetchAll();
    }
}
