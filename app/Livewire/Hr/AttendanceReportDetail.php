<?php

namespace App\Livewire\Hr;

use App\Authorization\Permissions;
use App\Models\AttendanceLog;
use App\Models\DailyWorkLog;
use App\Models\Employee;
use App\Models\JPayrollAttendance;
use Illuminate\View\View;
use Livewire\Component;

class AttendanceReportDetail extends Component
{
    public Employee $employee;

    public $month;

    public $year;

    public $statusFilter = 'all';

    public function mount(Employee $employee): void
    {
        $this->employee = $employee;

        $this->month = request('month', now()->month);
        $this->year = request('year', now()->year);

        $user = auth()->user();
        if (! $user->can(Permissions::MANAGE_ATTENDANCE)) {
            $managerDeptId = $user->employee?->department_id;
            if (! $managerDeptId || $employee->department_id !== $managerDeptId) {
                abort(403, 'You are only authorized to view attendance logs for employees in your department.');
            }
        }
    }

    public function setStatusFilter(string $status): void
    {
        $this->statusFilter = $this->statusFilter === $status ? 'all' : $status;
    }

    public function render(): View
    {
        $availableYears = JPayrollAttendance::where('employee_id', $this->employee->id)
            ->selectRaw('YEAR(shift_date) as year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->toArray();

        if (empty($availableYears)) {
            $availableYears = [now()->year];
        }

        if (! in_array((int) $this->year, $availableYears)) {
            $this->year = $availableYears[0];
        }

        $availableMonths = JPayrollAttendance::where('employee_id', $this->employee->id)
            ->whereYear('shift_date', $this->year)
            ->selectRaw('MONTH(shift_date) as month')
            ->distinct()
            ->orderBy('month')
            ->pluck('month')
            ->toArray();

        if (empty($availableMonths)) {
            $availableMonths = [now()->month];
        }

        if (! in_array((int) $this->month, $availableMonths)) {
            $this->month = $availableMonths[0] ?? now()->month;
        }

        $startDate = now()->setYear((int) $this->year)->setMonth((int) $this->month)->startOfMonth()->toDateString();
        $endDate = now()->setYear((int) $this->year)->setMonth((int) $this->month)->endOfMonth()->toDateString();

        // Pre-fetch manual logs for the month to prevent N+1 in statusLabel() & getBiometricLog()
        $manualLogs = AttendanceLog::where('employee_id', $this->employee->employee_id)
            ->whereBetween('clock_in_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->get();

        JPayrollAttendance::seedBiometricLogsCache($this->employee->employee_id, $manualLogs, $startDate, $endDate);

        // Single query for all logs of the month
        $allLogs = JPayrollAttendance::with('employee')
            ->where('employee_id', $this->employee->id)
            ->whereBetween('shift_date', [$startDate, $endDate])
            ->orderBy('shift_date', 'asc')
            ->get();

        // Calculate summary stats in a single pass
        $summary = [
            'total' => 0,
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'sick' => 0,
            'permit' => 0,
            'leave' => 0,
        ];

        foreach ($allLogs as $log) {
            $summary['total']++;
            $statusLabel = $log->statusLabel();
            if ($statusLabel === 'Absent' || $statusLabel === 'Absent (No Biometric)') {
                $summary['absent']++;
            } elseif ($statusLabel === 'Sick') {
                $summary['sick']++;
            } elseif ($statusLabel === 'Permit' || $statusLabel === 'Leave') {
                $summary['permit']++;
                $summary['leave']++;
            } elseif ($statusLabel === 'Late') {
                $summary['late']++;
            } elseif ($statusLabel === 'Off Day') {
                // Exclude off day
            } else {
                $summary['present']++;
            }
        }

        // Filter logs based on statusFilter
        $filteredLogs = $allLogs->filter(function ($log) {
            if ($this->statusFilter === 'all' || empty($this->statusFilter)) {
                return true;
            }

            $statusLabel = strtolower($log->statusLabel());
            $filter = strtolower($this->statusFilter);

            if ($filter === 'absent') {
                return str_contains($statusLabel, 'absent');
            }

            if ($filter === 'present') {
                return $statusLabel === 'present';
            }

            if ($filter === 'permit' || $filter === 'leave') {
                return $statusLabel === 'permit' || $statusLabel === 'leave';
            }

            return $statusLabel === $filter;
        });

        // Group filtered logs by calendar week (Monday start)
        $groupedLogs = $filteredLogs->groupBy(function ($log) {
            $date = $log->shift_date;
            $firstDayOfMonth = $date->copy()->startOfMonth();
            $firstDayOffset = $firstDayOfMonth->dayOfWeekIso - 1;
            $dayOfMonth = $date->day - 1;
            $weekNumber = (int) floor(($dayOfMonth + $firstDayOffset) / 7) + 1;

            return 'Week '.$weekNumber;
        });

        // Fetch all work logs for the employee in the month
        $workLogs = DailyWorkLog::where('user_id', $this->employee->user_id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('start_time')
            ->get()
            ->groupBy(fn ($log) => $log->date->toDateString());

        return view('livewire.hr.attendance-report-detail', [
            'groupedLogs' => $groupedLogs,
            'summary' => $summary,
            'workLogs' => $workLogs,
            'availableYears' => $availableYears,
            'availableMonths' => $availableMonths,
        ])->layout('layouts.app');
    }
}
