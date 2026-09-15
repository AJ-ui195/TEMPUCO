<?php

namespace App\Filament\User\Pages;

use Filament\Pages\Page;

class EmergencyLoanLedger extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'loan-ledger/emergency-loan';

    protected string $view = 'filament-panels::page';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(): void
    {
        $this->redirect(CharacterLoanLedger::getUrl(panel: 'user'));
    }
}
