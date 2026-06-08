<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-extrabold text-xl text-slate-900 leading-tight tracking-tight">Attendance</h2>
                <p class="text-xs text-slate-400 font-medium mt-0.5">Daily logs &amp; JPayroll synced data</p>
            </div>

            {{-- Last sync indicator + manual sync trigger (HR / Admin only) --}}
            @can('sync attendance')
                <div class="flex items-center gap-3">
                    @if($lastSync)
                        <span class="text-[10px] text-slate-500 font-medium hidden sm:block">
                            Last sync: {{ \Carbon\Carbon::parse($lastSync)->timezone(config('app.timezone'))->format('d M Y, H:i') }}
                        </span>
                    @else
                        <span class="text-[10px] text-slate-400 font-medium hidden sm:block">Never synced</span>
                    @endif

                    <form method="POST" action="{{ route('attendance.sync-jpayroll') }}" id="sync-form">
                        @csrf
                        <button type="submit"
                            id="sync-btn"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-brand-600 hover:bg-brand-700 active:scale-95 text-white text-xs font-bold rounded-xl shadow-sm transition-all cursor-pointer border-0"
                            onclick="event.preventDefault(); this.disabled=true; this.textContent='Syncing…'; this.form.submit();">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                            </svg>
                            Sync from JPayroll
                        </button>
                    </form>
                </div>
            @endcan
        </div>
    </x-slot>

    <div class="space-y-6">

        {{-- Flash messages --}}
        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl p-4 flex items-center gap-3 shadow-sm">
                <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-semibold">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 rounded-2xl p-4 flex items-center gap-3 shadow-sm">
                <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-semibold">{{ session('error') }}</span>
            </div>
        @endif

        {{-- ── SECTION 1: JPayroll Attendance Summary ──────────────────────── --}}
        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">

            {{-- Card header + date filter --}}
            <div class="px-6 pt-6 pb-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <div class="p-2 rounded-xl bg-brand-50 border border-brand-100 text-brand-600">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">JPayroll Daily Summary</h3>
                        <p class="text-[10px] text-slate-400 font-medium">Synced from payroll system</p>
                    </div>
                </div>

                {{-- Date range filter form --}}
                <form method="GET" action="{{ route('attendance.index') }}"
                    class="flex flex-wrap items-center gap-2" id="date-filter-form">
                    <div class="flex items-center gap-1.5">
                        <label for="date_from" class="text-[10px] font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">From</label>
                        <input type="date" id="date_from" name="date_from"
                            value="{{ $dateFrom }}"
                            class="text-xs border border-slate-200 rounded-lg px-2.5 py-1.5 text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-400 bg-slate-50">
                    </div>
                    <div class="flex items-center gap-1.5">
                        <label for="date_to" class="text-[10px] font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">To</label>
                        <input type="date" id="date_to" name="date_to"
                            value="{{ $dateTo }}"
                            class="text-xs border border-slate-200 rounded-lg px-2.5 py-1.5 text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-400 bg-slate-50">
                    </div>
                    <button type="submit"
                        class="px-3 py-1.5 bg-brand-600 hover:bg-brand-700 text-white text-[10px] font-bold rounded-lg transition-colors cursor-pointer border-0">
                        Filter
                    </button>
                    <a href="{{ route('attendance.index') }}"
                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 text-[10px] font-bold rounded-lg transition-colors">
                        Reset
                    </a>
                </form>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead>
                        <tr class="bg-slate-50/60">
                            @can('manage attendance')
                                <th class="px-5 py-3 text-left text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Employee</th>
                            @endcan
                            <th class="px-5 py-3 text-left text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Date</th>
                            <th class="px-5 py-3 text-center text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Status</th>
                            <th class="px-5 py-3 text-center text-[10px] font-extrabold text-red-400 uppercase tracking-wider" title="Absent without leave">Alpha</th>
                            <th class="px-5 py-3 text-center text-[10px] font-extrabold text-amber-400 uppercase tracking-wider" title="Late arrival">Late</th>
                            <th class="px-5 py-3 text-center text-[10px] font-extrabold text-indigo-400 uppercase tracking-wider" title="Approved leave / Cuti">Leave</th>
                            <th class="px-5 py-3 text-center text-[10px] font-extrabold text-slate-400 uppercase tracking-wider" title="Other permitted">OP</th>
                            <th class="px-5 py-3 text-center text-[10px] font-extrabold text-orange-400 uppercase tracking-wider" title="Sick leave">Sick</th>
                            <th class="px-5 py-3 text-center text-[10px] font-extrabold text-rose-400 uppercase tracking-wider" title="Work accident">WA</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($jpayrollLogs as $log)
                            @php
                                $statusLabel = $log->statusLabel();
                                $statusClass = match($statusLabel) {
                                    'Present'    => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                    'Absent'     => 'bg-red-50 text-red-700 border-red-100',
                                    'Late'       => 'bg-amber-50 text-amber-700 border-amber-100',
                                    'Leave'      => 'bg-indigo-50 text-indigo-700 border-indigo-100',
                                    'Sick / WA'  => 'bg-orange-50 text-orange-700 border-orange-100',
                                    'Permitted'  => 'bg-slate-50 text-slate-600 border-slate-200',
                                    default      => 'bg-slate-50 text-slate-500 border-slate-100',
                                };
                            @endphp
                            <tr class="hover:bg-slate-50/40 transition-colors group">
                                @can('manage attendance')
                                    <td class="px-5 py-3">
                                        <span class="text-xs font-semibold text-slate-700">
                                            {{ $log->employee->first_name ?? '—' }} {{ $log->employee->last_name ?? '' }}
                                        </span>
                                        <span class="text-[10px] text-slate-400 font-mono ml-1">{{ $log->employee->employee_id ?? '' }}</span>
                                    </td>
                                @endcan
                                <td class="px-5 py-3">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-slate-800">{{ $log->shift_date->format('d M Y') }}</span>
                                        <span class="text-[10px] text-slate-400">{{ $log->shift_date->format('l') }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $statusClass }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <span class="text-xs font-bold {{ $log->alpha > 0 ? 'text-red-600' : 'text-slate-300' }}">
                                        {{ $log->alpha }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <span class="text-xs font-bold {{ $log->telat > 0 ? 'text-amber-600' : 'text-slate-300' }}">
                                        {{ $log->telat }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <span class="text-xs font-bold {{ $log->izin > 0 ? 'text-indigo-600' : 'text-slate-300' }}">
                                        {{ $log->izin }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <span class="text-xs font-bold {{ $log->op > 0 ? 'text-slate-600' : 'text-slate-300' }}">
                                        {{ $log->op }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <span class="text-xs font-bold {{ ($log->hos > 0 || $log->hoswa > 0) ? 'text-orange-600' : 'text-slate-300' }}">
                                        {{ $log->hos + $log->hoswa }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <span class="text-xs font-bold {{ $log->wa > 0 ? 'text-rose-600' : 'text-slate-300' }}">
                                        {{ $log->wa }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-5 py-12 text-center">
                                    <div class="flex flex-col items-center gap-2 text-slate-400">
                                        <svg class="w-10 h-10 text-slate-200" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                                        </svg>
                                        <p class="text-sm font-semibold text-slate-500">No attendance records for this date range</p>
                                        @can('sync attendance')
                                            <p class="text-xs text-slate-400">Click <strong>Sync from JPayroll</strong> to fetch data.</p>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($jpayrollLogs->hasPages())
                <div class="px-6 py-4 border-t border-slate-100">
                    {{ $jpayrollLogs->appends(request()->query())->links() }}
                </div>
            @endif
        </div>

        {{-- ── SECTION 2: Manual Clock-In / Clock-Out Log ───────────────────── --}}
        @can('view attendance')
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">

                <div class="px-6 pt-6 pb-4 border-b border-slate-100 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-2">
                        <div class="p-2 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-600">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-900">Manual Clock Log</h3>
                            <p class="text-[10px] text-slate-400 font-medium">Your clock-in &amp; clock-out records</p>
                        </div>
                    </div>

                    {{-- Clock In / Out actions --}}
                    <div class="flex gap-2 flex-shrink-0">
                        <form method="POST" action="{{ route('attendance.clock-in') }}">
                            @csrf
                            <button type="submit"
                                class="px-4 py-2 bg-brand-600 hover:bg-brand-700 active:scale-95 text-white text-xs font-bold rounded-xl shadow-sm transition-all cursor-pointer border-0">
                                Clock In
                            </button>
                        </form>
                        <form method="POST" action="{{ route('attendance.clock-out') }}">
                            @csrf
                            <button type="submit"
                                class="px-4 py-2 bg-red-500 hover:bg-red-600 active:scale-95 text-white text-xs font-bold rounded-xl shadow-sm transition-all cursor-pointer border-0">
                                Clock Out
                            </button>
                        </form>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead>
                            <tr class="bg-slate-50/60">
                                <th class="px-5 py-3 text-left text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Date</th>
                                <th class="px-5 py-3 text-left text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Clock In</th>
                                <th class="px-5 py-3 text-left text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Clock Out</th>
                                <th class="px-5 py-3 text-left text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Duration</th>
                                <th class="px-5 py-3 text-left text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Note</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($manualLogs as $log)
                                @php
                                    $duration = '—';
                                    if ($log->clock_in_at && $log->clock_out_at) {
                                        $mins = $log->clock_in_at->diffInMinutes($log->clock_out_at);
                                        $h = intdiv($mins, 60);
                                        $m = $mins % 60;
                                        $duration = $h > 0 ? "{$h}h {$m}m" : "{$m}m";
                                    }
                                @endphp
                                <tr class="hover:bg-slate-50/40 transition-colors">
                                    <td class="px-5 py-3 text-xs font-bold text-slate-800">
                                        {{ $log->clock_in_at?->format('d M Y') ?? '—' }}
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="text-xs font-semibold text-emerald-700">
                                            {{ $log->clock_in_at?->format('H:i:s') ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3">
                                        @if($log->clock_out_at)
                                            <span class="text-xs font-semibold text-slate-700">{{ $log->clock_out_at->format('H:i:s') }}</span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded-full">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                                Active
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-xs text-slate-500 font-medium">{{ $duration }}</td>
                                    <td class="px-5 py-3 text-xs text-slate-400">{{ $log->note ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-10 text-center">
                                        <p class="text-sm font-semibold text-slate-400">No clock records yet.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($manualLogs instanceof \Illuminate\Pagination\LengthAwarePaginator && $manualLogs->hasPages())
                    <div class="px-6 py-4 border-t border-slate-100">
                        {{ $manualLogs->links() }}
                    </div>
                @endif
            </div>
        @endcan

    </div>
</x-app-layout>
