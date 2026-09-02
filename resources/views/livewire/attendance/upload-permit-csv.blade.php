<div>
    <!-- Trigger Button -->
    <button type="button"
        wire:click="openModal"
        class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white text-xs font-bold rounded-xl shadow-md shadow-emerald-600/10 transition-all cursor-pointer border-0">
        <svg class="w-4 h-4 text-emerald-100" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
        </svg>
        <span>{{ __('Upload Permit CSV') }}</span>
    </button>

    <!-- Upload Modal -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto"
            x-data
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0">
            
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" 
                wire:click="closeModal"></div>

            <!-- Modal Panel -->
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-100"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                    
                    <!-- Full-Panel Processing Overlay -->
                    <div wire:loading.flex
                        wire:target="importPermit"
                        class="absolute inset-0 bg-white/95 backdrop-blur-md flex flex-col items-center justify-center z-50 p-6 text-center rounded-3xl">
                        <div class="w-16 h-16 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-center mb-4 shadow-sm">
                            <svg class="animate-spin h-8 w-8 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        <h4 class="text-base font-extrabold text-slate-900">{{ __('Processing Permit CSV...') }}</h4>
                        <p class="text-xs text-slate-500 mt-1.5 max-w-xs leading-relaxed">{{ __('Matching employee records with biometric punches and assigning permit days.') }}</p>
                    </div>

                    <div class="bg-white px-6 pb-6 pt-6 sm:p-8">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-emerald-50 sm:mx-0 sm:h-12 sm:w-12 border border-emerald-100">
                                <svg class="h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12-3-3m0 0-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                            </div>
                            <div class="mt-4 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg font-extrabold leading-6 text-slate-900" id="modal-title">{{ __('Upload Monthly Permit CSV') }}</h3>
                                <p class="text-xs text-slate-500 mt-1 font-medium">{{ __('Import employee permit counts for attendance records.') }}</p>
                                
                                @if (session()->has('permit_upload_success'))
                                    <div class="mt-4 p-3.5 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-start gap-2.5">
                                        <svg class="w-5 h-5 text-emerald-600 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                        <p class="text-xs font-bold text-emerald-800 text-left">{{ session('permit_upload_success') }}</p>
                                    </div>
                                @endif

                                @if($importResult)
                                    <!-- Results Breakdown Card -->
                                    <div class="mt-4 bg-slate-50 border border-slate-200 rounded-2xl p-4 text-left space-y-2.5">
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-slate-500 font-semibold">{{ __('Target Month/Year') }}:</span>
                                            <span class="font-extrabold text-slate-800">{{ __(date('F', mktime(0, 0, 0, (int) $importResult['month'], 1))) }} {{ $importResult['year'] }}</span>
                                        </div>
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-slate-500 font-semibold">{{ __('Employees Processed') }}:</span>
                                            <span class="font-extrabold text-slate-800">{{ $importResult['employees_processed'] }} / {{ $importResult['total_rows'] }}</span>
                                        </div>
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-slate-500 font-semibold">{{ __('Total Permit Days Assigned') }}:</span>
                                            <span class="font-extrabold text-emerald-600">{{ $importResult['total_permit_days'] }}</span>
                                        </div>
                                        @if(!empty($importResult['unmatched_niks']))
                                            <div class="pt-2 border-t border-slate-200">
                                                <p class="text-[11px] font-bold text-amber-700">{{ count($importResult['unmatched_niks']) }} {{ __('unmatched NIK(s) skipped') }}:</p>
                                                <p class="text-[10px] text-slate-500 font-mono mt-0.5 truncate">{{ implode(', ', array_slice($importResult['unmatched_niks'], 0, 8)) }} {{ count($importResult['unmatched_niks']) > 8 ? '...' : '' }}</p>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="mt-6 flex flex-row-reverse gap-2">
                                        <button type="button" 
                                            wire:click="closeModal"
                                            class="inline-flex w-full justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-slate-800 sm:w-auto transition-all cursor-pointer">
                                            {{ __('Done & View Report') }}
                                        </button>
                                        <button type="button" 
                                            wire:click="openModal"
                                            class="inline-flex w-full justify-center rounded-xl bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-200 hover:bg-slate-50 sm:w-auto transition-all cursor-pointer">
                                            {{ __('Upload Another') }}
                                        </button>
                                    </div>
                                @else
                                    <div class="mt-5 space-y-4">
                                        <!-- Month & Year Grid -->
                                        <div class="grid grid-cols-2 gap-3 text-left">
                                            <!-- Month Selection -->
                                            <div class="flex flex-col gap-1">
                                                <label for="upload_month" class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">{{ __('Month') }}</label>
                                                <select id="upload_month" wire:model.live="month"
                                                    class="w-full text-xs font-semibold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                                                    @foreach(range(1, 12) as $m)
                                                        <option value="{{ $m }}">{{ __(date('F', mktime(0, 0, 0, $m, 1))) }}</option>
                                                    @endforeach
                                                </select>
                                                @error('month') <span class="text-[10px] text-red-500 font-bold mt-0.5">{{ $message }}</span> @enderror
                                            </div>

                                            <!-- Year Selection -->
                                            <div class="flex flex-col gap-1">
                                                <label for="upload_year" class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">{{ __('Year') }}</label>
                                                <select id="upload_year" wire:model.live="year"
                                                    class="w-full text-xs font-semibold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                                                    @foreach($availableYears as $y)
                                                        <option value="{{ $y }}">{{ $y }}</option>
                                                    @endforeach
                                                </select>
                                                @error('year') <span class="text-[10px] text-red-500 font-bold mt-0.5">{{ $message }}</span> @enderror
                                            </div>
                                        </div>

                                        <!-- CSV File Input -->
                                        <div class="flex flex-col gap-1 text-left">
                                            <label for="csv_file" class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">{{ __('CSV File') }}</label>
                                            <div class="relative">
                                                <input type="file" id="csv_file" wire:model="csv_file" accept=".csv,.txt"
                                                    class="w-full text-xs text-slate-600 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 border border-slate-200 rounded-xl bg-slate-50 p-1.5 transition-all">
                                            </div>

                                            <!-- Temporary Uploading Indicator -->
                                            <div wire:loading wire:target="csv_file" class="mt-2">
                                                <div class="flex items-center gap-1.5 text-[11px] font-bold text-brand-600 bg-brand-50 border border-brand-100 rounded-xl p-2">
                                                    <svg class="animate-spin h-3.5 w-3.5 text-brand-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    <span>{{ __('Uploading file from browser...') }}</span>
                                                </div>
                                            </div>

                                            @if ($csv_file && !$errors->has('csv_file'))
                                                <div wire:loading.remove wire:target="csv_file" class="mt-2 flex items-center gap-2 p-2.5 bg-emerald-50 border border-emerald-200 rounded-xl text-xs font-semibold text-emerald-800">
                                                    <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                    </svg>
                                                    <span class="truncate">{{ $csv_file->getClientOriginalName() }}</span>
                                                    <span class="text-[10px] text-emerald-600 ml-auto font-bold">{{ number_format($csv_file->getSize() / 1024, 1) }} KB</span>
                                                </div>
                                            @endif

                                            <p class="text-[10px] text-slate-400 font-medium mt-1">
                                                {{ __('Expected format: Semicolon (;) or comma (,) separated CSV with headers: No.; Employee ID; Name; Column1') }}
                                            </p>
                                            @error('csv_file') <span class="text-[10px] text-red-500 font-bold mt-0.5 block">{{ $message }}</span> @enderror
                                        </div>

                                        <div class="mt-6 sm:flex sm:flex-row-reverse gap-2">
                                            <button type="button" 
                                                wire:click="importPermit"
                                                wire:loading.attr="disabled"
                                                wire:target="importPermit, csv_file"
                                                class="inline-flex w-full justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-500 sm:w-auto transition-all items-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                                                <svg wire:loading wire:target="importPermit" class="animate-spin -ml-1 mr-1.5 h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <span wire:loading.remove wire:target="importPermit, csv_file">{{ __('Upload Permit') }}</span>
                                                <span wire:loading wire:target="csv_file">{{ __('Uploading...') }}</span>
                                                <span wire:loading wire:target="importPermit">{{ __('Processing...') }}</span>
                                            </button>
                                            <button type="button" 
                                                wire:click="closeModal"
                                                class="mt-2 sm:mt-0 inline-flex w-full justify-center rounded-xl bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-200 hover:bg-slate-50 sm:w-auto transition-all cursor-pointer">
                                                {{ __('Close') }}
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
