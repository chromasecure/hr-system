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

    public function findByBranchAndName(int $branchId, string $name): ?array {
        $st = $this->pdo->prepare("SELECT * FROM devices WHERE branch_id=? AND name=? LIMIT 1");
        $st->execute([$branchId, $name]);
        return $st->fetch() ?: null;
    }

    public function create(int $branchId, string $name, string $token, string $secret, ?string $ip = null): int {
        $st = $this->pdo->prepare("INSERT INTO devices(branch_id,name,device_token,api_secret,last_ip,last_seen_at,created_at) VALUES (?,?,?,?,?,NOW(),NOW())");
        $st->execute([$branchId, $name, $token, $secret, $ip]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateToken(int $id, string $token, string $secret, ?string $ip = null): void {
        $st = $this->pdo->prepare("UPDATE devices SET device_token=?, api_secret=?, last_ip=?, last_seen_at=NOW() WHERE id=?");
        $st->execute([$token, $secret, $ip, $id]);
    }

    public function updateHeartbeat(int $id, ?string $ip): void {
        $st = $this->pdo->prepare("UPDATE devices SET last_seen_at=NOW(), last_ip=? WHERE id=?");
        $st->execute([$ip, $id]);
    }
}
