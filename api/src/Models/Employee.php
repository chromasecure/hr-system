<?php
namespace App\Models;

use PDO;

class Employee {
    public function __construct(private PDO $pdo) {}

    public function allActiveByBranch(int $branchId): array {
        $st = $this->pdo->prepare("SELECT id, employee_code, name, branch_id, face_template_hash FROM employees WHERE branch_id=? AND status='active'");
        $st->execute([$branchId]);
        return $st->fetchAll();
    }

    public function findInBranch(int $empId, int $branchId): ?array {
        $st = $this->pdo->prepare("SELECT * FROM employees WHERE id=? AND branch_id=? AND status='active'");
        $st->execute([$empId, $branchId]);
        return $st->fetch() ?: null;
    }
}
