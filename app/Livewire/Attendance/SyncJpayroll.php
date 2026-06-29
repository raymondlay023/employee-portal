<?php

namespace App\Livewire\Attendance;

use Livewire\Component;
use App\Models\Employee;
use App\Models\ApiSyncLog;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SyncJpayroll extends Component
{
    public $date_from;
    public $date_to;
    public $nik;

    public $showModal = false;

    protected $rules = [
        'date_from' => 'nullable|date',
        'date_to'   => 'nullable|date|after_or_equal:date_from',
        'nik'       => 'nullable|string|max:20',
    ];

    public function mount()
    {
        $this->date_from = now()->format('Y-m-d');
        $this->date_to = now()->format('Y-m-d');
    }

    public $justQueued = false;

    public function sync()
    {
        $this->validate();

        // 1. Guardrail: limit to 31 days
        $start = $this->date_from ? Carbon::parse($this->date_from) : null;
        $end = $this->date_to ? Carbon::parse($this->date_to) : null;

        if ($start && $end && $start->diffInDays($end) > 31) {
            $this->addError('date_to', 'Date range cannot exceed 31 days to prevent API overload.');
            return;
        }

        $date1 = $start ? $start->format('d/m/Y') : null;
        $date2 = $end ? $end->format('d/m/Y') : null;

        $options = array_filter([
            '--date1'        => $date1,
            '--date2'        => $date2,
            '--nik'          => $this->nik ?: null,
            '--trigger'      => 'manual',
            '--triggered-by' => Auth::id(),
        ]);

        // 2. Synchronous fallback for small requests
        // If range <= 3 days, run synchronously
        $runSync = false;
        if ($start && $end && $start->diffInDays($end) <= 3) {
            $runSync = true;
        }

        if ($runSync) {
            Artisan::call('jpayroll:sync-attendance', $options);
            session()->flash('sync_success', 'JPayroll attendance synced successfully!');
        } else {
            Artisan::queue('jpayroll:sync-attendance', $options);
            session()->flash('sync_success', 'JPayroll attendance sync queued successfully. Please watch the logs.');
            $this->justQueued = true;
        }

        $this->showModal = false;
    }

    public function render()

    {
        $employees = Employee::orderBy('first_name')->orderBy('last_name')->get();
        
        $syncLogs = ApiSyncLog::with('triggeredBy')
            ->where('api_name', 'jpayroll_attendance')
            ->orderBy('started_at', 'desc')
            ->take(10)
            ->get();
            
        $isRunning = $syncLogs->where('status', 'running')->count() > 0;
        
        $lastSync = ApiSyncLog::where('api_name', 'jpayroll_attendance')
            ->where('status', 'success')
            ->max('started_at');

        return view('livewire.attendance.sync-jpayroll', [
            'employees' => $employees,
            'syncLogs'  => $syncLogs,
            'isRunning' => $isRunning,
            'lastSync'  => $lastSync,
        ]);
    }
}
