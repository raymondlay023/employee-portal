<?php

namespace App\Livewire\Hr;

use App\Models\JPayrollAttendance;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class AttendanceReport extends Component
{
    use WithPagination;

    public $month;
    public $year;
    public $sortField = 'first_name';
    public $sortDirection = 'asc';

    /**
     * Whitelist of columns permitted for sorting.
     * Prevents SQL injection via unsanitised public Livewire properties.
     */
    private const ALLOWED_SORT_FIELDS = [
        'first_name'   => 'employees.first_name',
        'present_days' => 'present_days',
        'absent_days'  => 'absent_days',
        'late_days'    => 'late_days',
        'sick_days'    => 'sick_days',
        'leave_days'   => 'leave_days',
        'total_days'   => 'total_days',
    ];

    public function mount(): void
    {
        $this->month = now()->month;
        $this->year  = now()->year;
    }

    public function sortBy(string $field): void
    {
        // Reject any field not in the whitelist silently
        if (!array_key_exists($field, self::ALLOWED_SORT_FIELDS)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField     = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function render(): View
    {
        $startDate = now()->setYear((int) $this->year)->setMonth((int) $this->month)->startOfMonth()->toDateString();
        $endDate   = now()->setYear((int) $this->year)->setMonth((int) $this->month)->endOfMonth()->toDateString();

        // Resolve the safe, whitelisted SQL column name for ordering
        $orderColumn = self::ALLOWED_SORT_FIELDS[$this->sortField] ?? 'employees.first_name';

        // Sanitise sort direction to prevent any possibility of injection
        $orderDirection = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        // Aggregate query: LEFT JOIN preserves orphaned jpayroll records whose
        // employee rows may have been soft/hard deleted, preventing silent data loss.
        $reportData = JPayrollAttendance::with('employee')
            ->leftJoin('employees', 'jpayroll_attendances.employee_id', '=', 'employees.id')
            ->whereBetween('jpayroll_attendances.shift_date', [$startDate, $endDate])
            ->selectRaw('
                jpayroll_attendances.employee_id,
                employees.first_name,
                COUNT(jpayroll_attendances.id) as total_days,
                SUM(CASE WHEN jpayroll_attendances.alpha > 0 THEN 1 ELSE 0 END) as absent_days,
                SUM(CASE WHEN jpayroll_attendances.alpha <= 0 AND jpayroll_attendances.sakit > 0 THEN 1 ELSE 0 END) as sick_days,
                SUM(CASE WHEN jpayroll_attendances.alpha <= 0 AND jpayroll_attendances.sakit = 0 AND jpayroll_attendances.izin > 0 THEN 1 ELSE 0 END) as leave_days,
                SUM(CASE WHEN jpayroll_attendances.alpha <= 0 AND jpayroll_attendances.sakit = 0 AND jpayroll_attendances.izin <= 0 AND jpayroll_attendances.telat > 0 THEN 1 ELSE 0 END) as late_days,
                SUM(CASE WHEN jpayroll_attendances.alpha = 0 AND jpayroll_attendances.sakit = 0 AND jpayroll_attendances.izin = 0 THEN 1 ELSE 0 END) as present_days
            ')
            ->groupBy('jpayroll_attendances.employee_id', 'employees.first_name')
            ->orderBy($orderColumn, $orderDirection)
            ->paginate(50);

        return view('livewire.hr.attendance-report', [
            'reportData' => $reportData,
            'startDate'  => $startDate,
            'endDate'    => $endDate,
        ])->layout('layouts.app');
    }
}
