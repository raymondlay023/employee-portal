<div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden" 
    @if($isRunning || $justQueued) wire:poll.5s @endif>
    <div class="px-6 pt-6 pb-4 border-b border-slate-100 flex items-center justify-between gap-4">
        <div class="flex items-center gap-2">
            <div class="p-2 rounded-xl bg-slate-50 border border-slate-100 text-slate-600">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-extrabold text-slate-900">Sync Execution Logs</h3>
                <p class="text-[10px] text-slate-400 font-medium">History of recent synchronizations</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            @if($lastSync)
                <span class="text-[10px] text-slate-500 font-medium hidden sm:block">
                    Last sync: {{ \Carbon\Carbon::parse($lastSync)->timezone('Asia/Jakarta')->format('d M Y, H:i') }}
                </span>
            @else
                <span class="text-[10px] text-slate-400 font-medium hidden sm:block">Never synced</span>
            @endif

            <button type="button"
                wire:click="$set('showModal', true)"
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-brand-600 hover:bg-brand-700 active:scale-95 text-white text-xs font-bold rounded-xl shadow-sm transition-all cursor-pointer border-0">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                </svg>
                Sync from JPayroll
            </button>
        </div>
    </div>

    <!-- Livewire Modal -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto"
            x-data="{ syncing: false }"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0">
            
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>

            <!-- Modal Content Container -->
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-visible rounded-3xl bg-white px-6 py-6 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg space-y-4 border border-slate-100"
                    @click.away="if (!syncing) @this.set('showModal', false)"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                    
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h3 class="text-base font-extrabold text-slate-900">Configure JPayroll Sync</h3>
                        <button type="button" wire:click="$set('showModal', false)" class="text-slate-400 hover:text-slate-600 focus:outline-none disabled:opacity-50">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form wire:submit.prevent="sync" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label for="sync_date_from" class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Date From</label>
                                <input type="date" id="sync_date_from" wire:model.defer="date_from"
                                    class="text-xs border border-slate-200 rounded-xl px-3 py-2.5 text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-400 bg-slate-50 disabled:opacity-60">
                                @error('date_from') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label for="sync_date_to" class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Date To</label>
                                <input type="date" id="sync_date_to" wire:model.defer="date_to"
                                    class="text-xs border border-slate-200 rounded-xl px-3 py-2.5 text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-400 bg-slate-50 disabled:opacity-60">
                                @error('date_to') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="flex flex-col gap-1.5" wire:ignore>
                            <label for="sync_nik" class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Employee / NIK (Optional)</label>
                            <select id="sync_nik" wire:model.defer="nik"
                                class="text-xs border border-slate-200 rounded-xl px-3 py-2.5 text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-400 bg-slate-50 disabled:opacity-60">
                                <option value="">All Employees</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->employee_id }}">
                                        {{ $emp->first_name }} {{ $emp->last_name }} ({{ $emp->employee_id }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @error('nik') <span class="text-red-500 text-[10px] block mt-1">{{ $message }}</span> @enderror

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                            <button type="button" wire:click="$set('showModal', false)"
                                class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold rounded-xl transition-colors disabled:opacity-50">
                                Cancel
                            </button>
                            <button type="submit"
                                wire:loading.attr="disabled"
                                class="px-4 py-2.5 bg-brand-600 hover:bg-brand-700 active:scale-95 disabled:opacity-60 text-white text-xs font-bold rounded-xl shadow-sm transition-all cursor-pointer border-0 inline-flex items-center gap-1.5">
                                <span wire:loading.remove class="inline-flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                                    </svg>
                                    Start Sync
                                </span>
                                <span wire:loading class="inline-flex items-center gap-1.5">
                                    <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Syncing…
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if (session()->has('sync_success'))
        <div class="px-6 py-3 bg-emerald-50 border-b border-emerald-100 text-emerald-700 text-xs font-bold">
            {{ session('sync_success') }}
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100">
            <thead>
                <tr class="bg-slate-50/60">
                    <th class="px-5 py-3 text-left text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Started At</th>
                    <th class="px-5 py-3 text-left text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Trigger</th>
                    <th class="px-5 py-3 text-left text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Date Range / NIK</th>
                    <th class="px-5 py-3 text-center text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Status</th>
                    <th class="px-5 py-3 text-center text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Records (F / P / S)</th>
                    <th class="px-5 py-3 text-left text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Details / Errors</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($syncLogs as $log)
                    @php
                        $statusClass = match($log->status) {
                            'success' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                            'failed'  => 'bg-red-50 text-red-700 border-red-100',
                            'running' => 'bg-amber-50 text-amber-700 border-amber-100 animate-pulse',
                            default   => 'bg-slate-50 text-slate-500 border-slate-100',
                        };
                        $triggerText = match($log->trigger_type) {
                            'manual'    => ($log->triggeredBy ? $log->triggeredBy->name : 'Manual'),
                            'scheduled' => 'System Schedule',
                            'cli'       => 'Developer CLI',
                            default     => ucfirst($log->trigger_type),
                        };
                        $params = $log->parameters;
                        $dateRange = ($params['date_from'] ?? '—') . ' to ' . ($params['date_to'] ?? '—');
                        $nikText = isset($params['nik']) ? " (NIK: {$params['nik']})" : ' (All)';
                    @endphp
                    <tr class="hover:bg-slate-50/40 transition-colors">
                        <td class="px-5 py-3 text-xs font-semibold text-slate-700">
                            {{ $log->started_at->timezone('Asia/Jakarta')->format('d M Y, H:i:s') }}
                            @if($log->ended_at)
                                <span class="block text-[9px] text-slate-400 font-medium">Duration: {{ $log->started_at->diffInSeconds($log->ended_at) }}s</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-xs text-slate-600 font-medium">
                            {{ $triggerText }}
                        </td>
                        <td class="px-5 py-3 text-xs text-slate-600 font-mono">
                            <span>{{ $dateRange }}</span>
                            <span class="text-slate-400">{{ $nikText }}</span>
                        </td>
                        <td class="px-5 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $statusClass }}">
                                {{ ucfirst($log->status) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-center text-xs font-mono">
                            <span class="text-slate-700 font-bold" title="Fetched">{{ $log->records_fetched ?? 0 }}</span>
                            <span class="text-slate-300 mx-0.5">/</span>
                            <span class="text-brand-600 font-bold" title="Processed">{{ $log->records_processed ?? 0 }}</span>
                            <span class="text-slate-300 mx-0.5">/</span>
                            <span class="text-emerald-600 font-bold" title="Saved">{{ $log->records_saved ?? 0 }}</span>
                        </td>
                        <td class="px-5 py-3 text-xs text-slate-500 font-mono truncate max-w-[200px]" title="{{ $log->error_message }}">
                            {{ $log->error_message ?? '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-50 mb-3 border border-slate-100">
                                <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                            </div>
                            <p class="text-sm font-semibold text-slate-400">No sync execution logs found.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
