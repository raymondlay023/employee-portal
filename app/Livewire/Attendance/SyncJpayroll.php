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
    
    public $syncRequestedAt = null;
    public $showModal = false;

    public function mount()
    {
        $this->date_from = now()->subDays(7)->format('Y-m-d');
        $this->date_to   = now()->format('Y-m-d');
    }

    public function sync()
    {
        $this->validate([
            'date_from' => 'required|date',
            'date_to'   => 'required|date|after_or_equal:date_from',
            'nik'       => 'nullable|string',
        ]);

        $start = Carbon::parse($this->date_from);
        $end   = Carbon::parse($this->date_to);

        if ($start->diffInDays($end) > 31) {
            $this->addError('date_to', 'Date range cannot exceed 31 days to prevent API overload.');
            return;
        }

        $options = array_filter([
            '--date1'        => $start->format('d/m/Y'),
            '--date2'        => $end->format('d/m/Y'),
            '--nik'          => $this->nik ?: null,
            '--trigger'      => 'manual',
            '--triggered-by' => Auth::id(),
        ]);

        $runSync = false;
        if ($start && $end && $start->diffInDays($end) <= 3) {
            $runSync = true;
        }

        if ($runSync) {
            Artisan::call('jpayroll:sync-attendance', $options);
            session()->flash('sync_success', 'JPayroll attendance synced successfully!');
            $this->redirect(route('attendance.index'));
        } else {
            Artisan::queue('jpayroll:sync-attendance', $options);
            session()->flash('sync_success', 'JPayroll attendance sync queued successfully. Please watch the logs.');
            $this->syncRequestedAt = now()->toDateTimeString();
        }

        $this->showModal = false;
    }

    public function render()
    {
        $employees = Employee::orderBy('first_name')->orderBy('last_name')->get();
        
        $latestLog = ApiSyncLog::where('api_name', 'jpayroll_attendance')
            ->orderBy('started_at', 'desc')
            ->first();
            
        $isRunning = $latestLog && $latestLog->status === 'running';
        
        if ($this->syncRequestedAt) {
            if ($latestLog && $latestLog->created_at >= $this->syncRequestedAt) {
                if ($latestLog->status !== 'running') {
                    $this->redirect(route('attendance.index'));
                }
            }
        }
        
        $lastSync = ApiSyncLog::where('api_name', 'jpayroll_attendance')
            ->where('status', 'success')
            ->max('started_at');

        return view('livewire.attendance.sync-jpayroll', [
            'employees' => $employees,
            'latestLog' => $latestLog,
            'isRunning' => $isRunning,
            'justQueued' => $this->syncRequestedAt !== null && !$isRunning,
            'lastSync'  => $lastSync,
        ]);
    }
}
