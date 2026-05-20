<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">New Leave Request</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('leave-requests.store') }}">
                    @csrf

                    <div class="mb-4">
                        <label class="block">Type</label>
                        <select name="type" class="w-full">
                            <option value="annual">Annual</option>
                            <option value="sick">Sick</option>
                            <option value="unpaid">Unpaid</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block">Start Date</label>
                        <input type="date" name="start_date" class="w-full" required />
                    </div>

                    <div class="mb-4">
                        <label class="block">End Date</label>
                        <input type="date" name="end_date" class="w-full" required />
                    </div>

                    <div class="mb-4">
                        <label class="block">Reason</label>
                        <textarea name="reason" class="w-full"></textarea>
                    </div>

                    <div>
                        <button class="btn">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
