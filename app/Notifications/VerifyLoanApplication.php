<?php

namespace App\Notifications;

use App\Models\Contracts\MemberLoan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyLoanApplication extends Notification
{
    use Queueable;

    public function __construct(
        public MemberLoan $loan,
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
            ->subject(__('Confirm your TEMPUCO loan application'))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name ?? __('member')]))
            ->line(__('You started a loan application with DICNHS TEMPUCO for ₱:amount (:type).', [
                'amount' => number_format((float) $this->loan->loan_amount, 2),
                'type' => $this->loan->loan_type,
            ]))
            ->line(__('Confirm this application from this email before TEMPUCO can review or approve it.'))
            ->action(__('Confirm loan application'), $this->verificationUrl)
            ->line(__('This confirmation link expires in 48 hours. If you did not apply for this loan, you can ignore this email.'))
            ->salutation(__('Regards,').PHP_EOL.__('DICNHS TEMPUCO'));
    }
}
