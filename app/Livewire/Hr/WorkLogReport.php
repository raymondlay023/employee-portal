<?php

namespace App\Livewire\Hr;

use App\Authorization\Permissions;
use App\Models\DailyWorkLog;
use App\Models\Department;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class WorkLogReport extends Component
{
    use WithPagination;

    public string $viewMode = 'day';

    public string $date = '';

    public string $startDate = '';

    public string $endDate = '';

    public string $month = '';

    public string $year = '';

    public string $department_id = '';

    public string $search = '';

    public bool $activeOnly = true;

    public string $sortField = 'first_name';

    public string $sortDirection = 'asc';

    private const ALLOWED_SORT_FIELDS = [
        'first_name' => 'employees.first_name',
        'total_hours' => 'total_hours',
        'activities_count' => 'activities_count',
    ];

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->date = now()->toDateString();
        $this->startDate = now()->subDays(6)->toDateString();
        $this->endDate = now()->toDateString();
        $this->month = (string) now()->month;
        $this->year = (string) now()->year;

        // Scoping manager's department
        if (! auth()->user()->can(Permissions::MANAGE_EMPLOYEES)) {
            $this->department_id = (string) (auth()->user()->employee?->department_id ?? '');
        }
    }

    public function updatingViewMode(): void
    {
        $this->resetPage();
    }

    public function updatingDate(): void
    {
        $this->resetPage();
    }

    public function updatingStartDate(): void
    {
        $this->resetPage();
    }

    public function updatingEndDate(): void
    {
        $this->resetPage();
    }

    public function updatingMonth(): void
    {
        $this->resetPage();
    }

    public function updatingYear(): void
    {
        $this->resetPage();
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

    /**
     * Sort the table by field.
     */
    public function sortBy(string $field): void
    {
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

    /**
     * Render the component view.
     */
    public function render(): View
    {
        $dateBoundaries = $this->getDateBoundaries();
        $startDate = $dateBoundaries['start'];
        $endDate = $dateBoundaries['end'];

        $orderColumn = self::ALLOWED_SORT_FIELDS[$this->sortField] ?? 'employees.first_name';
        $orderDirection = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        // Base query with SELECT list and group by constraints
        $query = Employee::query()
            ->with(['department', 'designation'])
            ->leftJoin('users', 'employees.user_id', '=', 'users.id')
            ->selectRaw('
                employees.*,
                COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(daily_work_logs.end_time, daily_work_logs.start_time))) / 3600, 0) as total_hours,
                COUNT(daily_work_logs.id) as activities_count
            ')
            ->leftJoin('daily_work_logs', function ($join) use ($startDate, $endDate) {
                $join->on('users.id', '=', 'daily_work_logs.user_id')
                    ->whereBetween('daily_work_logs.date', [$startDate, $endDate]);
            })
            ->groupBy(
                'employees.id',
                'employees.employee_id',
                'employees.first_name',
                'employees.last_name',
                'employees.email',
                'employees.gender',
                'employees.department_id',
                'employees.designation_id',
                'employees.phone',
                'employees.joined_at',
                'employees.end_date',
                'employees.status',
                'employees.account_type',
                'employees.organization_structure',
                'employees.branch',
                'employees.skills',
                'employees.created_at',
                'employees.updated_at',
                'users.id'
            );

        // Apply filters
        if (! auth()->user()->can(Permissions::MANAGE_EMPLOYEES)) {
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

        $stats = $this->calculateStats($startDate, $endDate);

        // Get unique years that have work logs, fallback to current and last year
        $availableYears = DailyWorkLog::selectRaw('YEAR(date) as year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->toArray();

        if (empty($availableYears)) {
            $availableYears = [(int) now()->year, (int) now()->subYear()->year];
        }

        $availableMonths = range(1, 12);

        return view('livewire.hr.work-log-report', [
            'reportData' => $reportData,
            'departments' => Department::orderBy('name')->get(),
            'availableYears' => $availableYears,
            'availableMonths' => $availableMonths,
            'stats' => $stats,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ])->layout('layouts.app');
    }

    /**
     * Resolve start and end dates based on viewMode.
     *
     * @return array{start: string, end: string}
     */
    private function getDateBoundaries(): array
    {
        $startDate = now()->toDateString();
        $endDate = now()->toDateString();

        if ($this->viewMode === 'day') {
            $startDate = ! empty($this->date) ? $this->date : now()->toDateString();
            $endDate = $startDate;
        } elseif ($this->viewMode === 'week') {
            $currentDate = ! empty($this->date) ? Carbon::parse($this->date) : now();
            $dayOfWeek = $currentDate->dayOfWeek; // 0 for Sunday, 6 for Saturday
            $startDate = $currentDate->copy()->subDays($dayOfWeek)->toDateString();
            $endDate = $currentDate->copy()->addDays(6 - $dayOfWeek)->toDateString();
        } elseif ($this->viewMode === 'month') {
            $year = ! empty($this->year) ? (int) $this->year : now()->year;
            $month = ! empty($this->month) ? (int) $this->month : now()->month;
            $startDate = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();
        } elseif ($this->viewMode === 'range') {
            $startDate = ! empty($this->startDate) ? $this->startDate : now()->toDateString();
            $endDate = ! empty($this->endDate) ? $this->endDate : now()->toDateString();
        }

        return ['start' => $startDate, 'end' => $endDate];
    }

    /**
     * Calculate summary statistics for matching employees.
     *
     * @return array{total: int, logged: int, average: float, completion_rate: float}
     */
    private function calculateStats(string $startDate, string $endDate): array
    {
        $query = Employee::query()
            ->leftJoin('users', 'employees.user_id', '=', 'users.id');

        if (! auth()->user()->can(Permissions::MANAGE_EMPLOYEES)) {
            $managerDeptId = auth()->user()->employee?->department_id;
            if (! $managerDeptId) {
                return ['total' => 0, 'logged' => 0, 'average' => 0, 'completion_rate' => 0];
            }
            $query->where('employees.department_id', $managerDeptId);
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

        $totalEmployees = $query->count();
        $userIds = $query->pluck('employees.user_id')->filter()->toArray();

        if (empty($userIds)) {
            return [
                'total' => $totalEmployees,
                'logged' => 0,
                'average' => 0,
                'completion_rate' => 0,
            ];
        }

        $logs = DailyWorkLog::whereIn('user_id', $userIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $hoursPerUser = [];
        foreach ($logs as $log) {
            $hoursPerUser[$log->user_id] = ($hoursPerUser[$log->user_id] ?? 0) + $log->duration_in_hours;
        }

        $loggedCount = count($hoursPerUser);
        $totalHours = array_sum($hoursPerUser);
        $averageHours = $loggedCount > 0 ? $totalHours / $loggedCount : 0;

        $completedCount = 0;
        foreach ($hoursPerUser as $hours) {
            if ($hours >= 8) {
                $completedCount++;
            }
        }

        $completionRate = $totalEmployees > 0 ? ($completedCount / $totalEmployees) * 100 : 0;

        return [
            'total' => $totalEmployees,
            'logged' => $loggedCount,
            'average' => round($averageHours, 1),
            'completion_rate' => round($completionRate, 1),
        ];
    }
}
