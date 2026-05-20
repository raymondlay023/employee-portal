<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Attendance</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                @if(session('error'))
                    <div class="text-red-600">{{ session('error') }}</div>
                @endif
                @if(session('success'))
                    <div class="text-green-600">{{ session('success') }}</div>
                @endif

                <div class="flex gap-3 mb-4">
                    <form method="POST" action="{{ route('attendance.clock-in') }}">
                        @csrf
                        <button class="btn">Clock In</button>
                    </form>

                    <form method="POST" action="{{ route('attendance.clock-out') }}">
                        @csrf
                        <button class="btn">Clock Out</button>
                    </form>
                </div>

                <h3 class="font-bold mb-2">Recent Attendance</h3>
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="p-2">Date</th>
                            <th class="p-2">Clock In</th>
                            <th class="p-2">Clock Out</th>
                            <th class="p-2">Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr class="border-t">
                                <td class="p-2">{{ $log->clock_in_at }}</td>
                                <td class="p-2">{{ $log->clock_in_at }}</td>
                                <td class="p-2">{{ $log->clock_out_at ?? '-' }}</td>
                                <td class="p-2">{{ $log->note ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-2">No logs found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
