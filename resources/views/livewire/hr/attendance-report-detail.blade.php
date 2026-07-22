<div>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-extrabold text-xl text-slate-900 leading-tight tracking-tight flex items-center gap-2">
                    <a href="{{ route('attendance-report') }}" wire:navigate class="text-slate-400 hover:text-brand-600 transition-colors" title="{{ __('Back to Report') }}">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </a>
                    {{ __('Attendance Details - :name', ['name' => $employee->first_name . ' ' . $employee->last_name]) }}
                </h2>
                <p class="text-xs text-slate-400 font-medium mt-0.5 ml-8">
                    {{ $employee->employee_id }} &bull; {{ $employee->department->name ?? __('No Department') }}
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Month / Year Controls -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="space-y-1">
                    <h3 class="text-lg font-bold text-slate-800">{{ __('Monthly Breakdown') }}</h3>
                    <p class="text-xs text-slate-500">{{ __('Daily JPayroll attendance records for this employee.') }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <select wire:model.live="month" class="text-sm font-semibold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl pl-4 pr-10 py-2 focus:border-brand-500 focus:ring focus:ring-brand-200/50">
                        @foreach($availableMonths as $m)
                            <option value="{{ $m }}">{{ __(date('F', mktime(0, 0, 0, $m, 1))) }}</option>
                        @endforeach
                    </select>
                    <select wire:model.live="year" class="text-sm font-semibold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl pl-4 pr-10 py-2 focus:border-brand-500 focus:ring focus:ring-brand-200/50">
                        @foreach($availableYears as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            
            <!-- Summary Cards -->
            <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
                <button type="button" wire:click="setStatusFilter('all')" class="bg-white rounded-3xl p-5 border transition-all shadow-xs flex flex-col justify-center text-center cursor-pointer {{ $statusFilter === 'all' ? 'border-brand-500 ring-2 ring-brand-200' : 'border-slate-100 hover:border-slate-300' }}">
                    <div class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">{{ __('Total') }}</div>
                    <div class="text-2xl font-extrabold text-slate-800">{{ $summary['total'] }}</div>
                </button>
                <button type="button" wire:click="setStatusFilter('present')" class="bg-white rounded-3xl p-5 border transition-all shadow-xs flex flex-col justify-center text-center cursor-pointer {{ $statusFilter === 'present' ? 'border-green-500 ring-2 ring-green-200' : 'border-slate-100 hover:border-slate-300' }}">
                    <div class="text-green-600 text-xs font-bold uppercase tracking-wider mb-1">{{ __('Present') }}</div>
                    <div class="text-2xl font-extrabold text-green-700">{{ $summary['present'] }}</div>
                </button>
                <button type="button" wire:click="setStatusFilter('absent')" class="bg-white rounded-3xl p-5 border transition-all shadow-xs flex flex-col justify-center text-center cursor-pointer {{ $statusFilter === 'absent' ? 'border-red-500 ring-2 ring-red-200' : 'border-slate-100 hover:border-slate-300' }}">
                    <div class="text-red-500 text-xs font-bold uppercase tracking-wider mb-1">{{ __('Absent') }}</div>
                    <div class="text-2xl font-extrabold text-red-600">{{ $summary['absent'] }}</div>
                </button>
                <button type="button" wire:click="setStatusFilter('late')" class="bg-white rounded-3xl p-5 border transition-all shadow-xs flex flex-col justify-center text-center cursor-pointer {{ $statusFilter === 'late' ? 'border-amber-500 ring-2 ring-amber-200' : 'border-slate-100 hover:border-slate-300' }}">
                    <div class="text-amber-500 text-xs font-bold uppercase tracking-wider mb-1">{{ __('Late') }}</div>
                    <div class="text-2xl font-extrabold text-amber-600">{{ $summary['late'] }}</div>
                </button>
                <button type="button" wire:click="setStatusFilter('sick')" class="bg-white rounded-3xl p-5 border transition-all shadow-xs flex flex-col justify-center text-center cursor-pointer {{ $statusFilter === 'sick' ? 'border-purple-500 ring-2 ring-purple-200' : 'border-slate-100 hover:border-slate-300' }}">
                    <div class="text-purple-500 text-xs font-bold uppercase tracking-wider mb-1">{{ __('Sick') }}</div>
                    <div class="text-2xl font-extrabold text-purple-600">{{ $summary['sick'] }}</div>
                </button>
                <button type="button" wire:click="setStatusFilter('leave')" class="bg-white rounded-3xl p-5 border transition-all shadow-xs flex flex-col justify-center text-center cursor-pointer {{ $statusFilter === 'leave' ? 'border-blue-500 ring-2 ring-blue-200' : 'border-slate-100 hover:border-slate-300' }}">
                    <div class="text-blue-500 text-xs font-bold uppercase tracking-wider mb-1">{{ __('Leave') }}</div>
                    <div class="text-2xl font-extrabold text-blue-600">{{ $summary['leave'] }}</div>
                </button>
            </div>

            <!-- Main Data Table -->
            <div class="bg-white rounded-3xl shadow-xs border border-slate-200 overflow-hidden relative">
                <div wire:loading class="absolute inset-0 bg-white/50 backdrop-blur-xs z-10 flex items-center justify-center">
                    <svg class="animate-spin h-8 w-8 text-brand-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[800px]">
                        <thead>
                            <tr class="bg-slate-50/50 border-b border-slate-100 text-[10px] uppercase font-extrabold tracking-wider text-slate-500">
                                <th class="py-4 px-6">{{ __('Date') }}</th>
                                <th class="py-4 px-4 text-center">{{ __('Status') }}</th>
                                <th class="py-4 px-4 text-center">{{ __('Work Logs') }}</th>
                                <th class="py-4 px-4 text-center">{{ __('Absent') }}</th>
                                <th class="py-4 px-4 text-center">{{ __('Late') }}</th>
                                <th class="py-4 px-4 text-center">{{ __('Sick') }}</th>
                                <th class="py-4 px-4 text-center">{{ __('Leave') }}</th>
                            </tr>
                        </thead>
                        <tbody class="hidden"></tbody>
                        @forelse ($groupedLogs as $weekLabel => $logsInWeek)
                            <!-- Week Subheader Row -->
                            <tbody class="border-t-2 border-slate-100">
                                <tr class="bg-slate-50/80">
                                    <td colspan="7" class="px-6 py-2.5">
                                        <span class="text-[10px] font-extrabold text-brand-600 uppercase tracking-wider">
                                            {{ __($weekLabel) }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>

                            @foreach($logsInWeek as $log)
                            @php
                                $dateKey = $log->shift_date->toDateString();
                                $dayWorkLogs = $workLogs[$dateKey] ?? collect();
                                $hasWorkLogs = $dayWorkLogs->isNotEmpty();
                            @endphp
                            <tbody x-data="{ expanded: false }" class="border-b border-slate-100">
                                <tr class="hover:bg-slate-50/60 transition-colors group">
                                    <td class="py-4 px-6">
                                        <div class="text-sm font-bold text-slate-800">{{ $log->shift_date->format('D, M j, Y') }}</div>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        @php
                                            $statusLabel = $log->statusLabel();
                                            $statusClass = match($statusLabel) {
                                                'Present' => 'bg-green-100 text-green-700 border-green-200',
                                                'Absent', 'Absent (No Biometric)' => 'bg-red-100 text-red-700 border-red-200',
                                                'Late' => 'bg-amber-100 text-amber-700 border-amber-200',
                                                'Leave' => 'bg-blue-100 text-blue-700 border-blue-200',
                                                'Sick' => 'bg-purple-100 text-purple-700 border-purple-200',
                                                'Off Day' => 'bg-slate-100 text-slate-600 border-slate-200',
                                                default => 'bg-slate-100 text-slate-700 border-slate-200',
                                            };
                                        @endphp
                                        <div class="flex items-center justify-center gap-1">
                                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-bold {{ $statusClass }} border shadow-xs">
                                                {{ __($statusLabel) }}
                                            </span>
                                            @if($log->getAbnormality())
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200 shadow-xs shrink-0 cursor-help" title="{{ __($log->getAbnormality()) }}">
                                                    <svg class="w-3 h-3 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                                    </svg>
                                                    {{ __('Conflict') }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <!-- Work Logs Badge Button -->
                                    <td class="py-4 px-4 text-center">
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
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-flex items-center justify-center min-w-[2.5rem] px-2 py-1 {{ $log->alpha > 0 ? 'bg-red-50 text-red-700 border-red-200/60 shadow-xs' : 'text-slate-400 bg-slate-50/50' }} font-bold text-xs rounded-lg border border-transparent">
                                            {{ (int) $log->alpha }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-flex items-center justify-center min-w-[2.5rem] px-2 py-1 {{ $log->telat > 0 ? 'bg-amber-50 text-amber-700 border-amber-200/60 shadow-xs' : 'text-slate-400 bg-slate-50/50' }} font-bold text-xs rounded-lg border border-transparent">
                                            {{ (int) $log->telat }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-flex items-center justify-center min-w-[2.5rem] px-2 py-1 {{ $log->sakit > 0 ? 'bg-purple-50 text-purple-700 border-purple-200/60 shadow-xs' : 'text-slate-400 bg-slate-50/50' }} font-bold text-xs rounded-lg border border-transparent">
                                            {{ (int) $log->sakit }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-flex items-center justify-center min-w-[2.5rem] px-2 py-1 {{ $log->izin > 0 ? 'bg-blue-50 text-blue-700 border-blue-200/60 shadow-xs' : 'text-slate-400 bg-slate-50/50' }} font-bold text-xs rounded-lg border border-transparent">
                                            {{ (int) $log->izin }}
                                        </span>
                                    </td>
                                </tr>
                                @if($hasWorkLogs)
                                    <tr x-show="expanded" x-transition.opacity class="bg-slate-50/60" style="display: none;">
                                        <td colspan="7" class="p-0 border-t border-slate-100">
                                            <div class="px-12 py-4 shadow-inner">
                                                <div class="flex items-center justify-between mb-3">
                                                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">{{ __('Daily Work Logs') }}</h4>
                                                    <span class="text-[11px] font-bold text-brand-600 bg-brand-50 px-2 py-0.5 rounded-md border border-brand-100">
                                                        {{ number_format($dayWorkLogs->sum('duration_minutes') / 60, 1) }} {{ __('Hours Total') }}
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
                                                                <span class="text-[10px] font-bold text-slate-700 bg-slate-100 px-2 py-1 rounded-md">{{ number_format($workLog->duration_minutes / 60, 1) }}h</span>
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
                            <tbody>
                                <tr>
                                    <td colspan="7" class="py-16 text-center">
                                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-50 mb-4">
                                            <svg class="w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-bold text-slate-600">{{ __('No attendance records found') }}</p>
                                        <p class="text-xs text-slate-400 mt-1">{{ __('No data matches the selected filter for this month/year.') }}</p>
                                    </td>
                                </tr>
                            </tbody>
                        @endforelse
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
