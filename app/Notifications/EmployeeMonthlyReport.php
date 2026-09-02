<?php

namespace App\Notifications;

use App\DataTransferObjects\AttendanceSummary;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmployeeMonthlyReport extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly AttendanceSummary $attendanceSummary,
        public readonly Carbon $month,
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
        $s = $this->attendanceSummary;

        return (new MailMessage)
            ->subject(__('Your Monthly Attendance Summary')." - {$monthLabel}")
            ->greeting(__('Hello').", {$notifiable->name}!")
            ->line(__('Here is your attendance summary for **:month**:', ['month' => $monthLabel]))
            ->line(__('Absent').": **{$s->absentDays}** | ".
                   __('Late').": **{$s->lateDays}**")
            ->line(__('Sick').": **{$s->sickDays}** | ".
                   __('Permit').": **{$s->permitDays}**")
            ->action(__('View My Attendance'), route('attendance.index'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $s = $this->attendanceSummary;

        return [
            'type' => 'monthly_report',
            'month' => $this->month->format('Y-m'),
            'sections' => [
                'attendance' => [
                    'total' => $s->totalDays,
                    'present' => $s->presentDays,
                    'absent' => $s->absentDays,
                    'late' => $s->lateDays,
                    'sick' => $s->sickDays,
                    'permit' => $s->permitDays,
                    'rate' => $s->attendanceRate(),
                ],
            ],
        ];
    }
}
