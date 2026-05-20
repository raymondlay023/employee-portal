<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-gray-900 tracking-tight">
                {{ __('Good morning, ') . auth()->user()->name . ' 👋' }}
            </h2>
            <div class="text-sm text-gray-500 font-medium">
                {{ now()->format('l, F j, Y') }}
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Stats Row -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Card 1 -->
                <div class="card p-6 flex items-center space-x-4 hover:-translate-y-1 transition-transform duration-300">
                    <div class="p-3 rounded-full bg-brand-50 text-brand-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Employees</p>
                        <p class="text-2xl font-bold text-gray-900">{{\App\Models\Employee::count() ?? 0}}</p>
                    </div>
                </div>

                <!-- Card 2 -->
                <div class="card p-6 flex items-center space-x-4 hover:-translate-y-1 transition-transform duration-300">
                    <div class="p-3 rounded-full bg-blue-50 text-blue-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">On Leave Today</p>
                        <p class="text-2xl font-bold text-gray-900">0</p>
                    </div>
                </div>

                <!-- Card 3 -->
                <div class="card p-6 flex items-center space-x-4 hover:-translate-y-1 transition-transform duration-300">
                    <div class="p-3 rounded-full bg-green-50 text-green-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Present Today</p>
                        <p class="text-2xl font-bold text-gray-900">0</p>
                    </div>
                </div>
            </div>

            <!-- Welcome Banner -->
            <div class="card p-8 bg-gradient-to-r from-brand-600 to-brand-800 text-white shadow-lg border-0">
                <h3 class="text-2xl font-bold mb-2">Welcome to your new HR Portal!</h3>
                <p class="text-brand-100 mb-6 max-w-2xl">We've redesigned everything to make managing your workforce simpler, faster, and more beautiful. Start by checking your employee directory or managing attendance.</p>
                <div class="flex space-x-4">
                    <a href="{{ route('employees.index') }}" class="inline-flex items-center px-4 py-2 bg-white text-brand-700 font-medium rounded-lg hover:bg-brand-50 transition-colors shadow-sm">
                        View Employees
                    </a>
                    <a href="{{ route('attendance.index') }}" class="inline-flex items-center px-4 py-2 bg-brand-700 border border-brand-500 text-white font-medium rounded-lg hover:bg-brand-600 transition-colors shadow-sm">
                        Record Attendance
                    </a>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>

