<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-extrabold text-xl text-slate-900 leading-tight tracking-tight">Attendance</h2>
                <p class="text-xs text-slate-400 font-medium mt-0.5">Daily logs &amp; JPayroll synced data</p>
            </div>

            @can('sync attendance')
                <!-- Space for layout consistency -->
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

        {{-- ── SUMMARY REPORT CARDS ────────────────────────────────────────── --}}
        @if(isset($summary))
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-sm flex flex-col justify-center">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="p-1.5 rounded-lg bg-slate-50 text-slate-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Total Days</span>
                    </div>
                    <span class="text-2xl font-black text-slate-800">{{ (int) $summary->total_days }}</span>
                </div>
                <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-sm flex flex-col justify-center border-b-4 border-b-emerald-400 relative overflow-hidden">
                    <div class="absolute -right-4 -top-4 opacity-5">
                        <svg class="w-24 h-24 text-emerald-900" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    </div>
                    <div class="flex items-center gap-2 mb-2 relative z-10">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span class="text-[10px] font-extrabold text-emerald-600 uppercase tracking-wider">Present</span>
                    </div>
                    <span class="text-2xl font-black text-slate-800 relative z-10">{{ (int) $summary->present_days }}</span>
                </div>
                <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-sm flex flex-col justify-center border-b-4 border-b-red-400 relative overflow-hidden">
                    <div class="absolute -right-4 -top-4 opacity-5">
                        <svg class="w-24 h-24 text-red-900" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                    </div>
                    <div class="flex items-center gap-2 mb-2 relative z-10">
                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                        <span class="text-[10px] font-extrabold text-red-600 uppercase tracking-wider">Absent</span>
                    </div>
                    <span class="text-2xl font-black text-slate-800 relative z-10">{{ (int) $summary->absent_days }}</span>
                </div>
                <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-sm flex flex-col justify-center border-b-4 border-b-amber-400 relative overflow-hidden">
                    <div class="absolute -right-4 -top-4 opacity-5">
                        <svg class="w-24 h-24 text-amber-900" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path></svg>
                    </div>
                    <div class="flex items-center gap-2 mb-2 relative z-10">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        <span class="text-[10px] font-extrabold text-amber-600 uppercase tracking-wider">Late</span>
                    </div>
                    <span class="text-2xl font-black text-slate-800 relative z-10">{{ (int) $summary->late_days }}</span>
                </div>
                <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-sm flex flex-col justify-center border-b-4 border-b-indigo-400 relative overflow-hidden">
                    <div class="absolute -right-4 -top-4 opacity-5">
                        <svg class="w-24 h-24 text-indigo-900" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path></svg>
                    </div>
                    <div class="flex items-center gap-2 mb-2 relative z-10">
                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                        <span class="text-[10px] font-extrabold text-indigo-600 uppercase tracking-wider">Leave / Sick</span>
                    </div>
                    <span class="text-2xl font-black text-slate-800 relative z-10">{{ (int) ($summary->leave_days + $summary->sick_days + $summary->permitted_days) }}</span>
                </div>
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

                {{-- Advanced filter form --}}
                <form method="GET" action="{{ route('attendance.index') }}"
                    class="grid grid-cols-1 sm:grid-cols-2 {{ Auth::user()->can('manage attendance') ? 'lg:grid-cols-5' : 'lg:grid-cols-4' }} gap-3 w-full" id="date-filter-form">
                    
                    <div class="flex flex-col gap-1.5">
                        <label for="date_from" class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">From</label>
                        <input type="date" id="date_from" name="date_from"
                            value="{{ $dateFrom }}"
                            class="text-xs border border-slate-200 rounded-xl px-3 py-2 text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-400 bg-slate-50">
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="date_to" class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">To</label>
                        <input type="date" id="date_to" name="date_to"
                            value="{{ $dateTo }}"
                            class="text-xs border border-slate-200 rounded-xl px-3 py-2 text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-400 bg-slate-50">
                    </div>

                    @can('manage attendance')
                        <div class="flex flex-col gap-1.5">
                            <label for="employee_id" class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Employee</label>
                            <select id="employee_id" name="employee_id"
                                class="text-xs border border-slate-200 rounded-xl px-3 py-2 text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-400 bg-slate-50">
                                <option value="">All Employees</option>
                                <optgroup label="Active Employees">
                                    @foreach($employees->where('status', 'active') as $emp)
                                        <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                                            {{ $emp->first_name }} {{ $emp->last_name }} ({{ $emp->employee_id }})
                                        </option>
                                    @endforeach
                                </optgroup>
                                <optgroup label="Inactive Employees">
                                    @foreach($employees->where('status', '!=', 'active') as $emp)
                                        <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                                            {{ $emp->first_name }} {{ $emp->last_name }} ({{ $emp->employee_id }}) ({{ ucfirst($emp->status) }})
                                        </option>
                                    @endforeach
                                </optgroup>
                            </select>
                        </div>
                    @endcan

                    <div class="flex flex-col gap-1.5">
                        <label for="status" class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status</label>
                        <select id="status" name="status"
                            class="text-xs border border-slate-200 rounded-xl px-3 py-2 text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-400 bg-slate-50">
                            <option value="all" {{ request('status') === 'all' || !request()->has('status') ? 'selected' : '' }}>All Statuses</option>
                            <option value="present" {{ request('status') === 'present' ? 'selected' : '' }}>Present</option>
                            <option value="absent" {{ request('status') === 'absent' ? 'selected' : '' }}>Absent</option>
                            <option value="late" {{ request('status') === 'late' ? 'selected' : '' }}>Late</option>
                            <option value="leave" {{ request('status') === 'leave' ? 'selected' : '' }}>Leave</option>
                            <option value="sick" {{ request('status') === 'sick' ? 'selected' : '' }}>Sick</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2 pt-5">
                        <button type="submit"
                            class="flex-1 px-4 py-2 bg-brand-600 hover:bg-brand-700 active:scale-95 text-white text-xs font-bold rounded-xl shadow-sm transition-all cursor-pointer border-0 text-center">
                            Filter
                        </button>
                        <a href="{{ route('attendance.index') }}"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold rounded-xl transition-all text-center">
                            Reset
                        </a>
                    </div>
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
                            <th class="px-5 py-3 text-center text-[10px] font-extrabold text-orange-400 uppercase tracking-wider" title="Sick leave">Sick</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($jpayrollLogs as $log)
                            @php
                                $statusLabel = $log->statusLabel();
                                $statusClass = match($statusLabel) {
                                    'Present' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                    'Absent' => 'bg-red-50 text-red-700 border-red-100',
                                    'Late' => 'bg-amber-50 text-amber-700 border-amber-100',
                                    'Leave' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
                                    'Sick' => 'bg-orange-50 text-orange-700 border-orange-100',
                                    default => 'bg-slate-50 text-slate-500 border-slate-100',
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
                                    <span class="text-xs font-bold {{ $log->sakit > 0 ? 'text-orange-600' : 'text-slate-300' }}">
                                        {{ $log->sakit }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ Auth::user()->can('manage attendance') ? 7 : 6 }}" class="px-5 py-12 text-center">
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
                                <tr class="hover:bg-slate-50/40 transition-colors">
                                    <td class="px-5 py-3 text-xs font-bold text-slate-800">
                                        {{ $log->clock_in_at ? $log->clock_in_at->timezone('Asia/Jakarta')->format('d M Y') : '—' }}
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="text-xs font-semibold text-emerald-700">
                                            {{ $log->clock_in_at ? $log->clock_in_at->timezone('Asia/Jakarta')->format('H:i:s') : '—' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3">
                                        @if($log->clock_out_at)
                                            <span class="text-xs font-semibold text-slate-700">{{ $log->clock_out_at->timezone('Asia/Jakarta')->format('H:i:s') }}</span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded-full">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                                Active
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-xs text-slate-500 font-medium">{{ $log->duration }}</td>
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

        {{-- ── SECTION 3: JPayroll Sync History ────────────────────── --}}
        @can('sync attendance')
            <livewire:attendance.sync-jpayroll />
        @endcan

    </div>

    @push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <style>
        .ts-control {
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 0.625rem 0.75rem;
            font-size: 0.75rem;
            background-color: #f8fafc;
        }
        .ts-control > input {
            font-size: 0.75rem;
        }
    </style>
    @endpush

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            if (document.getElementById('employee_id')) {
                new TomSelect('#employee_id', {
                    create: false,
                    sortField: { field: "text", direction: "asc" }
                });
            }
            if (document.getElementById('sync_nik')) {
                new TomSelect('#sync_nik', {
                    create: false,
                    sortField: { field: "text", direction: "asc" }
                });
            }
        });
    </script>
    @endpush
</x-app-layout>
