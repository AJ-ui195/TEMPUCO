<?php

namespace App\Filament\User\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class LoanLedger extends Page
{
    protected static ?string $navigationLabel = 'Loan Ledger';

    protected static ?string $title = 'Loan Ledger';

    protected static ?string $slug = 'loan-ledger';

    protected static ?int $navigationSort = 0;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('Loan Ledger');
    }

    public function mount(): void
    {
        $this->redirect(QuickLoanLedger::getUrl(panel: 'user'));
    }
}
