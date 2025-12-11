<?php
namespace App\Models;

use PDO;

class Employee {
    public function __construct(private PDO $pdo) {}

    public function allActiveByBranch(int $branchId): array {
        $st = $this->pdo->prepare("SELECT id, employee_code, name, branch_id, face_template_hash, face_image_path FROM employees WHERE branch_id=? AND status='active' ORDER BY name ASC");
        $st->execute([$branchId]);
        return $st->fetchAll();
    }

    public function findInBranch(int $empId, int $branchId): ?array {
        $st = $this->pdo->prepare("SELECT * FROM employees WHERE id=? AND branch_id=? AND status='active'");
        $st->execute([$empId, $branchId]);
        return $st->fetch() ?: null;
    }

    public function findByCodeInBranch(string $code, int $branchId): ?array {
        $st = $this->pdo->prepare("SELECT * FROM employees WHERE employee_code=? AND branch_id=? AND status='active' LIMIT 1");
        $st->execute([$code, $branchId]);
        return $st->fetch() ?: null;
    }

    public function updateFacePath(int $id, string $path): void {
        $st = $this->pdo->prepare("UPDATE employees SET face_image_path=?, updated_at=NOW() WHERE id=?");
        $st->execute([$path, $id]);
    }
}
