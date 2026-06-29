<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-extrabold text-xl text-slate-900 leading-tight tracking-tight">API Logs</h2>
                <p class="text-xs text-slate-400 font-medium mt-0.5">System-wide API synchronization history</p>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Filters -->
        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Search API Name</label>
                    <input type="text" wire:model.live.debounce.300ms="searchApi" placeholder="e.g. jpayroll_attendance"
                        class="text-xs border border-slate-200 rounded-xl px-3 py-2.5 text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-400 bg-slate-50">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status</label>
                    <select wire:model.live="searchStatus"
                        class="text-xs border border-slate-200 rounded-xl px-3 py-2.5 text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-400 bg-slate-50">
                        <option value="">All Statuses</option>
                        <option value="running">Running</option>
                        <option value="success">Success</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Logs Table -->
        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden" @if($isRunning) wire:poll.5s @endif>
            <div class="px-6 pt-6 pb-4 border-b border-slate-100 flex items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <div class="p-2 rounded-xl bg-slate-50 border border-slate-100 text-slate-600">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">Execution History</h3>
                        <p class="text-[10px] text-slate-400 font-medium">Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }}</p>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead>
                        <tr class="bg-slate-50/60">
                            <th class="px-5 py-3 text-left text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Started At</th>
                            <th class="px-5 py-3 text-left text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">API</th>
                            <th class="px-5 py-3 text-left text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Trigger</th>
                            <th class="px-5 py-3 text-left text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Parameters</th>
                            <th class="px-5 py-3 text-center text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Status</th>
                            <th class="px-5 py-3 text-center text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Records (F / P / S)</th>
                            <th class="px-5 py-3 text-left text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Details / Errors</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($logs as $log)
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
                            @endphp
                            <tr class="hover:bg-slate-50/40 transition-colors">
                                <td class="px-5 py-3 text-xs font-semibold text-slate-700">
                                    {{ $log->started_at->timezone('Asia/Jakarta')->format('d M Y, H:i:s') }}
                                    @if($log->ended_at)
                                        <span class="block text-[9px] text-slate-400 font-medium">Duration: {{ $log->started_at->diffInSeconds($log->ended_at) }}s</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-xs text-slate-600 font-medium">
                                    {{ str_replace('_', ' ', Str::title($log->api_name)) }}
                                </td>
                                <td class="px-5 py-3 text-xs text-slate-600 font-medium">
                                    {{ $triggerText }}
                                </td>
                                <td class="px-5 py-3 text-[10px] text-slate-500 font-mono">
                                    @if($log->parameters && count($log->parameters) > 0)
                                        @foreach($log->parameters as $key => $val)
                                            <div class="truncate max-w-[150px]" title="{{ $key }}: {{ $val }}"><span class="font-bold">{{ $key }}:</span> {{ $val ?: '—' }}</div>
                                        @endforeach
                                    @else
                                        —
                                    @endif
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
                                <td class="px-5 py-3 text-xs text-slate-500 max-w-[200px] truncate" title="{{ $log->error_message }}">
                                    {{ $log->error_message ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center">
                                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-50 mb-3 border border-slate-100">
                                        <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-semibold text-slate-400">No API sync logs found matching your criteria.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($logs->hasPages())
                <div class="px-6 py-4 border-t border-slate-100">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
