<?php

namespace App\Livewire\Hr;

use App\Authorization\Permissions;
use App\Models\Employee;
use App\Models\JPayrollAttendance;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class AttendanceReportDetail extends Component
{
    use WithPagination;

    public Employee $employee;
    public $month;
    public $year;

    public function mount(Employee $employee): void
    {
        $this->employee = $employee;
        
        $this->month = request('month', now()->month);
        $this->year = request('year', now()->year);

        $user = auth()->user();
        if (!$user->can(Permissions::MANAGE_ATTENDANCE)) {
            $managerDeptId = $user->employee?->department_id;
            if (!$managerDeptId || $employee->department_id !== $managerDeptId) {
                abort(403, 'You are only authorized to view attendance logs for employees in your department.');
            }
        }
    }

    public function updatingMonth(): void
    {
        $this->resetPage();
    }

    public function updatingYear(): void
    {
        $this->resetPage();
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

        if (!in_array((int) $this->year, $availableYears)) {
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

        if (!in_array((int) $this->month, $availableMonths)) {
            $this->month = $availableMonths[0] ?? now()->month;
        }

        $startDate = now()->setYear((int) $this->year)->setMonth((int) $this->month)->startOfMonth()->toDateString();
        $endDate   = now()->setYear((int) $this->year)->setMonth((int) $this->month)->endOfMonth()->toDateString();

        $query = JPayrollAttendance::where('employee_id', $this->employee->id)
            ->whereBetween('shift_date', [$startDate, $endDate])
            ->orderBy('shift_date', 'asc');

        $logs = $query->paginate(31);

        // Calculate summary for the selected month
        $summary = [
            'total' => 0,
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'sick' => 0,
            'leave' => 0,
        ];

        $allLogs = JPayrollAttendance::where('employee_id', $this->employee->id)
            ->whereBetween('shift_date', [$startDate, $endDate])
            ->get();

        foreach ($allLogs as $log) {
            $summary['total']++;
            if ($log->alpha > 0) {
                $summary['absent']++;
            } elseif ($log->sakit > 0) {
                $summary['sick']++;
            } elseif ($log->izin > 0) {
                $summary['leave']++;
            } elseif ($log->telat > 0) {
                $summary['late']++;
            } else {
                $summary['present']++;
            }
        }

        return view('livewire.hr.attendance-report-detail', [
            'logs' => $logs,
            'summary' => $summary,
            'availableYears' => $availableYears,
            'availableMonths' => $availableMonths,
        ])->layout('layouts.app');
    }
}
