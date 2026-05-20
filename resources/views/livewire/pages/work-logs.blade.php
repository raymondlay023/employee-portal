<?php

use App\Models\DailyWorkLog;
use Livewire\Volt\Component;

new class extends Component
{
    public string $date = '';
    public array $logs = [];

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->date = now()->toDateString();
        $this->loadLogs();
    }

    /**
     * Load work logs for the selected date.
     */
    public function loadLogs(): void
    {
        $dbLogs = DailyWorkLog::where('user_id', auth()->id())
            ->where('date', $this->date)
            ->orderBy('start_time', 'asc')
            ->get();

        if ($dbLogs->isEmpty()) {
            $this->logs = [
                [
                    'id' => null,
                    'user_id' => auth()->id(),
                    'date' => $this->date,
                    'start_time' => '07:30',
                    'end_time' => '08:30',
                    'activity' => '',
                    'remarks' => '',
                ]
            ];
        } else {
            $this->logs = $dbLogs->toArray();
        }
    }

    /**
     * Listener for date updates.
     */
    public function updatedDate(): void
    {
        $this->loadLogs();
    }

    /**
     * Add next chronological hour slot.
     */
    public function addHourSlot(): void
    {
        if (empty($this->logs)) {
            $nextStart = '07:30';
        } else {
            $lastLog = end($this->logs);
            $nextStart = $lastLog['end_time'];
        }

        // Parse start time "HH:MM" and add 1 hour
        [$hours, $minutes] = explode(':', $nextStart);
        $newHours = (int)$hours + 1;
        
        if ($newHours >= 24) {
            $newHours = 0; // wrap around
        }
        
        $nextEnd = sprintf('%02d:%02d', $newHours, (int)$minutes);

        $this->logs[] = [
            'id' => null,
            'user_id' => auth()->id(),
            'date' => $this->date,
            'start_time' => $nextStart,
            'end_time' => $nextEnd,
            'activity' => '',
            'remarks' => '',
        ];
    }

    /**
     * Remove the last chronological hour slot.
     */
    public function removeLastSlot(): void
    {
        if (count($this->logs) > 1) {
            $lastLog = array_pop($this->logs);
            if ($lastLog['id']) {
                DailyWorkLog::destroy($lastLog['id']);
            }
            session()->flash('success', 'Last time slot removed.');
        }
    }

    /**
     * Save all logs in the current timesheet.
     */
    public function save(): void
    {
        $this->validate([
            'logs.*.activity' => 'required|string|max:255',
            'logs.*.remarks' => 'nullable|string|max:1000',
        ], [
            'logs.*.activity.required' => 'The activity field is required.',
        ]);

        foreach ($this->logs as $index => $logData) {
            if (isset($logData['id']) && $logData['id']) {
                $log = DailyWorkLog::find($logData['id']);
                if ($log) {
                    $log->update([
                        'activity' => $logData['activity'],
                        'remarks' => $logData['remarks'],
                    ]);
                }
            } else {
                $newLog = DailyWorkLog::create([
                    'user_id' => auth()->id(),
                    'date' => $this->date,
                    'start_time' => $logData['start_time'],
                    'end_time' => $logData['end_time'],
                    'activity' => $logData['activity'],
                    'remarks' => $logData['remarks'],
                ]);
                $this->logs[$index]['id'] = $newLog->id;
            }
        }

        session()->flash('success', 'Daily work logs saved successfully.');
    }
}; ?>

