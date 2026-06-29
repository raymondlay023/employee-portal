<?php

namespace App\Livewire\Hr;

use App\Models\JPayrollAttendance;
use Illuminate\View\View;
use Livewire\Component;

class AttendanceReport extends Component
{
    public $month;
    public $year;
    
    // Sort variables
    public $sortField = 'first_name'; // We will sort the collection after querying
    public $sortDirection = 'asc';

    public function mount()
    {
        $this->month = now()->month;
        $this->year = now()->year;
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
            $this->sortField = $field;
        }
    }

    public function render(): View
    {
        $startDate = now()->setYear($this->year)->setMonth($this->month)->startOfMonth()->toDateString();
        $endDate = now()->setYear($this->year)->setMonth($this->month)->endOfMonth()->toDateString();

        // Single powerful query to aggregate all employee data for the selected month
        $reportData = JPayrollAttendance::with('employee')
            ->whereBetween('shift_date', [$startDate, $endDate])
            ->selectRaw('
                employee_id,
                COUNT(*) as total_days,
                SUM(CASE WHEN alpha > 0 THEN 1 ELSE 0 END) as absent_days,
                SUM(CASE WHEN alpha <= 0 AND (hos > 0 OR wa > 0 OR hoswa > 0) THEN 1 ELSE 0 END) as sick_days,
                SUM(CASE WHEN alpha <= 0 AND hos = 0 AND wa = 0 AND hoswa = 0 AND izin > 0 THEN 1 ELSE 0 END) as leave_days,
                SUM(CASE WHEN alpha <= 0 AND hos = 0 AND wa = 0 AND hoswa = 0 AND izin <= 0 AND telat > 0 THEN 1 ELSE 0 END) as late_days,
                SUM(CASE WHEN alpha <= 0 AND hos = 0 AND wa = 0 AND hoswa = 0 AND izin <= 0 AND telat <= 0 AND op > 0 THEN 1 ELSE 0 END) as permitted_days,
                SUM(CASE WHEN alpha = 0 AND hos = 0 AND wa = 0 AND hoswa = 0 AND izin = 0 AND telat = 0 AND op = 0 THEN 1 ELSE 0 END) as present_days
            ')
            ->groupBy('employee_id')
            ->get();

        // Sort the collection based on the selected field
        $reportData = $reportData->sortBy([
            [
                $this->sortField === 'first_name' ? fn ($item) => optional($item->employee)->first_name : $this->sortField, 
                $this->sortDirection
            ]
        ])->values();

        return view('livewire.hr.attendance-report', [
            'reportData' => $reportData,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ])->layout('layouts.app'); // Use the default authenticated app layout
    }
}
