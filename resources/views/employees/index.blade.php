<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-extrabold text-xl text-slate-900 leading-tight tracking-tight">
                    {{ __('Employees Directory') }}
                </h2>
                <p class="text-xs text-slate-400 font-medium mt-0.5">
                    {{ __('Manage employee profiles, designations, and department assignments') }}
                </p>
            </div>
            @can(\App\Authorization\Permissions::MANAGE_EMPLOYEES)
                <div>
                    <a href="{{ route('employees.create') }}" class="inline-flex items-center justify-center px-4 py-2.5 bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs rounded-xl shadow-sm transition-all hover:-translate-y-0.5 active:translate-y-0 transform gap-1.5 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Add Employee
                    </a>
                </div>
            @endcan
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-50 text-green-700 rounded-lg border border-green-200 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    {{ session('success') }}
                </div>
            @endif

            @can(\App\Authorization\Permissions::MANAGE_EMPLOYEES)
                <livewire:employee.sync-jpayroll />
            @endcan

            <livewire:employee-index />

        </div>
    </div>
</x-app-layout>