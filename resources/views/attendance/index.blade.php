<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-extrabold text-xl text-slate-900 leading-tight tracking-tight">{{ $targetEmployee ? __('Attendance - :name', ['name' => $targetEmployee->first_name . ' ' . $targetEmployee->last_name]) : __('Attendance') }}</h2>
                <p class="text-xs text-slate-400 font-medium mt-0.5">{{ __('Daily logs & JPayroll synced data') }}</p>
            </div>

            @can(\App\Authorization\Permissions::MANAGE_ATTENDANCE)
                <!-- Space for layout consistency -->
            @endcan
        </div>
    </x-slot>

    <div class="space-y-6">

        {{-- Flash messages --}}
        @if(isset($unlinkedProfileError) && $unlinkedProfileError)
            <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-2xl p-4 flex items-start gap-3 shadow-sm">
                <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <h3 class="font-bold text-sm">{{ __('Employee Profile Not Linked') }}</h3>
                    <p class="text-xs mt-0.5">{{ __('Your user account is not linked to an employee profile. Please contact an administrator. You can still manage other employees via the Attendance Report if you have permission.') }}</p>
                </div>
            </div>
        @endif

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

        {{-- ── GLOBAL CONTROL & FILTER BAR ─────────────────────────────────── --}}
        <form method="GET" action="{{ route('attendance.index') }}"
            id="attendance-filter-form"
            x-data="{ loading: false }"
            @change="loading = true; $el.submit()"
            class="bg-white rounded-3xl p-5 border border-slate-100 shadow-sm relative overflow-hidden">

            <!-- Loading Backdrop & Spinner Overlay -->
            <div x-show="loading" x-transition class="absolute inset-0 bg-white/80 backdrop-blur-xs flex items-center justify-center z-20" style="display:none;" x-cloak>
                <div class="flex items-center gap-2.5 text-brand-600 font-bold text-xs">
                    <svg class="animate-spin h-4 w-4 text-brand-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>{{ __('Updating Attendance Logs...') }}</span>
                </div>
            </div>

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <!-- Dropdown Select Controls -->
                <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                    <!-- Month Select -->
                    <div class="flex flex-col gap-1">
                        <label for="month" class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">{{ __('Month') }}</label>
                        <select id="month" name="month"
                            class="text-xs border border-slate-200 rounded-xl px-3 py-2 text-slate-700 font-bold bg-slate-50 focus:ring-2 focus:ring-brand-300 focus:border-brand-400">
                            @foreach($availableMonths as $m)
                                <option value="{{ $m }}" {{ $month === $m ? 'selected' : '' }}>{{ __(date('F', mktime(0, 0, 0, $m, 1))) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Year Select -->
                    <div class="flex flex-col gap-1">
                        <label for="year" class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">{{ __('Year') }}</label>
                        <select id="year" name="year"
                            class="text-xs border border-slate-200 rounded-xl px-3 py-2 text-slate-700 font-bold bg-slate-50 focus:ring-2 focus:ring-brand-300 focus:border-brand-400">
                            @foreach($availableYears as $y)
                                <option value="{{ $y }}" {{ $year === $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>

                    @can(\App\Authorization\Permissions::MANAGE_ATTENDANCE)
                        <input type="hidden" name="employee_id" value="{{ $targetEmployee->id ?? '' }}">
                    @endcan

                    <!-- Status Select -->
                    <div class="flex flex-col gap-1">
                        <label for="status" class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">{{ __('Status') }}</label>
                        <select id="status" name="status"
                            class="text-xs border border-slate-200 rounded-xl px-3 py-2 text-slate-700 font-bold bg-slate-50 focus:ring-2 focus:ring-brand-300 focus:border-brand-400">
                            <option value="all" {{ request('status') === 'all' || !request()->has('status') ? 'selected' : '' }}>{{ __('All Statuses') }}</option>
                            <option value="present" {{ request('status') === 'present' ? 'selected' : '' }}>{{ __('Present') }}</option>
                            <option value="absent" {{ request('status') === 'absent' ? 'selected' : '' }}>{{ __('Absent') }}</option>
                            <option value="late" {{ request('status') === 'late' ? 'selected' : '' }}>{{ __('Late') }}</option>
                            <option value="leave" {{ request('status') === 'leave' ? 'selected' : '' }}>{{ __('Leave') }}</option>
                            <option value="sick" {{ request('status') === 'sick' ? 'selected' : '' }}>{{ __('Sick') }}</option>
                        </select>
                    </div>

                    <!-- Reset Link -->
                    <div class="flex items-end pt-4">
                        <a href="{{ route('attendance.index') }}"
                            class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold rounded-xl transition-all">
                            {{ __('Reset') }}
                        </a>
                    </div>
                </div>

                <!-- Last Sync Status Badges -->
                <div class="flex flex-wrap items-center gap-2 text-right">
                    @if ($jpayrollLastSync)
                        <span class="text-[11px] text-slate-500 font-medium bg-slate-50 border border-slate-200/80 px-3 py-1.5 rounded-xl shadow-xs">
                            <strong class="text-slate-700 font-extrabold">{{ __('JPayroll Sync:') }}</strong> {{ \Carbon\Carbon::parse($jpayrollLastSync)->timezone('Asia/Jakarta')->diffForHumans() }}
                        </span>
                    @endif
                    @if ($biometricLastSync)
                        <span class="text-[11px] text-slate-500 font-medium bg-slate-50 border border-slate-200/80 px-3 py-1.5 rounded-xl shadow-xs">
                            <strong class="text-slate-700 font-extrabold">{{ __('Biometric Sync:') }}</strong> {{ \Carbon\Carbon::parse($biometricLastSync)->timezone('Asia/Jakarta')->diffForHumans() }}
                        </span>
                    @endif
                </div>
            </div>
        </form>

        {{-- ── SUMMARY REPORT CARDS ────────────────────────────────────────── --}}
        @if(isset($summary))
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-sm flex flex-col justify-center">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="p-1.5 rounded-lg bg-slate-50 text-slate-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">{{ __('Total Days') }}</span>
                    </div>
                    <span class="text-2xl font-black text-slate-800">{{ (int) $summary->total_days }}</span>
                </div>
                <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-sm flex flex-col justify-center border-b-4 border-b-emerald-400 relative overflow-hidden">
                    <div class="absolute -right-4 -top-4 opacity-5">
                        <svg class="w-24 h-24 text-emerald-900" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    </div>
                    <div class="flex items-center gap-2 mb-2 relative z-10">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span class="text-[10px] font-extrabold text-emerald-600 uppercase tracking-wider">{{ __('Present') }}</span>
                    </div>
                    <span class="text-2xl font-black text-slate-800 relative z-10">{{ (int) $summary->present_days }}</span>
                </div>
                <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-sm flex flex-col justify-center border-b-4 border-b-red-400 relative overflow-hidden">
                    <div class="absolute -right-4 -top-4 opacity-5">
                        <svg class="w-24 h-24 text-red-900" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                    </div>
                    <div class="flex items-center gap-2 mb-2 relative z-10">
                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                        <span class="text-[10px] font-extrabold text-red-600 uppercase tracking-wider">{{ __('Absent') }}</span>
                    </div>
                    <span class="text-2xl font-black text-slate-800 relative z-10">{{ (int) $summary->absent_days }}</span>
                </div>
                <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-sm flex flex-col justify-center border-b-4 border-b-amber-400 relative overflow-hidden">
                    <div class="absolute -right-4 -top-4 opacity-5">
                        <svg class="w-24 h-24 text-amber-900" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path></svg>
                    </div>
                    <div class="flex items-center gap-2 mb-2 relative z-10">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        <span class="text-[10px] font-extrabold text-amber-600 uppercase tracking-wider">{{ __('Late') }}</span>
                    </div>
                    <span class="text-2xl font-black text-slate-800 relative z-10">{{ (int) $summary->late_days }}</span>
                </div>
                <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-sm flex flex-col justify-center border-b-4 border-b-purple-400 relative overflow-hidden">
                    <div class="absolute -right-4 -top-4 opacity-5">
                        <svg class="w-24 h-24 text-purple-900" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path></svg>
                    </div>
                    <div class="flex items-center gap-2 mb-2 relative z-10">
                        <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                        <span class="text-[10px] font-extrabold text-purple-600 uppercase tracking-wider">{{ __('Sick') }}</span>
                    </div>
                    <span class="text-2xl font-black text-slate-800 relative z-10">{{ (int) $summary->sick_days }}</span>
                </div>
                <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-sm flex flex-col justify-center border-b-4 border-b-blue-400 relative overflow-hidden">
                    <div class="absolute -right-4 -top-4 opacity-5">
                        <svg class="w-24 h-24 text-blue-900" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"></path></svg>
                    </div>
                    <div class="flex items-center gap-2 mb-2 relative z-10">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                        <span class="text-[10px] font-extrabold text-blue-600 uppercase tracking-wider">{{ __('Leave') }}</span>
                    </div>
                    <span class="text-2xl font-black text-slate-800 relative z-10">{{ (int) ($summary->leave_days + ($summary->permitted_days ?? 0)) }}</span>
                </div>
            </div>
        @endif

        {{-- ── SECTION 1: JPayroll Attendance Summary ──────────────────────── --}}
        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">

            {{-- Card header --}}
            <div class="px-6 pt-6 pb-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <div class="p-2 rounded-xl bg-brand-50 border border-brand-100 text-brand-600">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">{{ __('JPayroll Daily Summary') }}</h3>
                        <p class="text-[10px] text-slate-400 font-medium">{{ __('Synced from payroll system') }}</p>
                    </div>
                </div>

                @if ($jpayrollLastSync)
                    <div class="text-[10px] font-semibold text-slate-400 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span>{{ __('Last Synced:') }} {{ \Carbon\Carbon::parse($jpayrollLastSync)->timezone('Asia/Jakarta')->diffForHumans() }}</span>
                    </div>
                @endif
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead>
                        <tr class="bg-slate-50/60">
                            <th class="px-5 py-4 text-left text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">{{ __('Date') }}</th>
                            <th class="px-5 py-4 text-center text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">{{ __('Status') }}</th>
                            <th class="px-5 py-4 text-center text-[10px] font-extrabold text-slate-500 uppercase tracking-wider" title="Absent without leave">{{ __('Absent') }}</th>
                            <th class="px-5 py-4 text-center text-[10px] font-extrabold text-slate-500 uppercase tracking-wider" title="Late arrival">{{ __('Late') }}</th>
                            <th class="px-5 py-4 text-center text-[10px] font-extrabold text-slate-500 uppercase tracking-wider" title="Sick leave">{{ __('Sick') }}</th>
                            <th class="px-5 py-4 text-center text-[10px] font-extrabold text-slate-500 uppercase tracking-wider" title="Approved leave / Cuti">{{ __('Leave') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($jpayrollLogs as $log)
                            @php
                                $statusLabel = $log->statusLabel();
                                $statusClass = match($statusLabel) {
                                    'Present' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                    'Absent', 'Absent (No Biometric)' => 'bg-red-50 text-red-700 border-red-100',
                                    'Late' => 'bg-amber-50 text-amber-700 border-amber-100',
                                    'Leave' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
                                    'Sick' => 'bg-orange-50 text-orange-700 border-orange-100',
                                    'Off Day' => 'bg-slate-50 text-slate-500 border-slate-100',
                                    default => 'bg-slate-50 text-slate-500 border-slate-100',
                                };
                            @endphp
                            <tr class="hover:bg-slate-50/40 transition-colors group">
                                <td class="px-5 py-4">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-slate-800">{{ $log->shift_date->translatedFormat('d M Y') }}</span>
                                        <span class="text-[10px] text-slate-400">{{ $log->shift_date->translatedFormat('l') }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $statusClass }}">
                                            {{ __($statusLabel) }}
                                        </span>
                                        @if($log->getAbnormality())
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200 shadow-sm shrink-0 cursor-help" title="{{ __($log->getAbnormality()) }}">
                                                <svg class="w-3.5 h-3.5 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                                </svg>
                                                {{ __('Conflict') }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span class="inline-flex items-center justify-center min-w-[2rem] px-1 py-0.5 {{ $log->alpha > 0 ? 'bg-red-50 text-red-700 border-red-200/60 shadow-sm' : 'text-slate-400 bg-slate-50/50' }} font-bold text-xs rounded border border-transparent">
                                        {{ $log->alpha }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span class="inline-flex items-center justify-center min-w-[2rem] px-1 py-0.5 {{ $log->telat > 0 ? 'bg-amber-50 text-amber-700 border-amber-200/60 shadow-sm' : 'text-slate-400 bg-slate-50/50' }} font-bold text-xs rounded border border-transparent">
                                        {{ $log->telat }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span class="inline-flex items-center justify-center min-w-[2rem] px-1 py-0.5 {{ $log->sakit > 0 ? 'bg-purple-50 text-purple-700 border-purple-200/60 shadow-sm' : 'text-slate-400 bg-slate-50/50' }} font-bold text-xs rounded border border-transparent">
                                        {{ $log->sakit }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span class="inline-flex items-center justify-center min-w-[2rem] px-1 py-0.5 {{ $log->izin > 0 ? 'bg-blue-50 text-blue-700 border-blue-200/60 shadow-sm' : 'text-slate-400 bg-slate-50/50' }} font-bold text-xs rounded border border-transparent">
                                        {{ $log->izin }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center">
                                    <div class="flex flex-col items-center gap-2 text-slate-400">
                                        <svg class="w-10 h-10 text-slate-200" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                                        </svg>
                                        <p class="text-sm font-semibold text-slate-500">{{ __('No attendance records found') }}</p>
                                         @can(\App\Authorization\Permissions::MANAGE_ATTENDANCE)
                                             <p class="text-xs text-slate-400">{!! __('Click :strong_openSync from JPayroll:strong_close to fetch data.', ['strong_open' => '<strong>', 'strong_close' => '</strong>']) !!}</p>
                                         @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── SECTION 2: Biometric Punch Logs ─────────────────────────────── --}}
        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">

            <div class="px-6 pt-6 pb-4 border-b border-slate-100 flex items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <div class="p-2 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-600">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">{{ __('Biometric Punch Logs') }}</h3>
                        <p class="text-[10px] text-slate-400 font-medium">{{ __('Raw clock-in & clock-out punches from device scans') }}</p>
                    </div>
                </div>

                {{-- Clock In / Out actions --}}
                @if(config('app.enable_manual_attendance'))
                    <div class="flex flex-col sm:flex-row gap-3 mt-4 lg:mt-0 lg:ml-auto">
                        @if (!$todayLog)
                            <form action="{{ route('attendance.clock-in') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-2.5 border border-transparent text-sm font-bold rounded-xl shadow-sm text-white bg-brand-600 hover:bg-brand-700 hover:-translate-y-0.5 transform transition-all">
                                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                    </svg>
                                    {{ __('Clock In') }}
                                </button>
                            </form>
                        @else
                            <form action="{{ route('attendance.clock-out') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-2.5 border border-transparent text-sm font-bold rounded-xl shadow-sm text-white bg-red-600 hover:bg-red-700 hover:-translate-y-0.5 transform transition-all">
                                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    {{ __('Clock Out') }}
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead>
                        <tr class="bg-slate-50/60">
                            <th class="px-5 py-3 text-left text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">{{ __('Date') }}</th>
                            <th class="px-5 py-3 text-left text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">{{ __('Clock In') }}</th>
                            <th class="px-5 py-3 text-left text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">{{ __('Clock Out') }}</th>
                            <th class="px-5 py-3 text-left text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">{{ __('Duration') }}</th>
                            <th class="px-5 py-3 text-left text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">{{ __('Note') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($manualLogs as $log)
                            <tr class="hover:bg-slate-50/40 transition-colors">
                                <td class="px-5 py-3 text-xs font-bold text-slate-800">
                                    {{ $log->clock_in_at ? $log->clock_in_at->timezone('Asia/Jakarta')->translatedFormat('d M Y') : '—' }}
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
                                        @if($log->clock_in_at && $log->clock_in_at->timezone('Asia/Jakarta')->isToday())
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded-full">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                                {{ __('Active') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-red-700 bg-red-50 border border-red-100 px-2 py-0.5 rounded-full" title="{{ __('Missing Clock-Out') }}">
                                                {{ __('Missing Out') }}
                                            </span>
                                        @endif
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-xs text-slate-500 font-medium">{{ $log->duration }}</td>
                                <td class="px-5 py-3 text-xs text-slate-400">{{ $log->note ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center">
                                    <p class="text-sm font-semibold text-slate-400">{{ __('No clock records yet.') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
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
