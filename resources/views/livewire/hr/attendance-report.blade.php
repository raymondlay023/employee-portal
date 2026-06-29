<div>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-brand-700 leading-tight">
            {{ __('HR Attendance Report') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Controls Header -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="space-y-1">
                    <h3 class="text-lg font-bold text-slate-800">Monthly Aggregates</h3>
                    <p class="text-xs text-slate-500">Overview of all employee attendance records from JPayroll.</p>
                </div>
                
                <div class="flex items-center gap-3">
                    <select wire:model.live="month" class="text-sm font-semibold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 focus:border-brand-500 focus:ring focus:ring-brand-200/50">
                        @for($m=1; $m<=12; $m++)
                            <option value="{{ $m }}">{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                        @endfor
                    </select>

                    <select wire:model.live="year" class="text-sm font-semibold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 focus:border-brand-500 focus:ring focus:ring-brand-200/50">
                        @for($y=date('Y')-2; $y<=date('Y')+1; $y++)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            </div>

            <!-- Main Data Table -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[800px]">
                        <thead>
                            <tr class="bg-slate-50/50 border-b border-slate-100 text-[10px] uppercase font-extrabold tracking-wider text-slate-500">
                                <th class="py-4 px-6 cursor-pointer hover:bg-slate-100 transition-colors" wire:click="sortBy('first_name')">
                                    <div class="flex items-center gap-2">
                                        Employee
                                        @if($sortField === 'first_name')
                                            <svg class="w-3 h-3 text-brand-600 transition-transform {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        @endif
                                    </div>
                                </th>
                                <th class="py-4 px-4 text-center cursor-pointer hover:bg-slate-100 transition-colors" wire:click="sortBy('present_days')">
                                    <div class="flex items-center justify-center gap-1.5">
                                        Present
                                        @if($sortField === 'present_days')
                                            <span class="text-brand-600 font-bold {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}">▼</span>
                                        @endif
                                    </div>
                                </th>
                                <th class="py-4 px-4 text-center cursor-pointer hover:bg-slate-100 transition-colors" wire:click="sortBy('absent_days')">
                                    <div class="flex items-center justify-center gap-1.5">
                                        Absent
                                        @if($sortField === 'absent_days')
                                            <span class="text-brand-600 font-bold {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}">▼</span>
                                        @endif
                                    </div>
                                </th>
                                <th class="py-4 px-4 text-center cursor-pointer hover:bg-slate-100 transition-colors" wire:click="sortBy('late_days')">
                                    <div class="flex items-center justify-center gap-1.5">
                                        Late
                                        @if($sortField === 'late_days')
                                            <span class="text-brand-600 font-bold {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}">▼</span>
                                        @endif
                                    </div>
                                </th>
                                <th class="py-4 px-4 text-center cursor-pointer hover:bg-slate-100 transition-colors" wire:click="sortBy('sick_days')">
                                    <div class="flex items-center justify-center gap-1.5">
                                        Sick
                                        @if($sortField === 'sick_days')
                                            <span class="text-brand-600 font-bold {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}">▼</span>
                                        @endif
                                    </div>
                                </th>
                                <th class="py-4 px-4 text-center cursor-pointer hover:bg-slate-100 transition-colors" wire:click="sortBy('leave_days')">
                                    <div class="flex items-center justify-center gap-1.5">
                                        Leave
                                        @if($sortField === 'leave_days')
                                            <span class="text-brand-600 font-bold {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}">▼</span>
                                        @endif
                                    </div>
                                </th>
                                <th class="py-4 px-6 text-center cursor-pointer hover:bg-slate-100 transition-colors" wire:click="sortBy('total_days')">
                                    <div class="flex items-center justify-center gap-1.5">
                                        Total Days
                                        @if($sortField === 'total_days')
                                            <span class="text-brand-600 font-bold {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}">▼</span>
                                        @endif
                                    </div>
                                </th>
                                <th class="py-4 px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($reportData as $row)
                                <tr class="hover:bg-slate-50/60 transition-colors group">
                                    <td class="py-4 px-6">
                                        <div class="flex items-center gap-3">
                                            <div class="h-10 w-10 rounded-full bg-gradient-to-br from-brand-100 to-brand-200 flex flex-shrink-0 items-center justify-center text-brand-700 font-extrabold text-sm border border-brand-200/50 shadow-inner">
                                                {{ substr($row->employee->first_name ?? '?', 0, 1) }}{{ substr($row->employee->last_name ?? '', 0, 1) }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-slate-800 tracking-tight">{{ $row->employee->first_name ?? 'Unknown' }} {{ $row->employee->last_name ?? '' }}</p>
                                                <p class="text-[10px] text-slate-500 font-semibold">{{ $row->employee->employee_id ?? 'No ID' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-flex items-center justify-center min-w-[2.5rem] px-2 py-1 bg-green-50 text-green-700 font-bold text-xs rounded-lg border border-green-200/60 shadow-sm">
                                            {{ (int) $row->present_days }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-flex items-center justify-center min-w-[2.5rem] px-2 py-1 {{ $row->absent_days > 0 ? 'bg-red-50 text-red-700 border-red-200/60 shadow-sm' : 'text-slate-400 bg-slate-50/50' }} font-bold text-xs rounded-lg border border-transparent">
                                            {{ (int) $row->absent_days }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-flex items-center justify-center min-w-[2.5rem] px-2 py-1 {{ $row->late_days > 0 ? 'bg-amber-50 text-amber-700 border-amber-200/60 shadow-sm' : 'text-slate-400 bg-slate-50/50' }} font-bold text-xs rounded-lg border border-transparent">
                                            {{ (int) $row->late_days }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-flex items-center justify-center min-w-[2.5rem] px-2 py-1 {{ $row->sick_days > 0 ? 'bg-purple-50 text-purple-700 border-purple-200/60 shadow-sm' : 'text-slate-400 bg-slate-50/50' }} font-bold text-xs rounded-lg border border-transparent">
                                            {{ (int) $row->sick_days }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-flex items-center justify-center min-w-[2.5rem] px-2 py-1 {{ $row->leave_days > 0 ? 'bg-blue-50 text-blue-700 border-blue-200/60 shadow-sm' : 'text-slate-400 bg-slate-50/50' }} font-bold text-xs rounded-lg border border-transparent">
                                            {{ (int) $row->leave_days }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <span class="inline-flex items-center justify-center min-w-[2.5rem] px-2.5 py-1 bg-slate-100 text-slate-700 font-bold text-xs rounded-lg shadow-inner">
                                            {{ (int) $row->total_days }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <a href="{{ route('attendance.index', ['employee_id' => $row->employee_id, 'date_from' => $startDate, 'date_to' => $endDate]) }}" wire:navigate class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 text-slate-600 hover:text-brand-600 hover:border-brand-200 hover:bg-brand-50 rounded-xl font-bold text-[10px] uppercase tracking-wider transition-all shadow-sm">
                                            Details
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-16 text-center">
                                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-50 mb-4">
                                            <svg class="w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-bold text-slate-600">No attendance data found</p>
                                        <p class="text-xs text-slate-400 mt-1">Try selecting a different month or year.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
