<?php

namespace App\Filament\User\Pages;

use App\Filament\User\Pages\Concerns\ResolvesMemberSalaryLoan;
use App\Models\Member;
use App\Support\RegularLoanLedgerEntries;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

abstract class IndividualSalaryLedgerPage extends Page
{
    use ResolvesMemberSalaryLoan;

    protected static bool $isDiscovered = false;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationParentItem = 'Individual ledger';

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
                TextEntry::make('salary_individual_ledger')
                    ->hiddenLabel()
                    ->state(function (): HtmlString {
                        /** @var Member $user */
                        $user = auth()->user();
                        $loan = $this->latestSalaryLoan($user, static::isSalaryTwo());

                        return new HtmlString(
                            view('filament.user.individual-salary-ledger', [
                                'user' => $user,
                                'loan' => $loan,
                                'entries' => RegularLoanLedgerEntries::forLoan($loan),
                            ])->render()
                        );
                    })
                    ->columnSpanFull(),
            ]);
    }
}
