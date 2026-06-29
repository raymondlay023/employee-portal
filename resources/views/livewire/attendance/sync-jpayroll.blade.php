<div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden" 
    @if($isRunning || $justQueued) wire:poll.5s @endif>
    <div class="px-6 pt-6 pb-6 flex items-center justify-between gap-4 flex-wrap">
        <div class="flex items-center gap-3">
            <div class="p-2.5 rounded-xl bg-brand-50 border border-brand-100 text-brand-600">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
            </div>
            <div>
                <h3 class="text-base font-extrabold text-slate-900">JPayroll Synchronization</h3>
                <div class="flex items-center gap-2 mt-0.5">
                    @if($isRunning || $justQueued)
                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-100 animate-pulse">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            Syncing...
                        </span>
                    @elseif($latestLog && $latestLog->status === 'failed')
                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-red-600 bg-red-50 px-2 py-0.5 rounded-full border border-red-100">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                            Last sync failed
                        </span>
                    @elseif($latestLog && $latestLog->status === 'success')
                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-100">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Last sync: {{ \Carbon\Carbon::parse($latestLog->started_at)->timezone('Asia/Jakarta')->diffForHumans() }}
                        </span>
                    @else
                        <span class="text-[10px] text-slate-400 font-medium">Never synced</span>
                    @endif
                    
                    <a href="{{ route('system.api-logs') }}" wire:navigate class="text-[10px] text-brand-600 hover:text-brand-700 font-bold ml-2 underline decoration-brand-200 underline-offset-2">View Full Logs &rarr;</a>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="button"
                wire:click="$set('showModal', true)"
                class="inline-flex items-center gap-1.5 px-5 py-2.5 bg-slate-900 hover:bg-slate-800 active:scale-95 text-white text-xs font-bold rounded-xl shadow-md shadow-slate-900/10 transition-all cursor-pointer border-0">
                <svg class="w-4 h-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                New Sync
            </button>
        </div>
    </div>

    <!-- Livewire Modal -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto"
            x-data="{ syncing: false }"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0">
            
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" 
                wire:click="$set('showModal', false)"></div>

            <!-- Modal Panel -->
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-100"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                    
                    <div class="bg-white px-6 pb-6 pt-6 sm:p-8">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-brand-50 sm:mx-0 sm:h-12 sm:w-12 border border-brand-100">
                                <svg class="h-6 w-6 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                            </div>
                            <div class="mt-4 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg font-extrabold leading-6 text-slate-900" id="modal-title">Sync from JPayroll</h3>
                                <p class="text-xs text-slate-500 mt-1 font-medium">Pull the latest attendance data. Date range is limited to 31 days.</p>
                                
                                @if (session()->has('sync_success'))
                                    <div class="mt-4 p-3 bg-emerald-50 border border-emerald-100 rounded-xl flex items-start gap-2">
                                        <svg class="w-4 h-4 text-emerald-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                        <p class="text-xs font-bold text-emerald-700">{{ session('sync_success') }}</p>
                                    </div>
                                @endif

                                <form wire:submit.prevent="sync" class="mt-6 space-y-5">
                                    <!-- Date From -->
                                    <div class="flex flex-col gap-1.5">
                                        <label for="date_from" class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider text-left">Date From</label>
                                        <input type="date" id="date_from" wire:model="date_from" 
                                            class="w-full text-sm border border-slate-200 rounded-xl px-4 py-3 text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-slate-50 transition-all">
                                        @error('date_from') <span class="text-[10px] text-red-500 font-bold text-left mt-1">{{ $message }}</span> @enderror
                                    </div>

                                    <!-- Date To -->
                                    <div class="flex flex-col gap-1.5">
                                        <label for="date_to" class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider text-left">Date To</label>
                                        <input type="date" id="date_to" wire:model="date_to" 
                                            class="w-full text-sm border border-slate-200 rounded-xl px-4 py-3 text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-slate-50 transition-all">
                                        @error('date_to') <span class="text-[10px] text-red-500 font-bold text-left mt-1">{{ $message }}</span> @enderror
                                    </div>

                                    <!-- Specific Employee -->
                                    <div class="flex flex-col gap-1.5" wire:ignore>
                                        <label for="sync_nik" class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider text-left">Specific Employee (Optional)</label>
                                        <select id="sync_nik" wire:model="nik" class="w-full text-sm">
                                            <option value="">All Employees</option>
                                            @foreach($employees as $emp)
                                                <option value="{{ $emp->nik }}">{{ $emp->first_name }} {{ $emp->last_name }} ({{ $emp->nik }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('nik') <span class="text-[10px] text-red-500 font-bold text-left mt-1 block">{{ $message }}</span> @enderror

                                    <div class="mt-8 sm:mt-6 sm:flex sm:flex-row-reverse gap-2">
                                        <button type="submit" 
                                            class="inline-flex w-full justify-center rounded-xl bg-brand-600 px-4 py-3 text-sm font-bold text-white shadow-sm hover:bg-brand-500 sm:w-auto transition-all items-center gap-2">
                                            <svg wire:loading wire:target="sync" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Start Sync
                                        </button>
                                        <button type="button" 
                                            wire:click="$set('showModal', false)"
                                            class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto transition-all">
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
