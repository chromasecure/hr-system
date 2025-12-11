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
        $user = WebAuth::authenticate($this->pdo, $this->jwt, ['super_admin']);
        $rows = (new Branch($this->pdo))->all();
        Response::ok(['branches' => $rows]);
    }

    public function branchEmployees(array $params): void {
        $user = WebAuth::authenticate($this->pdo, $this->jwt, ['super_admin','branch_manager']);
        $branchId = (int)$params['id'];
        if ($user['role'] === 'branch_manager' && $user['branch_id'] != $branchId) {
            Response::error('Forbidden', 403);
        }
        $emps = (new Employee($this->pdo))->allActiveByBranch($branchId);
        Response::ok(['employees' => $emps]);
    }

    public function branchAttendance(array $params): void {
        $user = WebAuth::authenticate($this->pdo, $this->jwt, ['super_admin','branch_manager']);
        $branchId = (int)$params['id'];
        if ($user['role'] === 'branch_manager' && $user['branch_id'] != $branchId) {
            Response::error('Forbidden', 403);
        }
        $date = $_GET['date'] ?? date('Y-m-d');
        $logs = (new AttendanceLog($this->pdo))->byBranchDate($branchId, $date);
        Response::ok(['logs' => $logs]);
    }

    public function myEmployees(): void {
    // Allow both super_admin and branch_manager
    $user = WebAuth::authenticate(
        $this->pdo,
        $this->jwt,
        ['super_admin', 'branch_manager']
    );

    // If branch_manager → always use their own branch_id
    if ($user['role'] === 'branch_manager') {
        $branchId = (int)($user['branch_id'] ?? 0);
        if (!$branchId) {
            Response::error('No branch assigned to this manager', 400);
        }
    } else {
        // super_admin must specify ?branch_id=ID
        $branchId = isset($_GET['branch_id'])
            ? (int)$_GET['branch_id']
            : 0;

        if (!$branchId) {
            Response::error(
                'branch_id query parameter is required for super_admin',
                400
            );
        }
    }

    $emps = (new Employee($this->pdo))->allActiveByBranch($branchId);
    Response::ok(['employees' => $emps]);
}

}
