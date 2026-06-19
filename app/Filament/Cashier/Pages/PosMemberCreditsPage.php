<?php

namespace App\Filament\Cashier\Pages;

use App\Support\MemberCreditsLedger;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class PosMemberCreditsPage extends Page
{
    protected static ?string $navigationLabel = 'Member credits';

    protected static ?string $title = 'Member credits';

    protected static ?string $slug = 'member-credits';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected string $view = 'filament.cashier.pos-member-credits';

    public string $memberSearch = '';

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('Member credits');
    }

    /**
     * @return Collection<int, array{
     *     member: \App\Models\User,
     *     grocery_outstanding: float,
     *     canteen_outstanding: float,
     *     total_outstanding: float,
     *     items: Collection<int, array{channel: string, date: string, name: string, quantity: int, unit_price: float, line_total: float, reference: string}>
     * }>
     */
    public function getMembersWithCredit(): Collection
    {
        $members = MemberCreditsLedger::membersWithCredit();
        $term = trim($this->memberSearch);

        if ($term === '') {
            return $members;
        }

        $lower = strtolower($term);

        return $members->filter(function (array $row) use ($lower, $term): bool {
            $member = $row['member'];

            return str_contains(strtolower($member->name), $lower)
                || str_contains(strtolower($member->email), $lower)
                || ($member->cellphone && str_contains($member->cellphone, $term));
        })->values();
    }

    public function getTotalOutstanding(): float
    {
        return MemberCreditsLedger::totalOutstanding();
    }
}
