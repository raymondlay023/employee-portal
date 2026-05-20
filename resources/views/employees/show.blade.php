@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4">
    <div class="bg-white shadow p-4 rounded">
        <h1 class="text-2xl font-bold">{{ $employee->first_name }} {{ $employee->last_name }}</h1>
        <p class="text-sm text-gray-600">{{ $employee->employee_id }}</p>

        <div class="mt-4 grid grid-cols-2 gap-4">
            <div>
                <strong>Email</strong>
                <div>{{ $employee->email }}</div>
            </div>
            <div>
                <strong>Phone</strong>
                <div>{{ $employee->phone }}</div>
            </div>
            <div>
                <strong>Department</strong>
                <div>{{ $employee->department?->name }}</div>
            </div>
            <div>
                <strong>Designation</strong>
                <div>{{ $employee->designation?->title }}</div>
            </div>
            <div>
                <strong>Joined</strong>
                <div>{{ $employee->joined_at?->toDateString() }}</div>
            </div>
            <div>
                <strong>Status</strong>
                <div>{{ $employee->status }}</div>
            </div>
        </div>

        <div class="mt-4">
            <a href="{{ route('employees.edit', $employee) }}" class="btn">Edit</a>
            <form action="{{ route('employees.destroy', $employee) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger">Delete</button>
            </form>
        </div>
    </div>
</div>
@endsection
