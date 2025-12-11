<?php
namespace App\Models;

use PDO;

class PendingEmployee {
    public function __construct(private PDO $pdo) {}

    public function create(array $data): int {
        $st = $this->pdo->prepare("INSERT INTO pending_employees (employee_code,name,branch_id,designation_id,contact,basic_salary,commission,joining_date,face_image_path,created_by_user_id,status,created_at) VALUES (?,?,?,?,?,?,?,?,NULL,?,'pending',NOW())");
        $st->execute([
            $data['employee_code'],
            $data['name'],
            $data['branch_id'],
            $data['designation_id'],
            $data['contact'],
            $data['basic_salary'],
            $data['commission'],
            $data['joining_date'],
            $data['created_by'],
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateFacePath(int $id, string $path): void {
        $st = $this->pdo->prepare("UPDATE pending_employees SET face_image_path=? WHERE id=?");
        $st->execute([$path, $id]);
    }

    public function all(): array {
        $sql = "SELECT p.*, b.name AS branch_name FROM pending_employees p LEFT JOIN branches b ON b.id=p.branch_id WHERE p.status='pending' ORDER BY p.created_at DESC";
        return $this->pdo->query($sql)->fetchAll();
    }

    public function find(int $id): ?array {
        $st = $this->pdo->prepare("SELECT * FROM pending_employees WHERE id=?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function delete(int $id): void {
        $st = $this->pdo->prepare("DELETE FROM pending_employees WHERE id=?");
        $st->execute([$id]);
    }
}
