<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyAdminEmailAuthentication extends Notification
{
    public function __construct(
        public string $code,
        public int $codeExpiryMinutes,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Your TEMPUCO admin sign-in code'))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name ?? __('admin')]))
            ->line(__('Use this 6-digit code to finish signing in to DICNHS TEMPUCO:'))
            ->line("**{$this->code}**")
            ->line(__('This code expires in :minutes minutes. After you enter it, this device will not ask again for 1 day.', [
                'minutes' => $this->codeExpiryMinutes,
            ]))
            ->line(__('If you did not try to sign in, you can ignore this email.'))
            ->salutation(__('Regards,').PHP_EOL.__('DICNHS TEMPUCO'));
    }
}
