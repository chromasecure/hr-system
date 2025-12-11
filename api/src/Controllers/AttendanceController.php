<?php
namespace App\Controllers;

use App\Helpers\Response;
use App\Models\AttendanceLog;
use App\Models\Employee;
use PDO;

class AttendanceController {
    public function __construct(private PDO $pdo, private array $config) {}

    private function normalizeEvent(?array $lastToday, string $incoming): string {
        if ($incoming !== 'auto') return $incoming;
        if (!$lastToday) return 'check_in';
        return $lastToday['event_type'] === 'check_in' ? 'check_out' : 'check_in';
    }

    private function insertOne(array $device, array $log, AttendanceLog $repo, Employee $empRepo): array {
        $emp = $empRepo->findInBranch((int)$log['employee_id'], (int)$device['branch_id']);
        if (!$emp) return ['status' => 'error', 'message' => 'Employee not in branch'];

        $recent = $repo->recentWithin((int)$log['employee_id'], $this->config['recent_window_minutes']);
        if ($recent) return ['status' => 'skipped', 'reason' => 'already_marked_recently'];

        $markedDate = date('Y-m-d', strtotime($log['captured_at']));
        $lastToday = $repo->lastForEmployeeToday((int)$log['employee_id'], $markedDate);
        $event = $this->normalizeEvent($lastToday, $log['event_type'] ?? 'auto');

        $id = $repo->insert([
            'employee_id' => (int)$log['employee_id'],
            'branch_id'   => (int)$device['branch_id'],
            'device_id'   => (int)$device['id'],
            'event_type'  => $event,
            'marked_at'   => $log['captured_at'],
            'meta'        => $log['meta'] ?? null,
            'source'      => 'device'
        ]);

        return ['status' => 'ok', 'log_id' => $id, 'normalized_event_type' => $event];
    }

    public function mark(array $device): void {
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        if (empty($body['employee_id']) || empty($body['captured_at'])) {
            Response::error('Missing employee_id or captured_at', 400);
        }
        $repo = new AttendanceLog($this->pdo);
        $empRepo = new Employee($this->pdo);
        $res = $this->insertOne($device, $body, $repo, $empRepo);
        if ($res['status'] === 'error') Response::error($res['message'], 400);
        Response::ok($res);
    }

    public function syncOffline(array $device): void {
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $logs = $body['logs'] ?? [];
        $repo = new AttendanceLog($this->pdo);
        $empRepo = new Employee($this->pdo);
        $result = [];
        foreach ($logs as $log) {
            if (empty($log['employee_id']) || empty($log['captured_at'])) {
                $result[] = ['status' => 'error', 'message' => 'missing fields'];
                continue;
            }
            $res = $this->insertOne($device, $log, $repo, $empRepo);
            $result[] = $res;
        }
        Response::ok(['results' => $result]);
    }
}
