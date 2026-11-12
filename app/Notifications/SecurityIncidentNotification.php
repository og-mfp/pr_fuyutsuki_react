<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SecurityIncidentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $userId, public string $action, public bool $admin = false) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Unauthorized security change reported')
            ->line($this->admin ? 'A user reported unauthorized security activity. User ID: '.$this->userId : 'Your account has been marked high risk after an unauthorized change was reported.')
            ->line('Security action: '.$this->action.'.')
            ->line($this->admin ? 'Review the security activity and assist with account recovery.' : 'Your sessions and trusted devices have been revoked. Use account recovery or contact an administrator.');
    }
}
