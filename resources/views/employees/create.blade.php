@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Create Employee</h1>

    <form action="{{ route('employees.store') }}" method="POST" class="bg-white p-4 shadow rounded">
        @csrf
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block">Employee ID</label>
                <input name="employee_id" class="input" value="{{ old('employee_id') }}" required />
            </div>
            <div>
                <label class="block">First name</label>
                <input name="first_name" class="input" value="{{ old('first_name') }}" required />
            </div>
            <div>
                <label class="block">Last name</label>
                <input name="last_name" class="input" value="{{ old('last_name') }}" />
            </div>
            <div>
                <label class="block">Email</label>
                <input name="email" class="input" value="{{ old('email') }}" required />
            </div>
            <div>
                <label class="block">Department</label>
                <select name="department_id" class="input">
                    <option value="">--</option>
                    @foreach($departments as $d)
                        <option value="{{ $d->id }}">{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block">Designation</label>
                <select name="designation_id" class="input">
                    <option value="">--</option>
                    @foreach($designations as $d)
                        <option value="{{ $d->id }}">{{ $d->title }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block">Phone</label>
                <input name="phone" class="input" value="{{ old('phone') }}" />
            </div>
            <div>
                <label class="block">Joined at</label>
                <input type="date" name="joined_at" class="input" value="{{ old('joined_at') }}" />
            </div>
        </div>

        <div class="mt-4">
            <button class="btn">Create</button>
        </div>
    </form>
</div>
@endsection
