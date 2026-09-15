<?php

namespace App\Filament\User\Pages;

use App\Enums\LoanStatus;
use App\Models\Member;
use App\Models\RegularLoan;
use App\Support\RegularLoanPaymentAllocation;
use App\Support\RegularLoanSchedule;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class RegularLoanLedger extends Page
{
    protected static ?string $navigationLabel = 'Regular loan';

    protected static ?string $title = 'Regular loan';

    protected static ?string $slug = 'loan-ledger/regular-loan';

    protected static ?string $navigationParentItem = 'Loan Ledger';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = null;

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('Regular loan');
    }

    public function content(Schema $schema): Schema
    {
        /** @var Member $user */
        $user = auth()->user();
        $loan = $this->latestRegularLoan($user);
        $schedule = $loan ? RegularLoanSchedule::fromLoan($loan) : null;
        $paidCash = $loan ? RegularLoanPaymentAllocation::cashPaid($loan) : 0.0;

        return $schema
            ->components([
                TextEntry::make('regular_loan_ledger')
                    ->hiddenLabel()
                    ->state(fn (): HtmlString => new HtmlString(
                        view('filament.user.regular-loan-ledger', [
                            'schedule' => $schedule ?? RegularLoanSchedule::blank(),
                            'blankAmounts' => $schedule === null,
                            'borrowerName' => $user->name,
                            'loan' => $loan,
                            'paidCash' => $paidCash,
                        ])->render()
                    ))
                    ->columnSpanFull(),
            ]);
    }

    protected function latestRegularLoan(Member $user): ?RegularLoan
    {
        return RegularLoan::query()
            ->forUser($user)
            ->where('status', LoanStatus::Approved)
            ->with('payments')
            ->orderedByLoanDate()
            ->orderByDesc('id')
            ->first();
    }
}
