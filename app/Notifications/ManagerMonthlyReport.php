<?php

namespace App\Notifications;

use App\DataTransferObjects\AttendanceSummary;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class ManagerMonthlyReport extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param  Collection<int, AttendanceSummary>  $attendanceSummaries
     */
    public function __construct(
        public readonly Collection $attendanceSummaries,
        public readonly Carbon $month,
        public readonly string $departmentName,
    ) {
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $monthLabel = $this->month->translatedFormat('F Y');

        return (new MailMessage)
            ->subject(__('Monthly Attendance Report')." - {$monthLabel}")
            ->markdown('emails.manager-monthly-report', [
                'notifiable' => $notifiable,
                'monthLabel' => $monthLabel,
                'departmentName' => $this->departmentName,
                'attendanceSummaries' => $this->attendanceSummaries,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'monthly_report',
            'month' => $this->month->format('Y-m'),
            'department' => $this->departmentName,
            'sections' => [
                'attendance' => [
                    'employee_count' => $this->attendanceSummaries->count(),
                    'summaries' => $this->attendanceSummaries->map(function ($s) {
                        return [
                            'employee_id' => $s->employeeId,
                            'employee_name' => $s->employeeName,
                            'present' => $s->presentDays,
                            'absent' => $s->absentDays,
                            'late' => $s->lateDays,
                            'sick' => $s->sickDays,
                            'permit' => $s->permitDays,
                            'rate' => $s->attendanceRate(),
                        ];
                    })->toArray(),
                ],
            ],
        ];
    }
}
