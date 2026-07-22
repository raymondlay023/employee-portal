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
                <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-sm flex flex-col justify-center text-center">
                    <div class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">{{ __('Total') }}</div>
                    <div class="text-2xl font-extrabold text-slate-800">{{ $summary['total'] }}</div>
                </div>
                <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-sm flex flex-col justify-center text-center">
                    <div class="text-green-600 text-xs font-bold uppercase tracking-wider mb-1">{{ __('Present') }}</div>
                    <div class="text-2xl font-extrabold text-green-700">{{ $summary['present'] }}</div>
                </div>
                <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-sm flex flex-col justify-center text-center">
                    <div class="text-red-500 text-xs font-bold uppercase tracking-wider mb-1">{{ __('Absent') }}</div>
                    <div class="text-2xl font-extrabold text-red-600">{{ $summary['absent'] }}</div>
                </div>
                <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-sm flex flex-col justify-center text-center">
                    <div class="text-amber-500 text-xs font-bold uppercase tracking-wider mb-1">{{ __('Late') }}</div>
                    <div class="text-2xl font-extrabold text-amber-600">{{ $summary['late'] }}</div>
                </div>
                <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-sm flex flex-col justify-center text-center">
                    <div class="text-purple-500 text-xs font-bold uppercase tracking-wider mb-1">{{ __('Sick') }}</div>
                    <div class="text-2xl font-extrabold text-purple-600">{{ $summary['sick'] }}</div>
                </div>
                <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-sm flex flex-col justify-center text-center">
                    <div class="text-blue-500 text-xs font-bold uppercase tracking-wider mb-1">{{ __('Leave') }}</div>
                    <div class="text-2xl font-extrabold text-blue-600">{{ $summary['leave'] }}</div>
                </div>
            </div>

            <!-- Main Data Table -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden relative">
                <div wire:loading class="absolute inset-0 bg-white/50 backdrop-blur-sm z-10 flex items-center justify-center">
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
                                <th class="py-4 px-4 text-center">{{ __('Absent') }}</th>
                                <th class="py-4 px-4 text-center">{{ __('Late') }}</th>
                                <th class="py-4 px-4 text-center">{{ __('Sick') }}</th>
                                <th class="py-4 px-4 text-center">{{ __('Leave') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($logs as $log)
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
                                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-bold {{ $statusClass }} border shadow-sm">
                                                {{ __($statusLabel) }}
                                            </span>
                                            @if($log->getAbnormality())
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200 shadow-sm shrink-0 cursor-help" title="{{ __($log->getAbnormality()) }}">
                                                    <svg class="w-3 h-3 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                                    </svg>
                                                    {{ __('Conflict') }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-flex items-center justify-center min-w-[2.5rem] px-2 py-1 {{ $log->alpha > 0 ? 'bg-red-50 text-red-700 border-red-200/60 shadow-sm' : 'text-slate-400 bg-slate-50/50' }} font-bold text-xs rounded-lg border border-transparent">
                                            {{ (int) $log->alpha }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-flex items-center justify-center min-w-[2.5rem] px-2 py-1 {{ $log->telat > 0 ? 'bg-amber-50 text-amber-700 border-amber-200/60 shadow-sm' : 'text-slate-400 bg-slate-50/50' }} font-bold text-xs rounded-lg border border-transparent">
                                            {{ (int) $log->telat }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-flex items-center justify-center min-w-[2.5rem] px-2 py-1 {{ $log->sakit > 0 ? 'bg-purple-50 text-purple-700 border-purple-200/60 shadow-sm' : 'text-slate-400 bg-slate-50/50' }} font-bold text-xs rounded-lg border border-transparent">
                                            {{ (int) $log->sakit }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-flex items-center justify-center min-w-[2.5rem] px-2 py-1 {{ $log->izin > 0 ? 'bg-blue-50 text-blue-700 border-blue-200/60 shadow-sm' : 'text-slate-400 bg-slate-50/50' }} font-bold text-xs rounded-lg border border-transparent">
                                            {{ (int) $log->izin }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-16 text-center">
                                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-50 mb-4">
                                            <svg class="w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-bold text-slate-600">{{ __('No attendance records found') }}</p>
                                        <p class="text-xs text-slate-400 mt-1">{{ __('No data is available for this month/year.') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
            @if ($logs->hasPages())
                <div class="mt-4">
                    {{ $logs->links() }}
                </div>
            @endif

        </div>
    </div>
</div>
