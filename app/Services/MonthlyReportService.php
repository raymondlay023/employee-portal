<?php

namespace App\Services;

use App\DataTransferObjects\AttendanceSummary;
use App\Models\JPayrollAttendance;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MonthlyReportService
{
    /**
     * Get attendance summaries for all active employees in a department.
     *
     * @return Collection<int, AttendanceSummary>
     */
    public function getAttendanceSummariesForDepartment(
        int $departmentId,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        $rows = JPayrollAttendance::query()
            ->join('employees', 'jpayroll_attendances.employee_id', '=', 'employees.id')
            ->leftJoin(DB::raw('(SELECT employee_id, DATE(clock_in_at) as punch_date FROM attendance_logs GROUP BY employee_id, DATE(clock_in_at)) as logs'), function ($join) {
                $join->on('logs.employee_id', '=', 'employees.employee_id')
                    ->on('logs.punch_date', '=', 'jpayroll_attendances.shift_date');
            })
            ->where('employees.department_id', $departmentId)
            ->where(function ($query) use ($endDate) {
                $query->whereNull('employees.end_date')
                    ->orWhere('employees.end_date', '>=', $endDate->toDateString());
            })
            ->where('employees.status', 'active')
            ->whereBetween('jpayroll_attendances.shift_date', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->selectRaw("
                jpayroll_attendances.employee_id,
                CONCAT(employees.first_name, ' ', COALESCE(employees.last_name, '')) as employee_name,
                COUNT(jpayroll_attendances.id) as total_days,
                SUM(CASE WHEN jpayroll_attendances.alpha = 0 AND jpayroll_attendances.sakit = 0 AND jpayroll_attendances.izin = 0 AND logs.employee_id IS NOT NULL THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN jpayroll_attendances.alpha > 0 THEN 1 ELSE 0 END) as absent_days,
                SUM(CASE WHEN jpayroll_attendances.alpha = 0 AND jpayroll_attendances.sakit = 0 AND jpayroll_attendances.izin = 0 AND jpayroll_attendances.telat > 0 AND logs.employee_id IS NOT NULL THEN 1 ELSE 0 END) as late_days,
                SUM(CASE WHEN jpayroll_attendances.alpha <= 0 AND jpayroll_attendances.sakit > 0 THEN 1 ELSE 0 END) as sick_days,
                SUM(CASE WHEN jpayroll_attendances.alpha <= 0 AND jpayroll_attendances.sakit = 0 AND jpayroll_attendances.izin > 0 THEN 1 ELSE 0 END) as leave_days
            ")
            ->groupBy('jpayroll_attendances.employee_id', 'employees.first_name', 'employees.last_name')
            ->orderBy('employee_name')
            ->get();

        return $rows->map(function ($row) {
            return new AttendanceSummary(
                employeeId: (int) $row->employee_id,
                employeeName: trim((string) $row->employee_name),
                totalDays: (int) $row->total_days,
                presentDays: (int) $row->present_days,
                absentDays: (int) $row->absent_days,
                lateDays: (int) $row->late_days,
                sickDays: (int) $row->sick_days,
                leaveDays: (int) $row->leave_days,
            );
        });
    }

    /**
     * Get attendance summary for a single employee.
     */
    public function getAttendanceSummaryForEmployee(
        int $employeeId,
        string $employeeName,
        Carbon $startDate,
        Carbon $endDate
    ): AttendanceSummary {
        $row = JPayrollAttendance::query()
            ->join('employees', 'jpayroll_attendances.employee_id', '=', 'employees.id')
            ->leftJoin(DB::raw('(SELECT employee_id, DATE(clock_in_at) as punch_date FROM attendance_logs GROUP BY employee_id, DATE(clock_in_at)) as logs'), function ($join) {
                $join->on('logs.employee_id', '=', 'employees.employee_id')
                    ->on('logs.punch_date', '=', 'jpayroll_attendances.shift_date');
            })
            ->where('jpayroll_attendances.employee_id', $employeeId)
            ->whereBetween('jpayroll_attendances.shift_date', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->selectRaw('
                COUNT(jpayroll_attendances.id) as total_days,
                SUM(CASE WHEN jpayroll_attendances.alpha = 0 AND jpayroll_attendances.sakit = 0 AND jpayroll_attendances.izin = 0 AND logs.employee_id IS NOT NULL THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN jpayroll_attendances.alpha > 0 THEN 1 ELSE 0 END) as absent_days,
                SUM(CASE WHEN jpayroll_attendances.alpha = 0 AND jpayroll_attendances.sakit = 0 AND jpayroll_attendances.izin = 0 AND jpayroll_attendances.telat > 0 AND logs.employee_id IS NOT NULL THEN 1 ELSE 0 END) as late_days,
                SUM(CASE WHEN jpayroll_attendances.alpha <= 0 AND jpayroll_attendances.sakit > 0 THEN 1 ELSE 0 END) as sick_days,
                SUM(CASE WHEN jpayroll_attendances.alpha <= 0 AND jpayroll_attendances.sakit = 0 AND jpayroll_attendances.izin > 0 THEN 1 ELSE 0 END) as leave_days
            ')
            ->first();

        return new AttendanceSummary(
            employeeId: $employeeId,
            employeeName: $employeeName,
            totalDays: (int) ($row->total_days ?? 0),
            presentDays: (int) ($row->present_days ?? 0),
            absentDays: (int) ($row->absent_days ?? 0),
            lateDays: (int) ($row->late_days ?? 0),
            sickDays: (int) ($row->sick_days ?? 0),
            leaveDays: (int) ($row->leave_days ?? 0),
        );
    }
}
