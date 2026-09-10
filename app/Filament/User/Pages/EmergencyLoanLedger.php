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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class EmergencyLoanLedger extends Page
{
    protected static ?string $navigationLabel = 'Emergency loan';

    protected static ?string $title = 'Emergency loan';

    protected static ?string $slug = 'loan-ledger/emergency-loan';

    protected static ?string $navigationParentItem = 'Loan Ledger';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = null;

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isRetiree();
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isRetiree();
    }

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('Emergency loan');
    }

    public function content(Schema $schema): Schema
    {
        /** @var User $user */
        $user = auth()->user();
        $loans = $this->emergencyLoans($user);

        return $schema
            ->components([
                TextEntry::make('emergency_loan_ledger')
                    ->hiddenLabel()
                    ->state(fn (): HtmlString => new HtmlString(
                        view('filament.user.emergency-loan-ledger', [
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
    protected function emergencyLoans(User $user): Collection
    {
        return Loan::query()
            ->forUser($user)
            ->where('loan_type', LoanTypes::EMERGENCY)
            ->where('status', LoanStatus::Approved)
            ->with('payments')
            ->orderedByLoanDate()
            ->orderByDesc('id')
            ->get();
    }
}
