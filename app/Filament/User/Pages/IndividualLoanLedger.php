<?php

namespace App\Filament\User\Pages;

use App\Filament\User\Pages\Concerns\HasSalaryLoanAccordion;
use App\Filament\User\Pages\Concerns\ResolvesMemberSalaryLoan;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class IndividualLoanLedger extends Page
{
    use HasSalaryLoanAccordion;
    use ResolvesMemberSalaryLoan;

    protected static ?string $navigationLabel = 'Individual ledger';

    protected static ?string $title = 'Individual ledger';

    protected static ?string $slug = 'loan-ledger/individual-ledger';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationParentItem = 'Loan Ledger';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = null;

    protected string $view = 'filament.user.salary-loan-accordion-page';

    public function mount(): void
    {
        $this->mountHasSalaryLoanAccordion();
    }

    public function getHeading(): string|Htmlable
    {
        return '';
    }

    public function getTitle(): string|Htmlable
    {
        return match ($this->selected) {
            'regular-1', 'individual-1' => __('Salary loan 1'),
            'regular-2', 'individual-2' => __('Salary loan 2'),
            default => static::$title ?? __('Individual ledger'),
        };
    }

    protected static function defaultOpenSection(): string
    {
        return '';
    }

    protected static function defaultSelected(): string
    {
        return '';
    }
}
