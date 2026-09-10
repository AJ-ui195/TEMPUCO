<?php

namespace App\Filament\User\Pages;

use App\Models\Loan;
use App\Models\User;
use App\Support\RegularLoanSchedule;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class RegularLoanLedger extends Page
{
    private const QUICK_LOAN_TYPE = 'QUICK LOAN';

    protected static ?string $navigationLabel = 'Regular loan';

    protected static ?string $title = 'Regular loan';

    protected static ?string $slug = 'loan-ledger/regular-loan';

    protected static ?string $navigationParentItem = 'Loan Ledger';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = null;

    public ?string $regularLoanId = null;

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('Regular loan');
    }

    public function content(Schema $schema): Schema
    {
        /** @var User $user */
        $user = auth()->user();
        $schedule = $this->selectedSchedule($user);

        return $schema
            ->components([
                TextEntry::make('regular_loan_ledger')
                    ->hiddenLabel()
                    ->state(fn (): HtmlString => new HtmlString(
                        view('filament.user.regular-loan-ledger', [
                            'regularLoans' => $this->regularLoans($user),
                            'regularLoanId' => $this->regularLoanId,
                            'schedule' => $schedule ?? RegularLoanSchedule::blank(),
                            'blankAmounts' => $schedule === null,
                            'borrowerName' => $user->name,
                        ])->render()
                    ))
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return Collection<int, Loan>
     */
    protected function regularLoans(User $user): Collection
    {
        return Loan::query()
            ->forUser($user)
            ->where('loan_type', '!=', self::QUICK_LOAN_TYPE)
            ->orderedByLoanDate()
            ->orderByDesc('id')
            ->get();
    }

    protected function selectedSchedule(User $user): ?RegularLoanSchedule
    {
        if (! filled($this->regularLoanId)) {
            return null;
        }

        $loan = $this->regularLoans($user)->first(
            fn (Loan $loan): bool => (string) $loan->getKey() === $this->regularLoanId
        );

        return $loan ? RegularLoanSchedule::fromLoan($loan) : null;
    }
}
