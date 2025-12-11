<?php
namespace App\Controllers;

use App\Helpers\Response;
use App\Helpers\JwtHelper;
use App\Middleware\WebAuth;
use App\Models\AttendanceLog;
use App\Models\Employee;
use PDO;

class AttendanceController {
    public function __construct(private PDO $pdo, private array $config, private JwtHelper $jwt) {}

    private function normalizeEvent(?array $lastToday, string $incoming): string {
        if ($incoming !== 'auto') return $incoming;
        if (!$lastToday) return 'in';
        return $lastToday['event_type'] === 'in' ? 'out' : 'in';
    }

    private function insertOne(int $branchId, array $log, AttendanceLog $repo, Employee $empRepo): array {
        $emp = $empRepo->findByCodeInBranch($log['employee_code'] ?? '', $branchId);
        if (!$emp) return ['status' => 'error', 'message' => 'Employee not in branch'];
        if (empty($emp['face_image_path'])) return ['status' => 'error', 'message' => 'Face not registered for employee'];

        $recent = $repo->recentWithin((int)$emp['id'], $this->config['recent_window_minutes']);
        if ($recent) return ['status' => 'skipped', 'reason' => 'already_marked_recently'];

        $markedDate = date('Y-m-d', strtotime($log['timestamp']));
        $lastToday = $repo->lastForEmployeeToday((int)$emp['id'], $markedDate);
        $hasIn = $repo->hasStatusForDay((int)$emp['id'], 'in', $markedDate);
        $hasOut = $repo->hasStatusForDay((int)$emp['id'], 'out', $markedDate);
        $event = $this->normalizeEvent($lastToday, $log['status'] ?? 'auto');
        if ($event === 'in' && $hasIn) return ['status' => 'skipped', 'reason' => 'already_marked_in'];
        if ($event === 'out' && $hasOut) return ['status' => 'skipped', 'reason' => 'already_marked_out'];

        $id = $repo->insert([
            'employee_id' => (int)$emp['id'],
            'branch_id'   => $branchId,
            'device_id'   => null,
            'event_type'  => $event,
            'marked_at'   => $log['timestamp'],
            'meta'        => $log['meta'] ?? null,
            'source'      => 'device',
        ]);

        return ['status' => 'ok', 'log_id' => $id, 'normalized_event_type' => $event];
    }

    public function markForManager(): void {
        $user = WebAuth::authenticate($this->pdo, $this->jwt, ['branch', 'super_admin']);
        $branchId = (int)($user['branch_id'] ?? 0);
        if ($branchId === 0) Response::error('No branch assigned', 400);

        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        if (empty($body['employee_code']) || empty($body['timestamp'])) {
            Response::error('Missing employee_code or timestamp', 400);
        }
        $repo = new AttendanceLog($this->pdo);
        $empRepo = new Employee($this->pdo);
        $res = $this->insertOne($branchId, $body, $repo, $empRepo);
        if ($res['status'] === 'error') Response::error($res['message'], 400);
        if ($res['status'] === 'skipped') Response::ok(['message' => $res['reason'] ?? 'skipped']);
        Response::ok(['message' => 'marked']);
    }

    public function syncOfflineManager(): void {
        $user = WebAuth::authenticate($this->pdo, $this->jwt, ['branch', 'super_admin']);
        $branchId = (int)($user['branch_id'] ?? 0);
        if ($branchId === 0) Response::error('No branch assigned', 400);

        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $logs = $body['logs'] ?? [];
        $repo = new AttendanceLog($this->pdo);
        $empRepo = new Employee($this->pdo);
        $result = [];
        foreach ($logs as $log) {
            if (empty($log['employee_code']) || empty($log['timestamp'])) {
                $result[] = ['status' => 'error', 'message' => 'missing fields'];
                continue;
            }
            $res = $this->insertOne($branchId, $log, $repo, $empRepo);
            $result[] = $res;
        }
        Response::ok(['results' => $result]);
    }
}
