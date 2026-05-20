<x-app-layout>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <a href="{{ route('employees.show', $employee) }}" wire:navigate class="text-gray-500 hover:text-gray-700 transition-colors">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-gray-900">Edit Employee</h1>
            </div>
        </div>

        <form action="{{ route('employees.update', $employee) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            
            <div class="card p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4 border-b border-gray-100 pb-3">Personal Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                        <input type="text" name="first_name" class="input" value="{{ old('first_name', $employee->first_name) }}" required>
                        @error('first_name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                        <input type="text" name="last_name" class="input" value="{{ old('last_name', $employee->last_name) }}">
                        @error('last_name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                        <input type="email" name="email" class="input" value="{{ old('email', $employee->email) }}" required>
                        @error('email') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                        <input type="text" name="phone" class="input" value="{{ old('phone', $employee->phone) }}">
                        @error('phone') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <div class="card p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4 border-b border-gray-100 pb-3">Employment Details</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Employee ID *</label>
                        <input type="text" name="employee_id" class="input" value="{{ old('employee_id', $employee->employee_id) }}" required>
                        @error('employee_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Joined Date</label>
                        <input type="date" name="joined_at" class="input" value="{{ old('joined_at', $employee->joined_at?->format('Y-m-d')) }}">
                        @error('joined_at') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                        <select name="department_id" class="input">
                            <option value="">Select a department...</option>
                            @foreach($departments as $d)
                                <option value="{{ $d->id }}" {{ old('department_id', $employee->department_id) == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                            @endforeach
                        </select>
                        @error('department_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Designation</label>
                        <select name="designation_id" class="input">
                            <option value="">Select a designation...</option>
                            @foreach($designations as $d)
                                <option value="{{ $d->id }}" {{ old('designation_id', $employee->designation_id) == $d->id ? 'selected' : '' }}>{{ $d->title }}</option>
                            @endforeach
                        </select>
                        @error('designation_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-4">
                <a href="{{ route('employees.show', $employee) }}" wire:navigate class="text-gray-600 hover:text-gray-900 font-medium text-sm">Cancel</a>
                <button type="submit" class="btn">Update Employee</button>
            </div>
        </form>
    </div>
</x-app-layout>
