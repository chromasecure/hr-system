<?php
namespace App\Models;

use PDO;

class User {
    public function __construct(private PDO $pdo) {}

    public function findByEmail(string $email): ?array
{
    $stmt = $this->pdo->prepare(
        'SELECT id, name, email, password_hash, role, branch_id, is_active
         FROM users
         WHERE email = :email
         LIMIT 1'
    );
    $stmt->execute(['email' => $email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}


    public function findById(int $id): ?array {
        $st = $this->pdo->prepare("SELECT * FROM users WHERE id=?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function countAll(): int {
        $st = $this->pdo->query("SELECT COUNT(*) AS c FROM users");
        $row = $st->fetch();
        return (int)($row['c'] ?? 0);
    }

    public function create(string $name, string $email, string $passwordHash, string $role, ?int $branchId = null): int {
        $st = $this->pdo->prepare("INSERT INTO users (name,email,password_hash,role,branch_id,is_active) VALUES (?,?,?,?,?,1)");
        $st->execute([$name, $email, $passwordHash, $role, $branchId]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updatePassword(int $id, string $passwordHash): void {
        $st = $this->pdo->prepare("UPDATE users SET password_hash=?, updated_at=NOW() WHERE id=?");
        $st->execute([$passwordHash, $id]);
    }
}
