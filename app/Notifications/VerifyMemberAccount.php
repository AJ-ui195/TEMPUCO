<?php

namespace App\Notifications;

use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyMemberAccount extends Notification
{
    use Queueable;

    public function __construct(
        public Member $member,
        public string $verificationUrl,
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
            ->subject(__('Confirm your TEMPUCO member email'))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name ?? __('member')]))
            ->line(__('Digos City National High School Teachers and Employees Multi-Purpose Cooperative (DICNHS TEMPUCO) started a member account using this email address.'))
            ->line(__('Please confirm this email so your Members Portal account can be used to sign in and apply for loans.'))
            ->action(__('Confirm email address'), $this->verificationUrl)
            ->line(__('This confirmation link expires in 48 hours. If you were not expecting this, you can ignore this email.'))
            ->salutation(__('Regards,').PHP_EOL.__('DICNHS TEMPUCO'));
    }
}
