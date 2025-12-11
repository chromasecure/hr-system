<?php
namespace App\Models;

use PDO;

class Device {
    public function __construct(private PDO $pdo) {}

    public function findByToken(string $token): ?array {
        $st = $this->pdo->prepare("SELECT * FROM devices WHERE device_token=?");
        $st->execute([$token]);
        return $st->fetch() ?: null;
    }

    public function create(int $branchId, string $name, string $token, string $secret): int {
        $st = $this->pdo->prepare("INSERT INTO devices(branch_id,name,device_token,api_secret) VALUES (?,?,?,?)");
        $st->execute([$branchId, $name, $token, $secret]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateHeartbeat(int $id, ?string $ip): void {
        $st = $this->pdo->prepare("UPDATE devices SET last_seen_at=NOW(), last_ip=? WHERE id=?");
        $st->execute([$ip, $id]);
    }
}
