<?php

namespace App\Livewire\Hr;

use App\Authorization\Permissions;
use App\Models\DailyWorkLog;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\View\View;
use Livewire\Component;

class WorkLogReportDetail extends Component
{
    public Employee $employee;

    public string $viewMode = 'day';

    public string $date = '';

    public string $startDate = '';

    public string $endDate = '';

    public string $month = '';

    public string $year = '';

    /**
     * Mount the component.
     */
    public function mount(Employee $employee): void
    {
        $this->employee = $employee;

        $this->viewMode = (string) request('viewMode', 'day');
        $this->date = (string) request('date', now()->toDateString());
        $this->startDate = (string) request('startDate', now()->subDays(6)->toDateString());
        $this->endDate = (string) request('endDate', now()->toDateString());
        $this->month = (string) request('month', now()->month);
        $this->year = (string) request('year', now()->year);

        // Security check
        $user = auth()->user();
        if (! $user->can(Permissions::MANAGE_EMPLOYEES)) {
            $managerDeptId = $user->employee?->department_id;
            if (! $managerDeptId || $employee->department_id !== $managerDeptId) {
                abort(403, 'You are only authorized to view work logs for employees in your department.');
            }
        }
    }

    /**
     * Render the component view.
     */
    public function render(): View
    {
        $dateBoundaries = $this->getDateBoundaries();
        $startDate = $dateBoundaries['start'];
        $endDate = $dateBoundaries['end'];

        $groupedLogs = collect();
        if ($this->employee->user_id) {
            $groupedLogs = DailyWorkLog::where('user_id', $this->employee->user_id)
                ->whereBetween('date', [$startDate, $endDate])
                ->orderBy('date', 'desc')
                ->orderBy('start_time', 'asc')
                ->get()
                ->groupBy(function ($log) {
                    return $log->date->toDateString();
                });
        }

        return view('livewire.hr.work-log-report-detail', [
            'groupedLogs' => $groupedLogs,
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
}
