<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Leave Request</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-bold mb-2">{{ $leaveRequest->type }} ({{ $leaveRequest->status }})</h3>

                <p><strong>Period:</strong> {{ $leaveRequest->start_date->toDateString() }} - {{ $leaveRequest->end_date->toDateString() }}</p>
                <p class="mt-2"><strong>Reason:</strong> {{ $leaveRequest->reason }}</p>

                <div class="mt-4">
                    <a href="{{ route('leave-requests.index') }}" class="btn">Back</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
