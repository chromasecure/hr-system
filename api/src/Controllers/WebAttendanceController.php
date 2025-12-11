<?php
namespace App\Controllers;

use App\Helpers\Response;
use App\Helpers\JwtHelper;
use App\Middleware\WebAuth;
use App\Models\AttendanceLog;
use App\Models\Employee;
use PDO;

class WebAttendanceController
{
    public function __construct(private PDO $pdo, private JwtHelper $jwt) {}

    public function today(): void
    {
        $user = WebAuth::authenticate($this->pdo, $this->jwt, ['branch', 'super_admin']);
        $branchId = (int)($user['branch_id'] ?? 0);
        if ($branchId === 0) Response::error('No branch assigned', 400);

        $date = $_GET['date'] ?? date('Y-m-d');
        $empRepo = new Employee($this->pdo);
        $logRepo = new AttendanceLog($this->pdo);

        $emps = $empRepo->allActiveByBranch($branchId);
        $logs = $logRepo->byBranchDate($branchId, $date);
        $statusMap = [];
        foreach ($logs as $l) {
            $statusMap[(int)$l['employee_id']] = $l;
        }
        $out = [];
        foreach ($emps as $e) {
            $log = $statusMap[(int)$e['id']] ?? null;
            $status = $log['event_type'] ?? 'absent';
            $out[] = [
                'id' => (int)$e['id'],
                'code' => $e['employee_code'],
                'name' => $e['name'],
                'status' => $status,
                'remark' => $log && isset($log['meta']) ? json_decode($log['meta'], true)['remark'] ?? '' : '',
            ];
        }
        Response::ok(['employees' => $out, 'date' => $date]);
    }

    public function update(): void
    {
        $user = WebAuth::authenticate($this->pdo, $this->jwt, ['branch', 'super_admin']);
        $branchId = (int)($user['branch_id'] ?? 0);
        if ($branchId === 0) Response::error('No branch assigned', 400);

        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $date = $body['date'] ?? date('Y-m-d');
        $records = $body['records'] ?? [];
        if (!is_array($records)) Response::error('Invalid records', 400);

        $logRepo = new AttendanceLog($this->pdo);
        $empRepo = new Employee($this->pdo);

        foreach ($records as $r) {
            $code = $r['employee_code'] ?? '';
            $status = $r['status'] ?? 'absent';
            $remark = $r['remark'] ?? '';
            if ($code === '') continue;
            $emp = $empRepo->findByCodeInBranch($code, $branchId);
            if (!$emp) continue;

            $eventType = $status === 'out' ? 'out' : ($status === 'absent' ? 'absent' : 'in');

            $logRepo->insert([
                'employee_id' => (int)$emp['id'],
                'branch_id' => $branchId,
                'device_id' => null,
                'event_type' => $eventType,
                'marked_at' => $date . ' 09:00:00',
                'meta' => ['remark' => $remark],
                'source' => 'manager',
            ]);
        }

        Response::ok(['message' => 'Attendance updated']);
    }
}
