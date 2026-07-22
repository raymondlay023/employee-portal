<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{ tab: window.location.hash ? window.location.hash.substring(1) : 'account' }" @hashchange.window="tab = window.location.hash.substring(1)">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row gap-8">
                <!-- Sidebar Navigation -->
                <div class="w-full md:w-64 flex flex-col space-y-2">
                    <a href="#account"
                       @click="tab = 'account'"
                       :class="{ 'bg-indigo-50 text-indigo-700 font-semibold border-indigo-200': tab === 'account', 'text-slate-600 hover:bg-slate-50 border-transparent': tab !== 'account' }"
                       class="px-4 py-3 rounded-xl transition-all border flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        {{ __('Account Details') }}
                    </a>
                    <a href="#employee"
                       @click="tab = 'employee'"
                       :class="{ 'bg-indigo-50 text-indigo-700 font-semibold border-indigo-200': tab === 'employee', 'text-slate-600 hover:bg-slate-50 border-transparent': tab !== 'employee' }"
                       class="px-4 py-3 rounded-xl transition-all border flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        {{ __('Employee Details') }}
                    </a>
                    <a href="#security"
                       @click="tab = 'security'"
                       :class="{ 'bg-indigo-50 text-indigo-700 font-semibold border-indigo-200': tab === 'security', 'text-slate-600 hover:bg-slate-50 border-transparent': tab !== 'security' }"
                       class="px-4 py-3 rounded-xl transition-all border flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        {{ __('Security') }}
                    </a>
                </div>
                
                <!-- Main Content Area -->
                <div class="flex-1 space-y-6">
                    <div x-show="tab === 'account'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" style="display: none;">
                        <livewire:profile.update-profile-information-form />
                    </div>
                    <div x-show="tab === 'employee'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" style="display: none;" x-cloak>
                        <livewire:profile.update-employee-information-form />
                    </div>
                    <div x-show="tab === 'security'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" style="display: none;" x-cloak>
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                            <livewire:profile.update-password-form />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
