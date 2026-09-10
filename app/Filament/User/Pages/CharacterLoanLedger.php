<?php

namespace App\Filament\User\Pages;

use App\Enums\LoanStatus;
use App\Models\Loan;
use App\Models\User;
use App\Support\CharacterLoanLedgerEntries;
use App\Support\LoanTypes;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class CharacterLoanLedger extends Page
{
    protected static ?string $navigationLabel = 'Character loan';

    protected static ?string $title = 'Character loan';

    protected static ?string $slug = 'loan-ledger/character-loan';

    protected static ?string $navigationParentItem = 'Loan Ledger';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = null;

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('Character loan');
    }

    public function content(Schema $schema): Schema
    {
        /** @var User $user */
        $user = auth()->user();
        $loans = $this->characterLoans($user);

        return $schema
            ->components([
                TextEntry::make('character_loan_ledger')
                    ->hiddenLabel()
                    ->state(fn (): HtmlString => new HtmlString(
                        view('filament.user.character-loan-ledger', [
                            'user' => $user,
                            'entries' => CharacterLoanLedgerEntries::forLoans($loans),
                        ])->render()
                    ))
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return Collection<int, Loan>
     */
    protected function characterLoans(User $user): Collection
    {
        return Loan::query()
            ->forUser($user)
            ->where('loan_type', LoanTypes::CHARACTER)
            ->where('status', LoanStatus::Approved)
            ->with('payments')
            ->orderedByLoanDate()
            ->orderByDesc('id')
            ->get();
    }
}
