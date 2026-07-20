<x-mail::message>
# {{ __('Monthly Attendance Report') }} - {{ $monthLabel }}

{{ __('Hello') }}, {{ $notifiable->name }}!

{{ __('Here is the attendance summary for **:department** for **:month**:', [
    'department' => $departmentName,
    'month' => $monthLabel,
]) }}

<x-mail::table>
| {{ __('Employee') }} | {{ __('Absent') }} | {{ __('Late') }} | {{ __('Sick') }} | {{ __('Leave') }} |
| :--- | :---: | :---: | :---: | :---: |
@foreach ($attendanceSummaries as $summary)
| **{{ $summary->employeeName }}** | {{ $summary->absentDays }} | {{ $summary->lateDays }} | {{ $summary->sickDays }} | {{ $summary->leaveDays }} |
@endforeach
</x-mail::table>

<x-mail::button :url="route('attendance-report')">
{{ __('View Full Report') }}
</x-mail::button>

{{ __('Regards') }},<br>
{{ config('app.name') }}
</x-mail::message>
