<?php

namespace App\Livewire\System;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\ApiSyncLog;

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
        $query = ApiSyncLog::with('triggeredBy')
            ->orderBy('started_at', 'desc');

        if ($this->searchApi) {
            $query->where('api_name', 'like', '%' . $this->searchApi . '%');
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
}
