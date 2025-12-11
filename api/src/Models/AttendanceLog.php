<?php
namespace App\Models;

use PDO;

class AttendanceLog {
    public function __construct(private PDO $pdo) {}

    public function lastForEmployeeToday(int $empId, string $date): ?array {
        $st = $this->pdo->prepare("SELECT * FROM attendance_logs WHERE employee_id=? AND DATE(marked_at)=? ORDER BY marked_at DESC LIMIT 1");
        $st->execute([$empId, $date]);
        return $st->fetch() ?: null;
    }

    public function recentWithin(int $empId, int $minutes): ?array {
        $st = $this->pdo->prepare("SELECT * FROM attendance_logs WHERE employee_id=? AND marked_at >= (NOW() - INTERVAL ? MINUTE) ORDER BY marked_at DESC LIMIT 1");
        $st->execute([$empId, $minutes]);
        return $st->fetch() ?: null;
    }

    public function insert(array $data): int {
        $st = $this->pdo->prepare("INSERT INTO attendance_logs (employee_id, branch_id, device_id, event_type, marked_at, created_at, source, meta) VALUES (?,?,?,?,?,NOW(),?,?)");
        $st->execute([
            $data['employee_id'], $data['branch_id'], $data['device_id'],
            $data['event_type'], $data['marked_at'], $data['source'] ?? 'device',
            $data['meta'] ? json_encode($data['meta']) : null
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function byBranchDate(int $branchId, string $date): array {
        $st = $this->pdo->prepare("SELECT al.*, e.employee_code, e.name FROM attendance_logs al JOIN employees e ON e.id=al.employee_id WHERE al.branch_id=? AND DATE(al.marked_at)=? ORDER BY al.marked_at ASC");
        $st->execute([$branchId, $date]);
        return $st->fetchAll();
    }
}
