<?php

namespace App\Filament\User\Pages;

use App\Models\Member;
use App\Support\MemberCreditLedger;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class CanteenCreditLedger extends Page
{
    protected static ?string $navigationLabel = 'Canteen / Grocery credit';

    protected static ?string $title = 'Canteen / Grocery credit';

    protected static ?string $slug = 'loan-ledger/canteen-credit';

    protected static ?string $navigationParentItem = 'Loan Ledger';

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = null;

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('Canteen / Grocery credit');
    }

    public function content(Schema $schema): Schema
    {
        /** @var Member $user */
        $user = auth()->user();

        return $schema
            ->components([
                TextEntry::make('canteen_credit_ledger')
                    ->hiddenLabel()
                    ->state(fn (): HtmlString => new HtmlString(
                        view('filament.user.canteen-credit-ledger', [
                            'user' => $user,
                            'entries' => (new MemberCreditLedger($user))->entries(),
                        ])->render()
                    ))
                    ->columnSpanFull(),
            ]);
    }
}
