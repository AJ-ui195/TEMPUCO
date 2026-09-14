<?php

namespace App\Filament\User\Pages;

use App\Enums\LoanStatus;
use App\Models\Loan;
use App\Models\Member;
use App\Support\LoanTypes;
use App\Support\QuickLoanLedgerEntries;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class QuickLoanLedger extends Page
{
    protected static ?string $navigationLabel = 'Quick loan';

    protected static ?string $title = 'Quick loan';

    protected static ?string $slug = 'loan-ledger/quick-loan';

    protected static ?string $navigationParentItem = 'Loan Ledger';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = null;

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('Quick loan');
    }

    public function content(Schema $schema): Schema
    {
        /** @var Member $user */
        $user = auth()->user();
        $loans = $this->quickLoans($user);
        $activeLoan = $loans->first();

        return $schema
            ->components([
                TextEntry::make('quick_loan_ledger')
                    ->hiddenLabel()
                    ->state(fn (): HtmlString => new HtmlString(
                        view('filament.user.quick-loan-ledger', [
                            'user' => $user,
                            'loan' => $activeLoan,
                            'entries' => QuickLoanLedgerEntries::forLoans($loans),
                        ])->render()
                    ))
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return Collection<int, Loan>
     */
    protected function quickLoans(Member $user): Collection
    {
        return Loan::query()
            ->forUser($user)
            ->where('loan_type', LoanTypes::QUICK)
            ->where('status', LoanStatus::Approved)
            ->with('payments')
            ->orderedByLoanDate()
            ->orderByDesc('id')
            ->get();
    }
}
