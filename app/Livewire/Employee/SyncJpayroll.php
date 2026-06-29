<?php

namespace App\Livewire\Employee;

use Livewire\Component;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use App\Models\ApiSyncLog;

class SyncJpayroll extends Component
{
    public $syncRequestedAt = null;
    public $showModal = false;

    public function sync()
    {
        $options = array_filter([
            '--trigger'      => 'manual',
            '--triggered-by' => Auth::id(),
        ]);

        Artisan::queue('jpayroll:sync-employees', $options);
        
        session()->flash('sync_success', 'Master Employee sync queued successfully. Please watch the logs.');
        $this->syncRequestedAt = now()->toDateTimeString();
        $this->showModal = false;
    }

    public function render()
    {
        $latestLog = ApiSyncLog::where('api_name', 'jpayroll_employees')
            ->orderBy('started_at', 'desc')
            ->first();
            
        $isRunning = $latestLog && $latestLog->status === 'running';

        if ($this->syncRequestedAt) {
            if ($latestLog && $latestLog->created_at >= $this->syncRequestedAt) {
                if ($latestLog->status !== 'running') {
                    $this->redirect(route('employees.index'));
                }
            }
        }

        return view('livewire.employee.sync-jpayroll', [
            'latestLog' => $latestLog,
            'isRunning' => $isRunning,
            'justQueued' => $this->syncRequestedAt !== null && !$isRunning,
        ]);
    }
}
