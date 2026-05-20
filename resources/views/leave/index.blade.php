<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Leave Requests</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                @if(session('success'))
                    <div class="text-green-600">{{ session('success') }}</div>
                @endif

                <div class="mb-4">
                    <a href="{{ route('leave-requests.create') }}" class="btn">New Leave Request</a>
                </div>

                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="p-2">Type</th>
                            <th class="p-2">Period</th>
                            <th class="p-2">Status</th>
                            <th class="p-2">Submitted</th>
                            <th class="p-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($leaveRequests as $lr)
                        <tr class="border-t">
                            <td class="p-2">{{ $lr->type }}</td>
                            <td class="p-2">{{ $lr->start_date->toDateString() }} - {{ $lr->end_date->toDateString() }}</td>
                            <td class="p-2">{{ $lr->status }}</td>
                            <td class="p-2">{{ $lr->created_at->toDateString() }}</td>
                            <td class="p-2">
                                <a href="{{ route('leave-requests.show', $lr) }}" class="text-blue-600">View</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $leaveRequests->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
