<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationCodeNotification extends Notification
{
    use Queueable;

    protected string $code;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $code)
    {
        $this->code = $code;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verify Your New Email Address')
            ->greeting('Hello!')
            ->line('You are receiving this email because you requested to update your email address on the Employee Portal.')
            ->line('To complete this update, please use the following 6-digit verification code:')
            ->line($this->code)
            ->line('This verification code is valid for 15 minutes. If you did not request this email change, no further action is required.')
            ->line('Thank you for using our application!');
    }
}
