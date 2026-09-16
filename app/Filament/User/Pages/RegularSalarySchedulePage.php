<?php

namespace App\Filament\User\Pages;

use App\Filament\User\Pages\Concerns\ResolvesMemberSalaryLoan;
use App\Models\Member;
use App\Support\RegularLoanPaymentAllocation;
use App\Support\RegularLoanSchedule;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

abstract class RegularSalarySchedulePage extends Page
{
    use ResolvesMemberSalaryLoan;

    protected static bool $isDiscovered = false;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationParentItem = 'Regular loan';

    protected static string|BackedEnum|null $navigationIcon = null;

    abstract protected static function isSalaryTwo(): bool;

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? (static::isSalaryTwo() ? __('Salary loan 2') : __('Salary loan 1'));
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('regular_loan_schedule')
                    ->hiddenLabel()
                    ->state(function (): HtmlString {
                        /** @var Member $user */
                        $user = auth()->user();
                        $loan = $this->latestSalaryLoan($user, static::isSalaryTwo());
                        $schedule = $loan ? RegularLoanSchedule::fromLoan($loan) : null;

                        return new HtmlString(
                            view('filament.user.regular-loan-ledger', [
                                'schedule' => $schedule ?? RegularLoanSchedule::blank(static::isSalaryTwo()),
                                'blankAmounts' => $schedule === null,
                                'borrowerName' => $user->name,
                                'loan' => $loan,
                                'paidCash' => $loan ? RegularLoanPaymentAllocation::cashPaid($loan) : 0.0,
                            ])->render()
                        );
                    })
                    ->columnSpanFull(),
            ]);
    }
}
