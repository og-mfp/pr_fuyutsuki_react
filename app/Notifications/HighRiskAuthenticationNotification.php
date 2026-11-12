<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HighRiskAuthenticationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $userId, public int $score, public string $ip) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('High-risk authentication activity detected')
            ->line('A high-risk authentication attempt was recorded.')
            ->line('Target user ID: '.$this->userId)
            ->line('Risk score: '.$this->score)
            ->line('Source IP: '.$this->ip)
            ->line('Review Security activity in the administration panel.');
    }
}
