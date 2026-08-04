<?php

namespace App\Livewire\Hr;

use App\Authorization\Permissions;
use App\Models\Department;
use App\Models\JPayrollAttendance;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class AttendanceReport extends Component
{
    use WithPagination;

    public $month;

    public $year;

    public $department_id = '';

    public $search = '';

    public $activeOnly = true;

    public $sortField = 'first_name';

    public $sortDirection = 'asc';

    /**
     * Whitelist of columns permitted for sorting.
     * Prevents SQL injection via unsanitised public Livewire properties.
     */
    private const ALLOWED_SORT_FIELDS = [
        'first_name' => 'employees.first_name',
        'present_days' => 'present_days',
        'absent_days' => 'absent_days',
        'late_days' => 'late_days',
        'sick_days' => 'sick_days',
        'leave_days' => 'leave_days',
        'total_days' => 'total_days',
    ];

    public function mount(): void
    {
        $this->month = now()->month;
        $this->year = now()->year;

        if (! auth()->user()->can(Permissions::MANAGE_ATTENDANCE)) {
            $this->department_id = auth()->user()->employee?->department_id ?? '';
        }
    }

    public function updatingDepartmentId(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingActiveOnly(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        // Reject any field not in the whitelist silently
        if (! array_key_exists($field, self::ALLOWED_SORT_FIELDS)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function render(): View
    {
        $availableYears = JPayrollAttendance::selectRaw('YEAR(shift_date) as year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->toArray();

        if (empty($availableYears)) {
            $availableYears = [now()->year];
        }

        // If the current selected year is not in the available years (e.g. initial load before any data exists)
        if (! in_array((int) $this->year, $availableYears)) {
            $this->year = $availableYears[0];
        }

        $availableMonths = JPayrollAttendance::whereYear('shift_date', $this->year)
            ->selectRaw('MONTH(shift_date) as month')
            ->distinct()
            ->orderBy('month')
            ->pluck('month')
            ->toArray();

        if (empty($availableMonths)) {
            $availableMonths = [now()->month];
        }

        // If the current selected month doesn't exist in the new year, fallback to the first available month
        if (! in_array((int) $this->month, $availableMonths)) {
            $this->month = $availableMonths[0];
        }

        $startDate = now()->setYear((int) $this->year)->setMonth((int) $this->month)->startOfMonth()->toDateString();
        $endDate = now()->setYear((int) $this->year)->setMonth((int) $this->month)->endOfMonth()->toDateString();

        // Resolve the safe, whitelisted SQL column name for ordering
        $orderColumn = self::ALLOWED_SORT_FIELDS[$this->sortField] ?? 'employees.first_name';

        // Sanitise sort direction to prevent any possibility of injection
        $orderDirection = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        $query = JPayrollAttendance::with('employee')
            ->leftJoin('employees', 'jpayroll_attendances.employee_id', '=', 'employees.id')
            ->leftJoin(DB::raw('(SELECT employee_id, DATE(clock_in_at) as punch_date FROM attendance_logs GROUP BY employee_id, DATE(clock_in_at)) as logs'), function ($join) {
                $join->on('logs.employee_id', '=', 'employees.employee_id')
                    ->on('logs.punch_date', '=', 'jpayroll_attendances.shift_date');
            })
            ->whereBetween('jpayroll_attendances.shift_date', [$startDate, $endDate])
            ->selectRaw('
                jpayroll_attendances.employee_id,
                employees.first_name,
                COUNT(jpayroll_attendances.id) as total_days,
                SUM(CASE WHEN jpayroll_attendances.alpha > 0 THEN 1 ELSE 0 END) as absent_days,
                SUM(CASE WHEN jpayroll_attendances.alpha <= 0 AND jpayroll_attendances.sakit > 0 THEN 1 ELSE 0 END) as sick_days,
                SUM(CASE WHEN jpayroll_attendances.alpha <= 0 AND jpayroll_attendances.sakit = 0 AND jpayroll_attendances.izin > 0 THEN 1 ELSE 0 END) as leave_days,
                SUM(CASE WHEN jpayroll_attendances.alpha = 0 AND jpayroll_attendances.sakit = 0 AND jpayroll_attendances.izin = 0 AND jpayroll_attendances.telat > 0 AND logs.employee_id IS NOT NULL THEN 1 ELSE 0 END) as late_days,
                SUM(CASE WHEN jpayroll_attendances.alpha = 0 AND jpayroll_attendances.sakit = 0 AND jpayroll_attendances.izin = 0 AND logs.employee_id IS NOT NULL THEN 1 ELSE 0 END) as present_days
            ')
            ->groupBy('jpayroll_attendances.employee_id', 'employees.first_name');

        if (! auth()->user()->can(Permissions::MANAGE_ATTENDANCE)) {
            $managerDeptId = auth()->user()->employee?->department_id;
            if (! $managerDeptId) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('employees.department_id', $managerDeptId);
            }
        } else {
            if (! empty($this->department_id)) {
                $query->where('employees.department_id', $this->department_id);
            }
        }

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('employees.first_name', 'like', '%'.$this->search.'%')
                    ->orWhere('employees.last_name', 'like', '%'.$this->search.'%')
                    ->orWhere('employees.employee_id', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->activeOnly) {
            $query->where(function ($q) {
                $q->whereNull('employees.end_date')
                    ->orWhere('employees.end_date', '>=', now()->toDateString());
            });
        }

        $reportData = $query->orderBy($orderColumn, $orderDirection)
            ->paginate(50);

        return view('livewire.hr.attendance-report', [
            'reportData' => $reportData,
            'departments' => Department::orderBy('name')->get(),
            'availableYears' => $availableYears,
            'availableMonths' => $availableMonths,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ])->layout('layouts.app');
    }
}
