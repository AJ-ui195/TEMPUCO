<?php

namespace App\Filament\User\Pages;

use App\Models\Loan;
use App\Models\User;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class QuickLoanLedger extends Page
{
    private const QUICK_LOAN_TYPE = 'QUICK LOAN';

    protected static ?string $navigationLabel = 'Quick loan';

    protected static ?string $title = 'Quick loan';

    protected static ?string $slug = 'loan-ledger/quick-loan';

    protected static ?string $navigationParentItem = 'Loan Ledger';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = null;

    public ?string $quickLoanId = null;

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('Quick loan');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('quick_loan_ledger')
                    ->hiddenLabel()
                    ->state(fn (): HtmlString => new HtmlString(
                        view('filament.user.quick-loan-ledger', [
                            'user' => auth()->user(),
                            'quickLoans' => $this->quickLoans(),
                            'quickLoanId' => $this->quickLoanId,
                        ])->render()
                    ))
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return Collection<int, Loan>
     */
    protected function quickLoans(): Collection
    {
        /** @var User $user */
        $user = auth()->user();

        return Loan::query()
            ->forUser($user)
            ->where('loan_type', self::QUICK_LOAN_TYPE)
            ->orderedByLoanDate()
            ->orderByDesc('id')
            ->get();
    }
}
