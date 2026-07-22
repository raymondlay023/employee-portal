<?php

namespace App\Livewire\System;

use App\Authorization\Permissions;
use App\Models\ApiSyncLog;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ApiLogs extends Component
{
    use WithPagination;

    public $searchApi = '';

    public $searchStatus = '';

    protected $queryString = [
        'searchApi' => ['except' => ''],
        'searchStatus' => ['except' => ''],
    ];

    public function updatingSearchApi()
    {
        $this->resetPage();
    }

    public function updatingSearchStatus()
    {
        $this->resetPage();
    }

    public function render()
    {
        // Auto-recover logs stuck in 'running' for > 10 minutes (killed by queue worker timeout)
        ApiSyncLog::where('status', 'running')
            ->where('started_at', '<', now()->subMinutes(10))
            ->update([
                'status' => 'failed',
                'error_message' => 'Job timed out or was interrupted before completing.',
                'ended_at' => now(),
            ]);

        $query = ApiSyncLog::with('triggeredBy')
            ->orderBy('started_at', 'desc');

        if ($this->searchApi) {
            $query->where('api_name', 'like', '%'.$this->searchApi.'%');
        }

        if ($this->searchStatus) {
            $query->where('status', $this->searchStatus);
        }

        $logs = $query->paginate(15);
        $isRunning = collect($logs->items())->where('status', 'running')->count() > 0;

        return view('livewire.system.api-logs', [
            'logs' => $logs,
            'isRunning' => $isRunning,
        ]);
    }

    /**
     * Download the raw XML payload file for a biometric sync.
     */
    public function downloadPayload(string $path)
    {
        // 1. Authorization check
        if (! auth()->user()->can(Permissions::VIEW_API_LOGS)) {
            abort(403, 'Unauthorized');
        }

        // 2. Path traversal security checks
        if (! str_starts_with($path, 'biometric/raw_') || ! str_ends_with($path, '.xml') || str_contains($path, '..')) {
            abort(400, 'Invalid file path');
        }

        // 3. File existence check
        if (! Storage::exists($path)) {
            session()->flash('error', __('Backup file does not exist on the storage disk.'));

            return null;
        }

        // 4. Stream file download
        return Storage::download($path);
    }
}
