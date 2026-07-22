<?php

use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public string $phone = '';
    public string $gender = '';
    public array $skills = [];

    // Read-only fields
    public string $employee_id = '';
    public string $department_name = '';
    public string $designation_name = '';
    public string $joined_at = '';
    
    public bool $hasEmployeeRecord = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $employee = $user->employee;

        if ($employee) {
            $this->hasEmployeeRecord = true;
            $this->phone = $employee->phone ?? '';
            $this->gender = $employee->gender ?? '';
            $this->skills = $employee->skills ?? [];
            
            $this->employee_id = $employee->employee_id ?? '';
            $this->department_name = $employee->department->name ?? 'N/A';
            $this->designation_name = $employee->designation->name ?? 'N/A';
            $this->joined_at = $employee->joined_at ? $employee->joined_at->format('M d, Y') : 'N/A';
        }
    }

    /**
     * Update the employee information.
     */
    public function updateEmployeeInformation(): void
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return;
        }

        $validated = $this->validate([
            'phone' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', 'string', 'max:10'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['string', 'max:50'],
        ]);

        $employee->phone = $this->phone;
        $employee->gender = $this->gender;
        $employee->skills = $this->skills;
        
        $employee->save();

        $this->dispatch('employee-updated');
    }
}; ?>

<section class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
    <header class="border-b border-slate-100 pb-4 mb-6">
        <h2 class="text-xl font-semibold text-slate-950 flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
            </svg>
            {{ __('Employee Details') }}
        </h2>

        <p class="mt-1 text-sm text-slate-600">
            {{ __("Manage your employee profile and personal information.") }}
        </p>
    </header>

    @if ($hasEmployeeRecord)
        <div class="mb-8 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                <div class="text-xs text-slate-500 uppercase font-bold tracking-wider mb-1">{{ __('Employee ID') }}</div>
                <div class="text-slate-900 font-medium">{{ $employee_id }}</div>
            </div>
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                <div class="text-xs text-slate-500 uppercase font-bold tracking-wider mb-1">{{ __('Department') }}</div>
                <div class="text-slate-900 font-medium">{{ $department_name }}</div>
            </div>
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                <div class="text-xs text-slate-500 uppercase font-bold tracking-wider mb-1">{{ __('Designation') }}</div>
                <div class="text-slate-900 font-medium">{{ $designation_name }}</div>
            </div>
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                <div class="text-xs text-slate-500 uppercase font-bold tracking-wider mb-1">{{ __('Joined Date') }}</div>
                <div class="text-slate-900 font-medium">{{ $joined_at }}</div>
            </div>
        </div>

        <form wire:submit="updateEmployeeInformation" class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Phone -->
                <div>
                    <x-input-label for="phone" :value="__('Phone Number')" class="text-slate-700 font-medium" />
                    <x-text-input wire:model="phone" id="phone" type="text" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200/50" autocomplete="tel" />
                    <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                </div>

                <!-- Gender -->
                <div>
                    <x-input-label for="gender" :value="__('Gender')" class="text-slate-700 font-medium" />
                    <select wire:model="gender" id="gender" class="mt-1 block w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200/50">
                        <option value="">{{ __('Select Gender') }}</option>
                        <option value="Male">{{ __('Male') }}</option>
                        <option value="Female">{{ __('Female') }}</option>
                        <option value="Other">{{ __('Other') }}</option>
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('gender')" />
                </div>
            </div>

            <!-- Skills (Alpine JS Tags Input) -->
            <div 
                x-data="{ 
                    newSkill: '',
                    skills: @entangle('skills')
                }" 
                class="w-full"
            >
                <x-input-label for="skills" :value="__('Skills')" class="text-slate-700 font-medium" />
                <p class="text-xs text-slate-500 mb-2">{{ __('Press enter or comma to add a skill') }}</p>
                
                <div class="mt-1 block w-full rounded-xl border border-slate-200 bg-white focus-within:border-indigo-500 focus-within:ring focus-within:ring-indigo-200/50 overflow-hidden px-3 py-2 flex flex-wrap gap-2 items-center">
                    <template x-for="(skill, index) in skills" :key="index">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-sm font-medium bg-indigo-50 text-indigo-700">
                            <span x-text="skill"></span>
                            <button type="button" @click="skills.splice(index, 1)" class="text-indigo-400 hover:text-indigo-600 focus:outline-none">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </span>
                    </template>
                    <input 
                        type="text" 
                        x-model="newSkill" 
                        @keydown.enter.prevent="if(newSkill.trim() !== '') { skills.push(newSkill.trim()); newSkill = ''; }"
                        @keydown.comma.prevent="if(newSkill.trim() !== '') { skills.push(newSkill.trim()); newSkill = ''; }"
                        @keydown.backspace="if(newSkill === '' && skills.length > 0) { skills.pop(); }"
                        class="flex-1 outline-none border-none p-0 focus:ring-0 min-w-[120px] text-sm text-slate-700" 
                        placeholder="Add a skill..."
                    >
                </div>
                <x-input-error class="mt-2" :messages="$errors->get('skills')" />
            </div>

            <div class="flex items-center gap-4 border-t border-slate-100 pt-5">
                <x-primary-button class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800 transition-all font-semibold">
                    {{ __('Save Changes') }}
                </x-primary-button>

                <x-action-message class="text-sm font-medium text-emerald-600 flex items-center gap-1" on="employee-updated">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ __('Employee details saved successfully.') }}
                </x-action-message>
            </div>
        </form>
    @else
        <div class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <h3 class="mt-2 text-sm font-semibold text-slate-900">{{ __('No Employee Record Found') }}</h3>
            <p class="mt-1 text-sm text-slate-500">{{ __('Your account is not linked to an employee record.') }}</p>
        </div>
    @endif
</section>
