<?php
namespace App\Controllers;

use App\Helpers\Response;
use App\Helpers\JwtHelper;
use App\Middleware\WebAuth;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\AttendanceLog;
use PDO;

class WebBranchController {
    public function __construct(private PDO $pdo, private JwtHelper $jwt) {}

    public function branches(): void {
        WebAuth::authenticate($this->pdo, $this->jwt, ['super_admin']);
        $rows = (new Branch($this->pdo))->all();
        Response::ok(['branches' => $rows]);
    }

    public function branchEmployees(array $params): void {
        $user = WebAuth::authenticate($this->pdo, $this->jwt, ['branch', 'super_admin']);
        $branchId = (int)($user['branch_id'] ?? 0);
        if ($user['role'] === 'super_admin' && !empty($params['id'])) {
            $branchId = (int)$params['id'];
        }
        if ($branchId === 0) {
            Response::error('No branch assigned.', 400);
        }
        $raw = (new Employee($this->pdo))->allActiveByBranch($branchId);
        $emps = array_map(function($e) {
            return [
                'id' => (int)$e['id'],
                'code' => $e['employee_code'],
                'name' => $e['name'],
                'face_registered' => !empty($e['face_image_path']),
                'photo_url' => $this->absUrl($e['face_image_path']),
            ];
        }, $raw);
        Response::ok(['employees' => $emps]);
    }

    public function branchAttendance(array $params): void {
        $user = WebAuth::authenticate($this->pdo, $this->jwt, ['super_admin','branch']);
        $branchId = (int)($user['branch_id'] ?? 0);
        if ($user['role'] === 'super_admin' && !empty($params['id'])) {
            $branchId = (int)$params['id'];
        }
        if ($branchId === 0) {
            Response::error('No branch assigned.', 400);
        }
        $date = $_GET['date'] ?? date('Y-m-d');
        $logs = (new AttendanceLog($this->pdo))->byBranchDate($branchId, $date);
        Response::ok(['logs' => $logs]);
    }

    public function myEmployees(): void
    {
        $user = WebAuth::authenticate(
            $this->pdo,
            $this->jwt,
            ['super_admin', 'branch']
        );

        $branchId = (int)($user['branch_id'] ?? 0);
        if ($user['role'] === 'super_admin' && isset($_GET['branch_id'])) {
            $branchId = (int)$_GET['branch_id'];
        }
        if (!$branchId) {
            Response::error('No branch assigned to this user.', 400);
        }

        $raw = (new Employee($this->pdo))->allActiveByBranch($branchId);
        $emps = array_map(function($e) {
            return [
                'id' => (int)$e['id'],
                'code' => $e['employee_code'],
                'name' => $e['name'],
                'face_registered' => !empty($e['face_image_path']),
                'photo_url' => $this->absUrl($e['face_image_path']),
            ];
        }, $raw);
        Response::ok(['employees' => $emps]);
    }

    private function absUrl(?string $path): ?string {
        if (!$path) return null;
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        return "$scheme://$host$path";
    }
}
