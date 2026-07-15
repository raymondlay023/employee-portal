<div>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-extrabold text-xl text-slate-900 leading-tight tracking-tight flex items-center gap-2">
                    <a href="{{ route('work-log-report', ['viewMode' => $viewMode, 'date' => $date, 'startDate' => $startDate, 'endDate' => $endDate, 'month' => $month, 'year' => $year]) }}" wire:navigate class="text-slate-400 hover:text-brand-600 transition-colors" title="{{ __('Back to Report') }}">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </a>
                    {{ __('Work Log Details - :name', ['name' => $employee->first_name . ' ' . $employee->last_name]) }}
                </h2>
                <p class="text-xs text-slate-400 font-medium mt-0.5 ml-8">
                    {{ $employee->employee_id }} &bull; {{ $employee->department->name ?? __('No Department') }} &bull; {{ $employee->designation->title ?? __('No Designation') }}
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-12" x-data="{ activeModalImage: null }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Selected Period Card -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="space-y-1">
                    <h3 class="text-base font-bold text-slate-800">{{ __('Reporting Period') }}</h3>
                    <p class="text-xs text-slate-500">
                        @if($viewMode === 'day')
                            {{ __('Showing logs for date: :date', ['date' => \Carbon\Carbon::parse($date)->format('F j, Y')]) }}
                        @elseif($viewMode === 'week')
                            {{ __('Showing logs for week: :start to :end', ['start' => \Carbon\Carbon::parse($startDate)->format('M j, Y'), 'end' => \Carbon\Carbon::parse($endDate)->format('M j, Y')]) }}
                        @elseif($viewMode === 'month')
                            {{ __('Showing logs for month: :month :year', ['month' => __(date('F', mktime(0, 0, 0, (int) $month, 1))), 'year' => $year]) }}
                        @elseif($viewMode === 'range')
                            {{ __('Showing logs from: :start to :end', ['start' => \Carbon\Carbon::parse($startDate)->format('M j, Y'), 'end' => \Carbon\Carbon::parse($endDate)->format('M j, Y')]) }}
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-2 text-xs font-bold text-slate-500 bg-slate-50 border border-slate-100 rounded-2xl px-4 py-2.5">
                    <span class="uppercase">{{ __('View Mode') }}:</span>
                    <span class="text-slate-800 uppercase bg-slate-200 px-2 py-0.5 rounded-lg">{{ __($viewMode) }}</span>
                </div>
            </div>

            <!-- Detailed Breakdown Grouped by Date -->
            <div class="space-y-6">
                @forelse ($groupedLogs as $logDate => $dayLogs)
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden" wire:key="group-{{ $logDate }}">
                        <!-- Day Header -->
                        <div class="bg-slate-50/50 border-b border-slate-100 px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <h4 class="font-extrabold text-sm text-slate-800 flex items-center gap-2">
                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ \Carbon\Carbon::parse($logDate)->format('D, M j, Y') }}
                            </h4>
                            @php
                                $dayTotalHours = 0;
                                foreach($dayLogs as $l) {
                                    $dayTotalHours += $l->duration_in_hours;
                                }
                            @endphp
                            <div class="flex items-center gap-3">
                                <span class="text-xs font-bold text-slate-500">
                                    {{ __('Total Hours') }}: 
                                    <span class="text-slate-800 bg-slate-100 px-2 py-0.5 rounded-md font-extrabold">{{ round($dayTotalHours, 1) }}h</span>
                                </span>
                                @if($dayTotalHours >= 8)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-50 text-green-700 border border-green-200">
                                        {{ __('Complete') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        {{ __('Partial') }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Day Log Entries -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse min-w-[700px]">
                                <thead>
                                    <tr class="border-b border-slate-100 text-[10px] uppercase font-extrabold tracking-wider text-slate-400">
                                        <th class="py-3 px-6 w-32">{{ __('Time') }}</th>
                                        <th class="py-3 px-4 w-40">{{ __('Activity') }}</th>
                                        <th class="py-3 px-6">{{ __('Remarks') }}</th>
                                        <th class="py-3 px-6 w-36 text-center">{{ __('Proof') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($dayLogs as $log)
                                        <tr class="hover:bg-slate-50/20 transition-colors">
                                            <td class="py-4 px-6 font-bold text-slate-700 text-xs whitespace-nowrap">
                                                {{ substr($log->start_time, 0, 5) }} - {{ substr($log->end_time, 0, 5) }}
                                                <span class="block text-[10px] text-slate-400 font-semibold mt-0.5">({{ round($log->duration_in_hours, 1) }}h)</span>
                                            </td>
                                            <td class="py-4 px-4 text-xs font-extrabold text-slate-800">
                                                <span class="inline-block bg-brand-50/60 text-brand-700 px-2 py-1 rounded-lg border border-brand-100/50">
                                                    {{ $log->activity }}
                                                </span>
                                            </td>
                                            <td class="py-4 px-6 text-xs text-slate-600 leading-relaxed max-w-md">
                                                {{ $log->remarks ?: '-' }}
                                            </td>
                                            <td class="py-4 px-6 text-center">
                                                @if($log->proof_path)
                                                    <button @click="activeModalImage = '{{ Storage::disk('public')->url($log->proof_path) }}'" class="inline-block group focus:outline-none relative">
                                                        <img src="{{ Storage::disk('public')->url($log->proof_path) }}" class="h-10 w-16 object-cover rounded-lg border border-slate-200 shadow-sm group-hover:opacity-80 group-hover:scale-105 transition-all">
                                                        <div class="absolute inset-0 flex items-center justify-center bg-black/30 opacity-0 group-hover:opacity-100 rounded-lg transition-opacity">
                                                            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                            </svg>
                                                        </div>
                                                    </button>
                                                @else
                                                    <span class="text-[10px] text-slate-400 font-semibold italic">{{ __('No Proof') }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @empty
                    <div class="bg-white rounded-3xl p-16 border border-slate-200 text-center shadow-sm">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-50 mb-4">
                            <svg class="w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h4 class="font-bold text-slate-600 mb-1">{{ __('No work logs found') }}</h4>
                        <p class="text-xs text-slate-400">{{ __('No entries recorded for this employee during the selected period.') }}</p>
                    </div>
                @endforelse
            </div>

        </div>

        <!-- Alpine.js Proof Modal overlay -->
        <template x-if="activeModalImage">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 p-4" @click="activeModalImage = null" x-transition>
                <div class="relative max-w-4xl max-h-[85vh] bg-white rounded-3xl overflow-hidden p-2 shadow-2xl border border-slate-100" @click.stop>
                    <button @click="activeModalImage = null" class="absolute top-4 right-4 p-2 bg-black/50 text-white rounded-full hover:bg-black/75 transition-colors focus:outline-none">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <img :src="activeModalImage" class="max-w-full max-h-[80vh] object-contain rounded-2xl">
                </div>
            </div>
        </template>
    </div>
</div>
