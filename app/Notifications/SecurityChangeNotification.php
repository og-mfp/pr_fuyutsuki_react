<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SecurityChangeNotification extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public function __construct(public string $action, public string $reportUrl, public ?string $code = null) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject($this->code ? 'Confirm your security change' : 'Your security settings changed');
        if ($this->code) {
            $mail->line('A security change was requested: '.$this->action.'.')
                ->line('Your confirmation code is '.$this->code.'. It expires in 10 minutes.');
        } else {
            $mail->line('A security change was completed: '.$this->action.'.');
        }

        return $mail->line('If you did not request this change, report it immediately.')
            ->action("It's not me", $this->reportUrl);
    }
}
