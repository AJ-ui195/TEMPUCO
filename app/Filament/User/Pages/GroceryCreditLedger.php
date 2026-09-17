<?php

namespace App\Filament\User\Pages;

use App\Enums\PosSaleChannel;
use App\Models\Member;
use App\Support\MemberCreditLedger;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class GroceryCreditLedger extends Page
{
    protected static ?string $navigationLabel = 'Grocery credit';

    protected static ?string $title = 'Grocery credit';

    protected static ?string $slug = 'loan-ledger/grocery-credit';

    protected static ?string $navigationParentItem = 'Loan Ledger';

    protected static ?int $navigationSort = 6;

    protected static string|BackedEnum|null $navigationIcon = null;

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('Grocery credit');
    }

    public function content(Schema $schema): Schema
    {
        /** @var Member $user */
        $user = auth()->user();

        return $schema
            ->components([
                TextEntry::make('grocery_credit_ledger')
                    ->hiddenLabel()
                    ->state(fn (): HtmlString => new HtmlString(
                        view('filament.user.grocery-credit-ledger', [
                            'user' => $user,
                            'entries' => (new MemberCreditLedger($user))
                                ->entries(PosSaleChannel::Grocery),
                        ])->render()
                    ))
                    ->columnSpanFull(),
            ]);
    }
}
