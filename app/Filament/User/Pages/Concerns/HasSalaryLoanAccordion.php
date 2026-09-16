<?php

namespace App\Filament\User\Pages\Concerns;

use App\Models\Member;
use App\Support\RegularLoanLedgerEntries;
use App\Support\RegularLoanPaymentAllocation;
use App\Support\RegularLoanSchedule;
use Illuminate\Support\HtmlString;

trait HasSalaryLoanAccordion
{
    public string $openSection = '';

    public string $selected = '';

    abstract protected static function defaultOpenSection(): string;

    abstract protected static function defaultSelected(): string;

    public function mountHasSalaryLoanAccordion(): void
    {
        $this->openSection = static::defaultOpenSection();
        $this->selected = static::defaultSelected();
    }

    public function expandSection(string $section): void
    {
        if (! in_array($section, ['regular', 'individual'], true)) {
            return;
        }

        $this->openSection = $this->openSection === $section ? '' : $section;

        if ($section === 'regular' && ! str_starts_with($this->selected, 'regular-')) {
            $this->selected = '';
        }

        if ($section === 'individual' && ! str_starts_with($this->selected, 'individual-')) {
            $this->selected = '';
        }
    }

    public function selectSalaryLoan(string $selected): void
    {
        if (! in_array($selected, ['regular-1', 'regular-2', 'individual-1', 'individual-2'], true)) {
            return;
        }

        $this->selected = $selected;
        $this->openSection = str_starts_with($selected, 'individual-') ? 'individual' : 'regular';
    }

    public function accordionLedgerHtml(): HtmlString
    {
        return new HtmlString(match ($this->selected) {
            'regular-1' => $this->apdsScheduleHtml(false),
            'regular-2' => $this->apdsScheduleHtml(true),
            'individual-1' => $this->paperCardHtml(false),
            'individual-2' => $this->paperCardHtml(true),
            default => '',
        });
    }

    protected function apdsScheduleHtml(bool $salaryTwo): string
    {
        /** @var Member $user */
        $user = auth()->user();
        $loan = $this->latestSalaryLoan($user, $salaryTwo);
        $schedule = $loan ? RegularLoanSchedule::fromLoan($loan) : null;

        return view('filament.user.regular-loan-ledger', [
            'schedule' => $schedule ?? RegularLoanSchedule::blank($salaryTwo),
            'blankAmounts' => $schedule === null,
            'borrowerName' => $user->name,
            'loan' => $loan,
            'paidCash' => $loan ? RegularLoanPaymentAllocation::cashPaid($loan) : 0.0,
        ])->render();
    }

    protected function paperCardHtml(bool $salaryTwo): string
    {
        /** @var Member $user */
        $user = auth()->user();
        $loan = $this->latestSalaryLoan($user, $salaryTwo);

        return view('filament.user.individual-salary-ledger', [
            'user' => $user,
            'loan' => $loan,
            'entries' => RegularLoanLedgerEntries::forLoan($loan),
        ])->render();
    }
}
