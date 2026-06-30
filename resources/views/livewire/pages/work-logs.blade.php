<div class="space-y-6 pb-28 md:pb-0">
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
                    Log what you worked on hour by hour. You can override start and end times, and attach image proofs for each time slot to keep an accurate activity log.
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

    <!-- Dynamic Work Progress Indicator -->
    @php
        $totalHours = 0;
        foreach ($logs as $log) {
            if (!empty($log['start_time']) && !empty($log['end_time'])) {
                try {
                    $start = \Carbon\Carbon::createFromFormat('H:i', substr($log['start_time'], 0, 5));
                    $end = \Carbon\Carbon::createFromFormat('H:i', substr($log['end_time'], 0, 5));
                    if ($end->greaterThan($start)) {
                        $totalHours += ($end->timestamp - $start->timestamp) / 3600;
                    }
                } catch (\Exception $e) {
                    // fallback if time is not H:i format
                }
            }
        }
        $percentage = min(100, ($totalHours / 8) * 100);
        $isComplete = $totalHours >= 8;
        $formattedHours = number_format($totalHours, $totalHours == (int)$totalHours ? 0 : 1);
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
                    {{ $formattedHours }} Hour{{ $totalHours != 1 ? 's' : '' }} Logged for {{ \Carbon\Carbon::parse($date)->format('l, F j') }}
                </h3>
                
                <!-- Custom Progress Bar -->
                <div class="w-full bg-slate-100 rounded-full h-3.5 mt-4 overflow-hidden border border-slate-200/50">
                    <div 
                        class="h-full bg-gradient-to-r {{ $isComplete ? 'from-emerald-400 to-teal-500' : 'from-amber-400 to-orange-500' }} rounded-full transition-all duration-500" 
                        style="width: {{ $percentage }}%"
                    ></div>
                </div>
            </div>
            
            <div class="flex-shrink-0 bg-slate-50 border border-slate-100 p-4 rounded-2xl text-center w-full md:w-36">
                <span class="text-3xl font-black {{ $isComplete ? 'text-emerald-600' : 'text-amber-600' }} block tracking-tight">
                    {{ $formattedHours }}/8
                </span>
                <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Hours Target</span>
            </div>
        </div>

        <!-- Centralized Gallery Display in the Day Summary -->
        @php
            $proofs = [];
            foreach ($logs as $index => $log) {
                if (!empty($log['proof_path'])) {
                    $proofs[] = [
                        'url' => asset('storage/' . $log['proof_path']),
                        'time' => $log['start_time'] . ' - ' . $log['end_time'],
                        'activity' => $log['activity'] ?: 'No activity'
                    ];
                }
            }
        @endphp
        @if (count($proofs) > 0)
            <div class="mt-6 pt-6 border-t border-slate-100 relative z-10 animate-fade-in">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block mb-3">Today's Proof Gallery</span>
                <div class="flex flex-wrap gap-3">
                    @foreach ($proofs as $proof)
                        <div class="relative group/gallery rounded-2xl overflow-hidden border border-slate-200 shadow-sm w-16 h-16 bg-slate-50 cursor-pointer transition-all hover:shadow-md"
                             x-on:click="$dispatch('open-lightbox', { url: '{{ $proof['url'] }}' })"
                             title="{{ $proof['activity'] }} ({{ $proof['time'] }})">
                            <img src="{{ $proof['url'] }}" class="w-full h-full object-cover transition-all duration-300 group-hover/gallery:scale-110" alt="Proof">
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover/gallery:opacity-100 transition-opacity flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.637 10.637z" />
                                </svg>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Timesheet Table Card -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
        <header class="bg-slate-50/50 border-b border-slate-100 p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h4 class="text-lg font-bold text-slate-800 tracking-tight">Time Slots & Activities</h4>
                <p class="text-xs text-slate-500 leading-relaxed mt-0.5">Customize time slots and supply matching activities, remarks, and image proof files.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button 
                    type="button"
                    wire:click="addHourSlot"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-50 border border-indigo-100 hover:bg-indigo-100 text-indigo-700 rounded-xl font-bold text-xs transition-all shadow-sm cursor-pointer border-0"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Add Next Hour
                </button>

                @if(!empty($logs) && (count($logs) > 1 || !empty($logs[0]['id'])))
                    <button 
                        type="button"
                        wire:confirm="Are you sure you want to completely clear all logs for this day? This cannot be undone."
                        wire:click="clearAllLogs"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-50 border border-red-100 hover:bg-red-100 text-red-600 rounded-xl font-bold text-xs transition-all shadow-sm cursor-pointer border-0"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                        Reset Day
                    </button>
                @endif
            </div>
        </header>

        <!-- Dynamic Form Fields -->
        <form wire:submit.prevent="save" class="divide-y divide-slate-100">
            <!-- Desktop Table View (Hidden on mobile) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/20 text-slate-400 text-[10px] uppercase font-bold tracking-wider border-b border-slate-100">
                            <th class="py-4 px-6 w-60">Time Range</th>
                            <th class="py-4 px-6 w-80">Activity Name</th>
                            <th class="py-4 px-6">Detailed Remarks</th>
                            <th class="py-4 px-6 w-48">Proof Attachment</th>
                            <th class="py-4 px-4 w-16"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($logs as $index => $log)
                            <tr class="hover:bg-slate-50/30 transition-colors group">
                                <!-- Time slot input fields -->
                                <td class="py-4 px-6 align-top">
                                    <div class="flex flex-col gap-1.5">
                                        <div class="flex items-center gap-1.5">
                                            <input 
                                                type="time" 
                                                wire:model="logs.{{ $index }}.start_time" 
                                                class="w-24 text-xs font-bold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-1.5 focus:border-indigo-500 focus:ring focus:ring-indigo-200/50 transition-colors {{ $errors->has('logs.' . $index . '.start_time') ? 'border-red-300 bg-red-50/10' : '' }}"
                                            />
                                            <span class="text-xs text-slate-400 font-bold">—</span>
                                            <input 
                                                type="time" 
                                                wire:model="logs.{{ $index }}.end_time" 
                                                class="w-24 text-xs font-bold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-1.5 focus:border-indigo-500 focus:ring focus:ring-indigo-200/50 transition-colors {{ $errors->has('logs.' . $index . '.end_time') ? 'border-red-300 bg-red-50/10' : '' }}"
                                            />
                                        </div>
                                        @error('logs.' . $index . '.start_time')
                                            <span class="text-[10px] font-semibold text-red-600 flex items-center gap-0.5 mt-1 leading-tight">
                                                <svg class="w-3.5 h-3.5 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3Z" />
                                                </svg>
                                                {{ $message }}
                                            </span>
                                        @enderror
                                        @error('logs.' . $index . '.end_time')
                                            <span class="text-[10px] font-semibold text-red-600 flex items-center gap-0.5 mt-1 leading-tight">
                                                <svg class="w-3.5 h-3.5 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3Z" />
                                                </svg>
                                                {{ $message }}
                                            </span>
                                        @enderror
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
                                            
                                        />
                                        @error('logs.' . $index . '.activity')
                                            <span class="text-[10px] font-semibold text-red-600 flex items-center gap-0.5 mt-1 leading-tight">
                                                <svg class="w-3.5 h-3.5 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                                        x-data
                                        x-init="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                                        x-on:input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                                        placeholder="What are you working on in this current time? (e.g. fixed ticket #302, deployed to staging)"
                                        class="w-full text-sm text-slate-600 rounded-xl border-slate-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200/50 transition-colors py-2 px-3 resize-y {{ $errors->has('logs.' . $index . '.remarks') ? 'border-red-300 focus:border-red-500 focus:ring-red-200/50 bg-red-50/10' : '' }}"
                                    ></textarea>
                                    @error('logs.' . $index . '.remarks')
                                        <span class="text-[10px] font-semibold text-red-600 flex items-center gap-0.5 mt-1 leading-tight">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </td>

                                <!-- Proof Attachment -->
                                <td class="py-4 px-6 align-top">
                                    <div class="flex items-center gap-2">
                                        @if (isset($newProofs[$index]))
                                            <!-- Temporary Upload Preview / Pending Save Status -->
                                            <div class="relative group/thumb rounded-xl overflow-hidden border border-amber-300 shadow-sm w-12 h-12 flex-shrink-0 bg-amber-50">
                                                @if (method_exists($newProofs[$index], 'temporaryUrl'))
                                                    <img src="{{ $newProofs[$index]->temporaryUrl() }}" class="w-full h-full object-cover" alt="Temporary upload">
                                                @else
                                                    <div class="w-full h-full flex items-center justify-center text-amber-500">
                                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                                        </svg>
                                                    </div>
                                                @endif
                                                <div class="absolute inset-0 bg-amber-500/10 flex items-center justify-center">
                                                    <span class="text-[8px] font-black uppercase text-amber-700 bg-white/90 px-1 py-0.5 rounded shadow-sm tracking-widest">PENDING</span>
                                                </div>
                                            </div>
                                            
                                            <!-- Cancel Upload Button -->
                                            <button type="button" 
                                                    wire:click="deleteProof({{ $index }})" 
                                                    class="text-amber-600 hover:text-amber-800 bg-amber-50 hover:bg-amber-100 p-1.5 rounded-lg border border-amber-100 transition-colors cursor-pointer flex-shrink-0"
                                                    title="Cancel upload">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        @elseif (!empty($log['proof_path']))
                                            @if (isset($pendingDeletions[$index]))
                                                <!-- Pending Deletion Thumbnail with Red desaturated overlay -->
                                                <div class="relative group/thumb rounded-xl overflow-hidden border border-red-300 shadow-sm w-12 h-12 flex-shrink-0 bg-red-50 opacity-60 filter grayscale">
                                                    <img src="{{ asset('storage/' . $log['proof_path']) }}" class="w-full h-full object-cover" alt="Proof thumbnail">
                                                    <div class="absolute inset-0 bg-red-500/20 flex items-center justify-center">
                                                        <span class="text-[7px] font-black uppercase text-red-700 bg-white/95 px-1 py-0.5 rounded shadow-sm tracking-wide">TO DELETE</span>
                                                    </div>
                                                </div>
                                                
                                                <!-- Undo Deletion Button -->
                                                <button type="button" 
                                                        wire:click="undoDeleteProof({{ $index }})" 
                                                        class="text-indigo-650 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 p-1.5 rounded-lg border border-indigo-100 transition-colors cursor-pointer flex-shrink-0"
                                                        title="Undo Delete">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                                                    </svg>
                                                </button>

                                                <!-- Re-upload Button -->
                                                <label for="proof-input-{{ $index }}" 
                                                       class="text-indigo-650 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 p-1.5 rounded-lg border border-indigo-100 transition-colors cursor-pointer flex-shrink-0 flex items-center justify-center"
                                                       title="Re-upload Proof">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                                                    </svg>
                                                </label>
                                            @else
                                                <!-- Thumbnail Preview -->
                                                <div class="relative group/thumb rounded-xl overflow-hidden border border-slate-200 shadow-sm w-12 h-12 flex-shrink-0 bg-slate-50 cursor-pointer"
                                                     x-on:click="$dispatch('open-lightbox', { url: '{{ asset('storage/' . $log['proof_path']) }}' })">
                                                    <img src="{{ asset('storage/' . $log['proof_path']) }}" class="w-full h-full object-cover transition-transform duration-300 group-hover/thumb:scale-110" alt="Proof thumbnail">
                                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover/thumb:opacity-100 transition-opacity flex items-center justify-center">
                                                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                        </svg>
                                                    </div>
                                                </div>
                                                
                                                <!-- Delete Proof Button -->
                                                <button type="button" 
                                                        wire:click="deleteProof({{ $index }})" 
                                                        class="text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 p-1.5 rounded-lg border border-red-100 transition-colors cursor-pointer flex-shrink-0"
                                                        title="Delete proof">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                    </svg>
                                                </button>
                                            @endif
                                        @else
                                            <!-- Upload Button -->
                                            <label for="proof-input-{{ $index }}" 
                                                   class="flex items-center justify-center gap-1.5 px-3 py-2 bg-slate-50 border border-slate-200 hover:bg-slate-100 hover:border-slate-300 text-slate-600 hover:text-slate-800 rounded-xl font-bold text-xs transition-all shadow-sm cursor-pointer w-full text-center">
                                                <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                                </svg>
                                                Attach Proof
                                            </label>
                                        @endif
                                        
                                        <input type="file" 
                                               id="proof-input-{{ $index }}" 
                                               wire:model="newProofs.{{ $index }}" 
                                               class="hidden" 
                                               accept="image/*" />
                                    </div>
                                    
                                    <div wire:loading wire:target="newProofs.{{ $index }}" class="text-[10px] text-indigo-650 font-bold mt-1 flex items-center gap-1">
                                        <svg class="animate-spin h-3.5 w-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Uploading...
                                    </div>
                                    
                                    @error('newProofs.' . $index)
                                        <span class="text-[10px] font-semibold text-red-600 block mt-1 leading-tight">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </td>

                                <!-- Delete Row Action -->
                                <td class="py-4 px-4 align-top text-right">
                                    <button 
                                        type="button" 
                                        wire:click="removeSlot({{ $index }})"
                                        wire:confirm="Are you sure you want to delete this log slot?"
                                        class="p-2 mt-1 text-slate-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors border-0 cursor-pointer"
                                        title="Delete slot"
                                    >
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View (Hidden on desktop) -->
            <div class="md:hidden p-4 space-y-4">
                @foreach ($logs as $index => $log)
                    @php
                        $cardHasErrors = $errors->has('logs.'.$index.'.*') || $errors->has('newProofs.'.$index);
                    @endphp
                    <div class="bg-slate-50/50 border {{ $cardHasErrors ? 'border-red-200 shadow-sm shadow-red-50/50' : 'border-slate-100' }} rounded-2xl p-4 space-y-4 relative"
                         x-data="{ expanded: {{ $cardHasErrors ? 'true' : 'false' }} }">
                        
                        <!-- Collapsible Header -->
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2.5 cursor-pointer select-none" x-on:click="expanded = !expanded">
                            <div class="flex items-center gap-2 overflow-hidden mr-2">
                                <span class="text-xs font-black text-slate-800 tracking-wide flex-shrink-0">Time Slot #{{ $index + 1 }}</span>
                                <span x-show="!expanded" class="text-[10px] text-slate-500 font-semibold truncate max-w-[160px] animate-fade-in" style="display: none;">
                                    {{ !empty($log['start_time']) ? substr($log['start_time'], 0, 5) : '--:--' }} - {{ !empty($log['end_time']) ? substr($log['end_time'], 0, 5) : '--:--' }} | {{ $log['activity'] ?: 'New Slot' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-3">
                                <!-- Delete button on mobile -->
                                <button 
                                    type="button" 
                                    wire:click="removeSlot({{ $index }})"
                                    wire:confirm="Are you sure you want to delete this log slot?"
                                    class="p-1 text-slate-400 hover:text-red-500 rounded-lg transition-colors border-0"
                                    x-on:click.stop
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                                <!-- Arrow toggle indicator -->
                                <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': expanded }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </div>
                        </div>

                        <!-- Card Body (Collapsed/Expanded content) -->
                        <div x-show="expanded" x-transition class="space-y-4" style="display: none;">
                            <!-- Time Range Fields -->
                            <div class="space-y-1">
                                <label class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">Time Range</label>
                                <div class="flex items-center gap-2">
                                    <input 
                                        type="time" 
                                        wire:model="logs.{{ $index }}.start_time" 
                                        class="w-full text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-xl px-3 py-2 focus:border-indigo-500 focus:ring focus:ring-indigo-200/50 transition-colors {{ $errors->has('logs.' . $index . '.start_time') ? 'border-red-300 bg-red-50/10' : '' }}"
                                    />
                                    <span class="text-xs text-slate-400 font-bold">—</span>
                                    <input 
                                        type="time" 
                                        wire:model="logs.{{ $index }}.end_time" 
                                        class="w-full text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-xl px-3 py-2 focus:border-indigo-500 focus:ring focus:ring-indigo-200/50 transition-colors {{ $errors->has('logs.' . $index . '.end_time') ? 'border-red-300 bg-red-50/10' : '' }}"
                                    />
                                </div>
                                @error('logs.' . $index . '.start_time')
                                    <span class="text-[10px] font-semibold text-red-600 flex items-center gap-0.5 mt-1 leading-tight">
                                        <svg class="w-3.5 h-3.5 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3Z" />
                                        </svg>
                                        {{ $message }}
                                    </span>
                                @enderror
                                @error('logs.' . $index . '.end_time')
                                    <span class="text-[10px] font-semibold text-red-600 flex items-center gap-0.5 mt-1 leading-tight">
                                        <svg class="w-3.5 h-3.5 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3Z" />
                                        </svg>
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            <!-- Activity Field -->
                            <div class="space-y-1">
                                <label class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">Activity Name</label>
                                <input 
                                    type="text" 
                                    wire:model="logs.{{ $index }}.activity" 
                                    placeholder="e.g., Code Review, Standup, Development"
                                    class="w-full text-sm font-semibold text-slate-800 rounded-xl border-slate-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200/50 transition-colors {{ $errors->has('logs.' . $index . '.activity') ? 'border-red-300 focus:border-red-500 focus:ring-red-200/50 bg-red-50/10' : '' }}"
                                />
                                @error('logs.' . $index . '.activity')
                                    <span class="text-[10px] font-semibold text-red-600 flex items-center gap-0.5 mt-1 leading-tight">
                                        <svg class="w-3.5 h-3.5 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3Z" />
                                        </svg>
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            <!-- Remarks Field -->
                            <div class="space-y-1">
                                <label class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">Detailed Remarks</label>
                                <textarea 
                                    wire:model="logs.{{ $index }}.remarks" 
                                    rows="2"
                                    x-data
                                    x-init="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                                    x-on:input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                                    placeholder="What are you working on in this current time?"
                                    class="w-full text-sm text-slate-600 rounded-xl border-slate-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200/50 transition-colors py-2 px-3 resize-y min-h-[80px] {{ $errors->has('logs.' . $index . '.remarks') ? 'border-red-300 focus:border-red-500 focus:ring-red-200/50 bg-red-50/10' : '' }}"
                                ></textarea>
                                @error('logs.' . $index . '.remarks')
                                    <span class="text-[10px] font-semibold text-red-600 flex items-center gap-0.5 mt-1 leading-tight">
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            <!-- Proof Attachment Field -->
                            <div class="space-y-1.5">
                                <label class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">Proof Attachment</label>
                                <div class="flex items-center gap-3">
                                    @if (isset($newProofs[$index]))
                                        <!-- Temporary Upload Preview / Pending Save Status -->
                                        <div class="relative group/thumb rounded-xl overflow-hidden border border-amber-300 shadow-sm w-12 h-12 flex-shrink-0 bg-amber-50">
                                            @if (method_exists($newProofs[$index], 'temporaryUrl'))
                                                <img src="{{ $newProofs[$index]->temporaryUrl() }}" class="w-full h-full object-cover" alt="Temporary upload">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center text-amber-500">
                                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                                    </svg>
                                                </div>
                                            @endif
                                            <div class="absolute inset-0 bg-amber-500/10 flex items-center justify-center">
                                                <span class="text-[8px] font-black uppercase text-amber-700 bg-white/90 px-1 py-0.5 rounded shadow-sm tracking-widest">PENDING</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Cancel Upload Button -->
                                        <button type="button" 
                                                wire:click="deleteProof({{ $index }})" 
                                                class="text-amber-600 hover:text-amber-800 bg-amber-50 hover:bg-amber-100 px-3 py-2 rounded-xl border border-amber-100 transition-colors cursor-pointer text-xs font-bold flex items-center gap-1.5">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                            </svg>
                                            Cancel Upload
                                        </button>
                                    @elseif (!empty($log['proof_path']))
                                        @if (isset($pendingDeletions[$index]))
                                            <!-- Pending Deletion Thumbnail with Red desaturated overlay -->
                                            <div class="relative group/thumb rounded-xl overflow-hidden border border-red-300 shadow-sm w-12 h-12 flex-shrink-0 bg-red-50 opacity-60 filter grayscale">
                                                <img src="{{ asset('storage/' . $log['proof_path']) }}" class="w-full h-full object-cover" alt="Proof thumbnail">
                                                <div class="absolute inset-0 bg-red-500/20 flex items-center justify-center">
                                                    <span class="text-[7px] font-black uppercase text-red-700 bg-white/95 px-1 py-0.5 rounded shadow-sm tracking-wide">TO DELETE</span>
                                                </div>
                                            </div>
                                            
                                            <!-- Undo Deletion Button -->
                                            <button type="button" 
                                                    wire:click="undoDeleteProof({{ $index }})" 
                                                    class="text-indigo-650 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-3 py-2 rounded-xl border border-indigo-100 transition-colors cursor-pointer text-xs font-bold flex items-center gap-1.5">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                                                </svg>
                                                Undo Delete
                                            </button>

                                            <!-- Re-upload Button (Mobile) -->
                                            <label for="proof-input-mobile-{{ $index }}" 
                                                   class="text-indigo-650 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-3 py-2 rounded-xl border border-indigo-100 transition-colors cursor-pointer text-xs font-bold flex items-center gap-1.5">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                                                </svg>
                                                Re-upload
                                            </label>
                                        @else
                                            <!-- Thumbnail Preview -->
                                            <div class="relative group/thumb rounded-xl overflow-hidden border border-slate-200 shadow-sm w-12 h-12 flex-shrink-0 bg-slate-50 cursor-pointer"
                                                 x-on:click="$dispatch('open-lightbox', { url: '{{ asset('storage/' . $log['proof_path']) }}' })">
                                                <img src="{{ asset('storage/' . $log['proof_path']) }}" class="w-full h-full object-cover transition-transform duration-300 group-hover/thumb:scale-110" alt="Proof thumbnail">
                                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover/thumb:opacity-100 transition-opacity flex items-center justify-center">
                                                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                    </svg>
                                                </div>
                                            </div>
                                            
                                            <!-- Delete Proof Button -->
                                            <button type="button" 
                                                    wire:click="deleteProof({{ $index }})" 
                                                    class="text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 px-3 py-2 rounded-xl border border-red-100 transition-colors cursor-pointer text-xs font-bold flex items-center gap-1.5">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>
                                                Delete Proof
                                            </button>
                                        @endif
                                    @else
                                        <!-- Upload Button -->
                                        <label for="proof-input-mobile-{{ $index }}" 
                                               class="flex items-center justify-center gap-1.5 px-4 py-2.5 bg-slate-50 border border-slate-200 hover:bg-slate-100 hover:border-slate-300 text-slate-600 hover:text-slate-800 rounded-xl font-bold text-xs transition-all shadow-sm cursor-pointer w-full text-center">
                                            <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                            </svg>
                                            Attach Proof Image
                                        </label>
                                    @endif
                                    
                                    <input type="file" 
                                           id="proof-input-mobile-{{ $index }}" 
                                           wire:model="newProofs.{{ $index }}" 
                                           class="hidden" 
                                           accept="image/*" />
                                </div>
                                <div wire:loading wire:target="newProofs.{{ $index }}" class="text-[10px] text-indigo-650 font-bold mt-1 flex items-center gap-1">
                                    <svg class="animate-spin h-3.5 w-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Uploading image...
                                </div>
                                
                                @error('newProofs.' . $index)
                                    <span class="text-[10px] font-semibold text-red-655 block mt-1 leading-tight">
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Footer Save Bar -->
            <div class="bg-slate-50/50 p-6 flex items-center justify-between border-t border-slate-100 md:relative md:static md:bottom-auto md:w-auto" id="timesheet-footer">
                <div class="text-xs text-slate-500 font-medium">
                    Please make sure to click **Save Timesheet** to persist all daily logs.
                </div>
                
                <div class="flex items-center gap-3">
                    @if(count($logs) > 0)
                        <button 
                            type="button"
                            wire:click="addHourSlot"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 border border-indigo-100 hover:bg-indigo-100 text-indigo-700 rounded-xl font-bold text-xs transition-all shadow-sm cursor-pointer border-0 hidden md:inline-flex"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Add Next Hour
                        </button>

                        <!-- Mobile visible add button (always visible when logs exist) -->
                        <button 
                            type="button"
                            wire:click="addHourSlot"
                            class="inline-flex items-center gap-2 px-3 py-2 bg-indigo-50 border border-indigo-100 hover:bg-indigo-100 text-indigo-700 rounded-xl font-bold text-xs transition-all shadow-sm cursor-pointer md:hidden"
                        >
                            Add Slot
                        </button>
                    @endif

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
            </div>
        </form>
    </div>

    <!-- Toast container (bottom-right) -->
    <div id="toast-container-alpine" x-data="{ toasts: [] }" x-init="window.addEventListener('toast', e => {
            const id = Date.now() + Math.random();
            const message = toastMessageFrom(e.detail);
            const toast = { id, message, type: (e.detail && e.detail.type) || 'success' };
            toasts.push(toast);
            setTimeout(() => { toasts = toasts.filter(t => t.id !== id) }, 4000);
        })" class="fixed bottom-6 right-6 z-50 space-y-3">
        <script>
            // Normalize payload to a message string
            function toastMessageFrom(detail) {
                if (!detail) return '';
                if (typeof detail === 'string') return detail;
                if (Array.isArray(detail)) {
                    if (detail.length === 1) {
                        const first = detail[0];
                        if (!first) return '';
                        if (typeof first === 'string') return first;
                        if (first.message) return first.message;
                        return JSON.stringify(first);
                    }
                    // multiple args - join if strings
                    const strings = detail.filter(d => typeof d === 'string');
                    if (strings.length) return strings.join(' ');
                    return JSON.stringify(detail);
                }
                if (detail.message) return detail.message;
                if (detail[0] && detail[0].message) return detail[0].message;
                return JSON.stringify(detail);
            }

            // Forward Livewire server emits named `toast` to a window-level CustomEvent so our toast listeners work
            if (window.livewire && typeof window.livewire.on === 'function') {
                window.livewire.on('toast', function() {
                    const payload = arguments.length === 1 ? arguments[0] : Array.from(arguments);
                    window.dispatchEvent(new CustomEvent('toast', { detail: payload }));
                });
            }
        </script>
        <template x-for="toast in toasts" :key="toast.id">
            <div :class="toast.type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-amber-50 border-amber-200 text-amber-800'" class="border rounded-xl p-3 shadow-md max-w-xs">
                <div x-text="toast.message" class="text-sm font-semibold"></div>
            </div>
        </template>
    </div>

    <!-- Glassmorphic Fullscreen Lightbox Modal -->
    <div 
        x-data="{ open: false, url: '' }" 
        x-on:open-lightbox.window="open = true; url = $event.detail.url"
        x-on:keydown.escape.window="open = false"
        x-show="open" 
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-md"
        style="display: none;"
    >
        <!-- Modal backdrop -->
        <div class="absolute inset-0 bg-transparent cursor-pointer" x-on:click="open = false"></div>
        
        <!-- Modal container -->
        <div class="relative max-w-4xl max-h-[85vh] w-full bg-white/10 backdrop-blur-xl border border-white/20 rounded-3xl overflow-hidden shadow-2xl p-2 flex flex-col items-center">
            <!-- Close Button -->
            <button 
                type="button" 
                x-on:click="open = false" 
                class="absolute top-4 right-4 text-white hover:text-slate-200 bg-white/10 hover:bg-white/20 p-2.5 rounded-2xl border border-white/10 transition-all z-10 cursor-pointer"
            >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
            
            <!-- Full Image -->
            <img :src="url" class="max-w-full max-h-[80vh] object-contain rounded-2xl" alt="Proof details">
        </div>
    </div>
</div>