<div class="space-y-6">
    <!-- Top Level Greeting & Headers -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-brand-700 to-brand-900 text-white shadow-xl p-8 group transition-all duration-300">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_var(--tw-gradient-stops))] from-white/15 via-transparent to-transparent pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-3">
                <span class="text-[10px] uppercase tracking-wider font-extrabold text-white/90 bg-white/10 px-3 py-1 rounded-full backdrop-blur-md border border-white/10">
                    {{ __('Activity Tracker') }}
                </span>
                <h2 class="text-3xl font-extrabold tracking-tight">
                    {{ __('Daily Work Logs') }}
                </h2>
                <p class="text-sm text-brand-100 max-w-2xl leading-relaxed">
                    Log what you worked on hour by hour. All slots are sequentially chained starting from 07:30 to maintain a perfect, overlap-free timeline.
                </p>
            </div>
            
            <!-- Sleek Date Selector -->
            <div class="flex-shrink-0 bg-white/10 backdrop-blur-md border border-white/20 p-4 rounded-2xl flex flex-col gap-1.5 w-full md:w-auto">
                <label for="timesheet-date" class="text-[10px] uppercase font-bold text-brand-200 tracking-wider">Timesheet Date</label>
                <input 
                    type="date" 
                    id="timesheet-date" 
                    wire:model.live="date" 
                    class="bg-white border-0 text-slate-800 font-bold text-sm rounded-xl py-2 px-3 focus:ring-2 focus:ring-brand-500 cursor-pointer"
                />
            </div>
        </div>
    </div>

    <!-- Dual Action Status Alerts -->
    @if (session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl p-4 flex items-center space-x-3 shadow-sm transition-all duration-300">
            <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-sm font-semibold">{{ session('success') }}</span>
        </div>
    @endif

    <!-- Dynamic Work Progress Indicator -->
    @php
        $loggedHours = count($logs);
        $percentage = min(100, ($loggedHours / 8) * 100);
        $isComplete = $loggedHours >= 8;
    @endphp
    <div class="bg-white rounded-3xl p-6 border border-slate-100 hover:shadow-md transition-shadow duration-300 relative overflow-hidden group">
        <div class="absolute right-0 top-0 translate-x-4 -translate-y-4 w-32 h-32 {{ $isComplete ? 'bg-emerald-50/40' : 'bg-amber-50/40' }} rounded-full blur-2xl group-hover:scale-125 transition-transform duration-500"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2 flex-grow">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Day Summary</span>
                    @if ($isComplete)
                        <span class="text-[10px] text-emerald-700 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded-full font-bold">TARGET REACHED</span>
                    @else
                        <span class="text-[10px] text-amber-700 bg-amber-50 border border-amber-100 px-2 py-0.5 rounded-full font-bold">LOGGING REQUIRED</span>
                    @endif
                </div>
                <h3 class="text-xl font-extrabold text-slate-900 leading-tight">
                    {{ $loggedHours }} Hour{{ $loggedHours > 1 ? 's' : '' }} Logged for {{ \Carbon\Carbon::parse($date)->format('l, F j') }}
                </h3>
                
                <!-- Custom Progress Bar -->
                <div class="w-full bg-slate-150 bg-slate-100 rounded-full h-3.5 mt-4 overflow-hidden border border-slate-200/50">
                    <div 
                        class="h-full bg-gradient-to-r {{ $isComplete ? 'from-emerald-400 to-teal-500' : 'from-amber-400 to-orange-500' }} rounded-full transition-all duration-500" 
                        style="width: {{ $percentage }}%"
                    ></div>
                </div>
            </div>
            
            <div class="flex-shrink-0 bg-slate-50 border border-slate-100 p-4 rounded-2xl text-center w-full md:w-36">
                <span class="text-3xl font-black {{ $isComplete ? 'text-emerald-600' : 'text-amber-600' }} block tracking-tight">
                    {{ $loggedHours }}/8
                </span>
                <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Hours Target</span>
            </div>
        </div>
    </div>

    <!-- Timesheet Table Card -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
        <header class="bg-slate-50/50 border-b border-slate-100 p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h4 class="text-lg font-bold text-slate-800 tracking-tight">Chronological Time Slots</h4>
                <p class="text-xs text-slate-500 leading-relaxed mt-0.5">Define your specific activities and remarks inside each locked block.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button 
                    type="button"
                    wire:click="addHourSlot"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-50 border border-indigo-100 hover:bg-indigo-100 text-indigo-700 rounded-xl font-bold text-xs transition-all shadow-sm cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Add Next Hour
                </button>

                @if(count($logs) > 1)
                    <button 
                        type="button"
                        wire:click="removeLastSlot"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-50 border border-red-100 hover:bg-red-100 text-red-600 rounded-xl font-bold text-xs transition-all shadow-sm cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15" />
                        </svg>
                        Remove Last Slot
                    </button>
                @endif
            </div>
        </header>

        <!-- Dynamic Form Fields -->
        <form wire:submit.prevent="save" class="divide-y divide-slate-100">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/20 text-slate-400 text-[10px] uppercase font-bold tracking-wider border-b border-slate-100">
                            <th class="py-4 px-6 w-48">Time Range</th>
                            <th class="py-4 px-6 w-80">Activity Name</th>
                            <th class="py-4 px-6">Detailed Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($logs as $index => $log)
                            <tr class="hover:bg-slate-50/30 transition-colors group">
                                <!-- Time slot badge -->
                                <td class="py-4 px-6 align-top">
                                    <div class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-100/50 font-mono tracking-tight shadow-sm">
                                        <svg class="w-3.5 h-3.5 mr-1 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                        {{ $log['start_time'] }} - {{ $log['end_time'] }}
                                    </div>
                                </td>

                                <!-- Activity Input -->
                                <td class="py-4 px-6 align-top">
                                    <div class="space-y-1">
                                        <input 
                                            type="text" 
                                            wire:model="logs.{{ $index }}.activity" 
                                            placeholder="e.g., Code Review, Standup, Development"
                                            class="w-full text-sm font-semibold text-slate-800 rounded-xl border-slate-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200/50 transition-colors {{ $errors->has('logs.' . $index . '.activity') ? 'border-red-300 focus:border-red-500 focus:ring-red-200/50 bg-red-50/10' : '' }}"
                                            required
                                        />
                                        @error('logs.' . $index . '.activity')
                                            <span class="text-[10px] font-semibold text-red-650 text-red-650 flex items-center gap-0.5 mt-1">
                                                <svg class="w-3.5 h-3.5 text-red-550 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3Z" />
                                                </svg>
                                                {{ $message }}
                                            </span>
                                        @enderror
                                    </div>
                                </td>

                                <!-- Remarks Textarea -->
                                <td class="py-4 px-6 align-top">
                                    <textarea 
                                        wire:model="logs.{{ $index }}.remarks" 
                                        rows="1"
                                        placeholder="What are you working on in this current time? (e.g. fixed ticket #302, deployed to staging)"
                                        class="w-full text-sm text-slate-600 rounded-xl border-slate-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200/50 transition-colors py-2 px-3 resize-y {{ $errors->has('logs.' . $index . '.remarks') ? 'border-red-300 focus:border-red-500 focus:ring-red-200/50 bg-red-50/10' : '' }}"
                                    ></textarea>
                                    @error('logs.' . $index . '.remarks')
                                        <span class="text-[10px] font-semibold text-red-650 flex items-center gap-0.5 mt-1">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Footer Save Bar -->
            <div class="bg-slate-50/50 p-6 flex items-center justify-between border-t border-slate-100">
                <div class="text-xs text-slate-500 font-medium">
                    Please make sure to click **Save Timesheet** to persist all daily logs.
                </div>
                
                <button 
                    type="submit"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs rounded-xl shadow-md shadow-brand-500/10 hover:shadow-brand-500/20 hover:-translate-y-0.5 active:translate-y-0 transform transition-all cursor-pointer border-0"
                >
                    <svg wire:loading wire:target="save" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Save Timesheet
                </button>
            </div>
        </form>
    </div>
</div>
