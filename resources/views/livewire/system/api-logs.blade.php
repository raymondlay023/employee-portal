<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-extrabold text-xl text-slate-900 leading-tight tracking-tight">{{ __('API Logs') }}</h2>
                <p class="text-xs text-slate-400 font-medium mt-0.5">{{ __('System-wide API synchronization history') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Alerts -->
        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 rounded-3xl p-4 flex items-center gap-3 shadow-sm text-xs font-semibold">
                <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Filters -->
        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">{{ __('Search API Name') }}</label>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('e.g. jpayroll_attendance') }}"
                        class="text-xs border border-slate-200 rounded-xl px-3 py-2.5 text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-400 bg-slate-50">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">{{ __('Status') }}</label>
                    <select wire:model.live="searchStatus"
                        class="text-xs border border-slate-200 rounded-xl px-3 py-2.5 text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-400 bg-slate-50">
                        <option value="">{{ __('All Statuses') }}</option>
                        <option value="running">{{ __('Running') }}</option>
                        <option value="success">{{ __('Success') }}</option>
                        <option value="failed">{{ __('Failed') }}</option>
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
                        <h3 class="text-sm font-extrabold text-slate-900">{{ __('Execution History') }}</h3>
                        <p class="text-[10px] text-slate-400 font-medium">{{ __('Page :current of :last', ['current' => $logs->currentPage(), 'last' => $logs->lastPage()]) }}</p>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead>
                        <tr class="bg-slate-50/60">
                             <th class="px-5 py-3 text-left text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">{{ __('Started At') }}</th>
                            <th class="px-5 py-3 text-left text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">{{ __('API') }}</th>
                            <th class="px-5 py-3 text-left text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">{{ __('Trigger') }}</th>
                            <th class="px-5 py-3 text-left text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">{{ __('Parameters') }}</th>
                            <th class="px-5 py-3 text-center text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">{{ __('Status') }}</th>
                            <th class="px-5 py-3 text-center text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">{{ __('Records (F / P / S)') }}</th>
                            <th class="px-5 py-3 text-left text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">{{ __('Details / Errors') }}</th>
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
                                    'manual'    => ($log->triggeredBy ? $log->triggeredBy->name : __('Manual')),
                                    'scheduled' => __('System Schedule'),
                                    'cli'       => __('Developer CLI'),
                                    default     => ucfirst($log->trigger_type),
                                };
                            @endphp
                            <tr class="hover:bg-slate-50/40 transition-colors">
                                <td class="px-5 py-3 text-xs font-semibold text-slate-700">
                                    {{ $log->started_at->timezone('Asia/Jakarta')->format('d M Y, H:i:s') }}
                                     @if($log->ended_at)
                                         <span class="block text-[9px] text-slate-400 font-medium">{{ __('Duration: :sec s', ['sec' => $log->started_at->diffInSeconds($log->ended_at)]) }}</span>
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
                                            @if($key === 'raw_payloads' && is_array($val))
                                                <div class="mt-1 space-y-1">
                                                    <span class="font-bold block text-slate-400 uppercase tracking-wider text-[9px]">{{ __('Raw Payloads:') }}</span>
                                                    @foreach($val as $device => $path)
                                                        @php
                                                            $fileExists = \Illuminate\Support\Facades\Storage::exists($path);
                                                        @endphp
                                                        @if($fileExists)
                                                            <button wire:click="downloadPayload('{{ $path }}')" class="inline-flex items-center gap-1 text-[9px] font-bold text-brand-600 hover:text-brand-800 hover:underline bg-brand-50 border border-brand-100 px-1.5 py-0.5 rounded cursor-pointer transition-all hover:bg-brand-100">
                                                                <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                                                </svg>
                                                                {{ $device }}
                                                            </button>
                                                        @else
                                                            <span class="inline-flex items-center gap-1 text-[9px] font-bold text-slate-400 bg-slate-50 border border-slate-200 px-1.5 py-0.5 rounded cursor-not-allowed line-through" title="{{ __('File missing or pruned') }}">
                                                                {{ $device }}
                                                            </span>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @elseif($key === 'raw_payloads_pruned')
                                                <div class="mt-1">
                                                    <span class="inline-flex items-center gap-1 text-[9px] font-bold text-slate-400 bg-slate-50 border border-slate-200 px-1.5 py-0.5 rounded" title="{{ __('Raw XML payloads have been deleted to save space') }}">
                                                        {{ __('Backups Pruned') }}
                                                    </span>
                                                </div>
                                            @else
                                                @php
                                                    $displayVal = is_array($val) ? json_encode($val) : $val;
                                                @endphp
                                                <div class="truncate max-w-[150px]" title="{{ $key }}: {{ $displayVal }}"><span class="font-bold">{{ $key }}:</span> {{ $displayVal ?: '—' }}</div>
                                            @endif
                                        @endforeach
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $statusClass }}">
                                        {{ __($log->status) }}
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
                                    <p class="text-sm font-semibold text-slate-400">{{ __('No API sync logs found matching your criteria.') }}</p>
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
