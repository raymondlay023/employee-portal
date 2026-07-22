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
                            class="text-xs border border-slate-200 rounded-xl pl-3 pr-8 py-2 text-slate-700 font-bold bg-slate-50 focus:ring-2 focus:ring-brand-300 focus:border-brand-400">
                            @foreach($availableMonths as $m)
                                <option value="{{ $m }}" {{ $month === $m ? 'selected' : '' }}>{{ __(date('F', mktime(0, 0, 0, $m, 1))) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Year Select -->
                    <div class="flex flex-col gap-1">
                        <label for="year" class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">{{ __('Year') }}</label>
                        <select id="year" name="year"
                            class="text-xs border border-slate-200 rounded-xl pl-3 pr-8 py-2 text-slate-700 font-bold bg-slate-50 focus:ring-2 focus:ring-brand-300 focus:border-brand-400">
                            @foreach($availableYears as $y)
                                <option value="{{ $y }}" {{ $year === $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>

                    @can(\App\Authorization\Permissions::MANAGE_ATTENDANCE)
                        <input type="hidden" name="employee_id" value="{{ $targetEmployee->id ?? '' }}">
                    @endcan

                    <input type="hidden" name="status" value="{{ request('status', 'all') }}">
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
            @php $currentStatus = request('status', 'all'); @endphp
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <a href="{{ request()->fullUrlWithQuery(['status' => 'all']) }}"
                   class="bg-white rounded-3xl p-5 border transition-all shadow-xs flex flex-col justify-center text-center cursor-pointer {{ $currentStatus === 'all' ? 'border-brand-500 ring-2 ring-brand-200' : 'border-slate-100 hover:border-slate-300' }}">
                    <div class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">{{ __('Total') }}</div>
                    <div class="text-2xl font-extrabold text-slate-800">{{ (int) $summary->total_days }}</div>
                </a>
                <a href="{{ request()->fullUrlWithQuery(['status' => 'present']) }}"
                   class="bg-white rounded-3xl p-5 border transition-all shadow-xs flex flex-col justify-center text-center cursor-pointer {{ $currentStatus === 'present' ? 'border-green-500 ring-2 ring-green-200' : 'border-slate-100 hover:border-slate-300' }}">
                    <div class="text-green-600 text-xs font-bold uppercase tracking-wider mb-1">{{ __('Present') }}</div>
                    <div class="text-2xl font-extrabold text-green-700">{{ (int) $summary->present_days }}</div>
                </a>
                <a href="{{ request()->fullUrlWithQuery(['status' => 'absent']) }}"
                   class="bg-white rounded-3xl p-5 border transition-all shadow-xs flex flex-col justify-center text-center cursor-pointer {{ $currentStatus === 'absent' ? 'border-red-500 ring-2 ring-red-200' : 'border-slate-100 hover:border-slate-300' }}">
                    <div class="text-red-500 text-xs font-bold uppercase tracking-wider mb-1">{{ __('Absent') }}</div>
                    <div class="text-2xl font-extrabold text-red-600">{{ (int) $summary->absent_days }}</div>
                </a>
                <a href="{{ request()->fullUrlWithQuery(['status' => 'late']) }}"
                   class="bg-white rounded-3xl p-5 border transition-all shadow-xs flex flex-col justify-center text-center cursor-pointer {{ $currentStatus === 'late' ? 'border-amber-500 ring-2 ring-amber-200' : 'border-slate-100 hover:border-slate-300' }}">
                    <div class="text-amber-500 text-xs font-bold uppercase tracking-wider mb-1">{{ __('Late') }}</div>
                    <div class="text-2xl font-extrabold text-amber-600">{{ (int) $summary->late_days }}</div>
                </a>
                <a href="{{ request()->fullUrlWithQuery(['status' => 'sick']) }}"
                   class="bg-white rounded-3xl p-5 border transition-all shadow-xs flex flex-col justify-center text-center cursor-pointer {{ $currentStatus === 'sick' ? 'border-purple-500 ring-2 ring-purple-200' : 'border-slate-100 hover:border-slate-300' }}">
                    <div class="text-purple-500 text-xs font-bold uppercase tracking-wider mb-1">{{ __('Sick') }}</div>
                    <div class="text-2xl font-extrabold text-purple-600">{{ (int) $summary->sick_days }}</div>
                </a>
                <a href="{{ request()->fullUrlWithQuery(['status' => 'leave']) }}"
                   class="bg-white rounded-3xl p-5 border transition-all shadow-xs flex flex-col justify-center text-center cursor-pointer {{ $currentStatus === 'leave' ? 'border-blue-500 ring-2 ring-blue-200' : 'border-slate-100 hover:border-slate-300' }}">
                    <div class="text-blue-500 text-xs font-bold uppercase tracking-wider mb-1">{{ __('Leave') }}</div>
                    <div class="text-2xl font-extrabold text-blue-600">{{ (int) ($summary->leave_days + ($summary->permitted_days ?? 0)) }}</div>
                </a>
            </div>
        @endif

        {{-- ── UNIFIED SECTION: Attendance Records ──────────────────────── --}}
        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden" x-data="{ showLogs: false }">

            {{-- Card header --}}
            <div class="px-6 pt-6 pb-4 border-b border-slate-100 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 rounded-2xl bg-brand-50 border border-brand-100 text-brand-600 shadow-xs">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">{{ __('Attendance Daily Records') }}</h3>
                        <p class="text-xs text-slate-400 font-medium">{{ __('Combined JPayroll summary & biometric punch logs') }}</p>
                    </div>
                </div>

                {{-- Clock In / Out actions & Sync badges --}}
                <div class="flex flex-wrap items-center gap-3 lg:ml-auto">
                    <!-- Toggle Switch for Work & Biometric Logs -->
                    <div class="flex items-center gap-2.5 bg-slate-50/80 hover:bg-slate-100/80 px-3.5 py-2 rounded-xl border border-slate-200/80 transition-all shadow-2xs">
                        <button type="button" 
                                @click="showLogs = !showLogs"
                                :class="showLogs ? 'bg-brand-600' : 'bg-slate-300'"
                                class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-hidden focus:ring-2 focus:ring-brand-500 focus:ring-offset-1"
                                role="switch" 
                                :aria-checked="showLogs">
                            <span :class="showLogs ? 'translate-x-4' : 'translate-x-0'"
                                  class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-xs ring-0 transition duration-200 ease-in-out"></span>
                        </button>
                        <span @click="showLogs = !showLogs" class="text-xs font-extrabold text-slate-700 select-none cursor-pointer">
                            {{ __('Show Punch & Work Logs') }}
                        </span>
                    </div>
                    @if(config('app.enable_manual_attendance'))
                        @if (!$todayLog)
                            <form action="{{ route('attendance.clock-in') }}" method="POST">
                                @csrf
                                <button type="submit" class="inline-flex justify-center items-center px-5 py-2 border border-transparent text-xs font-extrabold rounded-xl shadow-xs text-white bg-brand-600 hover:bg-brand-700 hover:-translate-y-0.5 transform transition-all cursor-pointer">
                                    <svg class="-ml-1 mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                    </svg>
                                    {{ __('Clock In') }}
                                </button>
                            </form>
                        @else
                            <form action="{{ route('attendance.clock-out') }}" method="POST">
                                @csrf
                                <button type="submit" class="inline-flex justify-center items-center px-5 py-2 border border-transparent text-xs font-extrabold rounded-xl shadow-xs text-white bg-red-600 hover:bg-red-700 hover:-translate-y-0.5 transform transition-all cursor-pointer">
                                    <svg class="-ml-1 mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    {{ __('Clock Out') }}
                                </button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead>
                        <tr class="bg-slate-50/60">
                            <th class="px-5 py-3.5 text-left text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">{{ __('Date') }}</th>
                            <th class="px-5 py-3.5 text-center text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">{{ __('Status') }}</th>
                            <th x-show="showLogs" x-cloak class="px-5 py-3.5 text-center text-[10px] font-extrabold text-slate-500 uppercase tracking-wider" style="display: none;">{{ __('Clock In') }}</th>
                            <th x-show="showLogs" x-cloak class="px-5 py-3.5 text-center text-[10px] font-extrabold text-slate-500 uppercase tracking-wider" style="display: none;">{{ __('Clock Out') }}</th>
                            <th x-show="showLogs" x-cloak class="px-5 py-3.5 text-center text-[10px] font-extrabold text-slate-500 uppercase tracking-wider" style="display: none;">{{ __('Duration') }}</th>
                            <th x-show="showLogs" x-cloak class="px-5 py-3.5 text-center text-[10px] font-extrabold text-slate-500 uppercase tracking-wider" style="display: none;">{{ __('Work Logs') }}</th>
                            <th class="px-5 py-3.5 text-center text-[10px] font-extrabold text-slate-500 uppercase tracking-wider" title="Absent without leave">{{ __('Absent') }}</th>
                            <th class="px-5 py-3.5 text-center text-[10px] font-extrabold text-slate-500 uppercase tracking-wider" title="Late arrival">{{ __('Late') }}</th>
                            <th class="px-5 py-3.5 text-center text-[10px] font-extrabold text-slate-500 uppercase tracking-wider" title="Sick leave">{{ __('Sick') }}</th>
                            <th class="px-5 py-3.5 text-center text-[10px] font-extrabold text-slate-500 uppercase tracking-wider" title="Approved leave / Cuti">{{ __('Leave') }}</th>
                        </tr>
                    </thead>
                    <tbody class="hidden"></tbody>
                    @forelse($groupedLogs as $weekLabel => $logsInWeek)
                        <!-- Week Subheader Row -->
                        <tbody class="border-t-2 border-slate-100">
                            <tr class="bg-slate-50/80">
                                <td :colspan="showLogs ? 10 : 6" class="px-5 py-2.5">
                                    <span class="text-[10px] font-extrabold text-brand-600 uppercase tracking-wider">
                                        {{ __($weekLabel) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>

                        @foreach($logsInWeek as $row)
                        @php
                            $jp = $row['jpayroll'];
                            $bio = $row['biometric'];
                            $date = $row['date'];
                            $isJpayroll = $row['type'] === 'jpayroll';
                            $dateKey = $date->toDateString();
                            $dayWorkLogs = $workLogs[$dateKey] ?? collect();
                            $hasWorkLogs = $dayWorkLogs->isNotEmpty();

                            if ($isJpayroll && $jp) {
                                $statusLabel = $jp->statusLabel();
                                $statusClass = match($statusLabel) {
                                    'Present' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                    'Absent', 'Absent (No Biometric)' => 'bg-red-50 text-red-700 border-red-100',
                                    'Late' => 'bg-amber-50 text-amber-700 border-amber-100',
                                    'Leave' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
                                    'Sick' => 'bg-orange-50 text-orange-700 border-orange-100',
                                    'Off Day' => 'bg-slate-50 text-slate-500 border-slate-100',
                                    default => 'bg-slate-50 text-slate-500 border-slate-100',
                                };
                                $abnormality = $jp->getAbnormality();
                            } else {
                                $statusLabel = 'Pending Sync';
                                $statusClass = 'bg-sky-50 text-sky-700 border-sky-100';
                                $abnormality = null;
                            }
                        @endphp
                        <tbody x-data="{ expanded: false }" class="divide-y divide-slate-50 border-b border-slate-100">
                            <tr class="hover:bg-slate-50/40 transition-colors group">
                                <!-- Date -->
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-slate-800">{{ $date->translatedFormat('d M Y') }}</span>
                                        <span class="text-[10px] text-slate-400">{{ $date->translatedFormat('l') }}</span>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-5 py-3.5 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $statusClass }}">
                                            {{ __($statusLabel) }}
                                        </span>
                                        @if($abnormality)
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200 shadow-xs shrink-0 cursor-help" title="{{ __($abnormality) }}">
                                                <svg class="w-3.5 h-3.5 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                                </svg>
                                                {{ __('Conflict') }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Clock In -->
                                <td x-show="showLogs" x-cloak class="px-5 py-3.5 text-center" style="display: none;">
                                    @if($bio && $bio->clock_in_at)
                                        <span class="text-xs font-semibold text-emerald-700">
                                            {{ $bio->clock_in_at->timezone('Asia/Jakarta')->format('H:i:s') }}
                                        </span>
                                    @else
                                        <span class="text-xs text-slate-300">—</span>
                                    @endif
                                </td>

                                <!-- Clock Out -->
                                <td x-show="showLogs" x-cloak class="px-5 py-3.5 text-center" style="display: none;">
                                    @if($bio && $bio->clock_out_at)
                                        <span class="text-xs font-semibold text-slate-700">
                                            {{ $bio->clock_out_at->timezone('Asia/Jakarta')->format('H:i:s') }}
                                        </span>
                                    @elseif($bio && $bio->clock_in_at && $bio->clock_in_at->timezone('Asia/Jakarta')->isToday())
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded-full">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                            {{ __('Active') }}
                                        </span>
                                    @elseif($bio && $bio->clock_in_at && $bio->clock_in_at->timezone('Asia/Jakarta')->isBefore(today()))
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-red-700 bg-red-50 border border-red-100 px-2 py-0.5 rounded-full" title="{{ __('Missing Clock-Out') }}">
                                            {{ __('Missing Out') }}
                                        </span>
                                    @else
                                        <span class="text-xs text-slate-300">—</span>
                                    @endif
                                </td>

                                <!-- Duration -->
                                <td x-show="showLogs" x-cloak class="px-5 py-3.5 text-center text-xs text-slate-500 font-medium" style="display: none;">
                                    {{ $bio ? $bio->duration : '—' }}
                                </td>

                                <!-- Work Logs Badge Button -->
                                <td x-show="showLogs" x-cloak class="px-5 py-3.5 text-center" style="display: none;">
                                    @if($hasWorkLogs)
                                        <button @click="expanded = !expanded" type="button" class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-extrabold text-brand-600 bg-brand-50 hover:bg-brand-100 border border-brand-200 rounded-lg transition-colors shadow-xs cursor-pointer">
                                            <span>{{ $dayWorkLogs->count() }} {{ __('Logs') }}</span>
                                            <svg class="w-3 h-3 transition-transform shrink-0" :class="{'rotate-180': expanded}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                    @else
                                        <span class="text-xs text-slate-300">—</span>
                                    @endif
                                </td>

                                <!-- Absent -->
                                <td class="px-5 py-3.5 text-center">
                                    @if($jp)
                                        <span class="inline-flex items-center justify-center min-w-[2rem] px-1 py-0.5 {{ $jp->alpha > 0 ? 'bg-red-50 text-red-700 border-red-200/60 shadow-xs' : 'text-slate-400 bg-slate-50/50' }} font-bold text-xs rounded border border-transparent">
                                            {{ $jp->alpha }}
                                        </span>
                                    @else
                                        <span class="text-xs text-slate-300">—</span>
                                    @endif
                                </td>

                                <!-- Late -->
                                <td class="px-5 py-3.5 text-center">
                                    @if($jp)
                                        <span class="inline-flex items-center justify-center min-w-[2rem] px-1 py-0.5 {{ $jp->telat > 0 ? 'bg-amber-50 text-amber-700 border-amber-200/60 shadow-xs' : 'text-slate-400 bg-slate-50/50' }} font-bold text-xs rounded border border-transparent">
                                            {{ $jp->telat }}
                                        </span>
                                    @else
                                        <span class="text-xs text-slate-300">—</span>
                                    @endif
                                </td>

                                <!-- Sick -->
                                <td class="px-5 py-3.5 text-center">
                                    @if($jp)
                                        <span class="inline-flex items-center justify-center min-w-[2rem] px-1 py-0.5 {{ $jp->sakit > 0 ? 'bg-purple-50 text-purple-700 border-purple-200/60 shadow-xs' : 'text-slate-400 bg-slate-50/50' }} font-bold text-xs rounded border border-transparent">
                                            {{ $jp->sakit }}
                                        </span>
                                    @else
                                        <span class="text-xs text-slate-300">—</span>
                                    @endif
                                </td>

                                <!-- Leave -->
                                <td class="px-5 py-3.5 text-center">
                                    @if($jp)
                                        <span class="inline-flex items-center justify-center min-w-[2rem] px-1 py-0.5 {{ $jp->izin > 0 ? 'bg-blue-50 text-blue-700 border-blue-200/60 shadow-xs' : 'text-slate-400 bg-slate-50/50' }} font-bold text-xs rounded border border-transparent">
                                            {{ $jp->izin }}
                                        </span>
                                    @else
                                        <span class="text-xs text-slate-300">—</span>
                                    @endif
                                </td>
                            </tr>
                            @if($hasWorkLogs)
                                <tr x-show="expanded && showLogs" x-transition.opacity class="bg-slate-50/60" style="display: none;">
                                    <td :colspan="showLogs ? 10 : 6" class="p-0 border-t border-slate-100">
                                        <div class="px-10 py-4 shadow-inner">
                                            <div class="flex items-center justify-between mb-3">
                                                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">{{ __('Daily Work Logs') }}</h4>
                                                <span class="text-[11px] font-bold text-brand-600 bg-brand-50 px-2 py-0.5 rounded-md border border-brand-100">
                                                    {{ \App\Models\DailyWorkLog::formatMinutes($dayWorkLogs->sum('duration_minutes')) }} {{ __('Total') }}
                                                </span>
                                            </div>
                                            <div class="space-y-2">
                                                @foreach($dayWorkLogs as $workLog)
                                                    <div class="flex items-start gap-3 bg-white p-3 rounded-xl border border-slate-200/80 shadow-xs">
                                                        <div class="flex flex-col items-center justify-center min-w-[4rem] px-2 py-1 bg-slate-50 rounded-lg border border-slate-100 shrink-0">
                                                            <span class="text-[10px] font-extrabold text-slate-600">{{ \Carbon\Carbon::parse($workLog->start_time)->format('H:i') }}</span>
                                                            <span class="text-[10px] font-extrabold text-slate-400">{{ \Carbon\Carbon::parse($workLog->end_time)->format('H:i') }}</span>
                                                        </div>
                                                        <div class="flex-1 min-w-0">
                                                            <p class="text-xs font-bold text-slate-800 truncate">{{ $workLog->activity }}</p>
                                                            @if($workLog->task_description)
                                                                <p class="text-[11px] text-slate-500 mt-0.5 leading-snug">{{ $workLog->task_description }}</p>
                                                            @endif
                                                        </div>
                                                        <div class="ml-auto flex items-center shrink-0">
                                                            <span class="text-[10px] font-bold text-slate-700 bg-slate-100 px-2 py-1 rounded-md">{{ \App\Models\DailyWorkLog::formatMinutes($workLog->duration_minutes, true) }}</span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                        @endforeach
                    @empty
                            <tbody class="border-t border-slate-100">
                                <tr>
                                    <td :colspan="showLogs ? 10 : 6" class="px-5 py-12 text-center">
                                    <div class="flex flex-col items-center gap-2 text-slate-400">
                                        <svg class="w-10 h-10 text-slate-200" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                                        </svg>
                                        <p class="text-sm font-semibold text-slate-500">{{ __('No attendance records found') }}</p>
                                    </div>
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
