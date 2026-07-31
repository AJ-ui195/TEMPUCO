<?php

namespace App\Filament\Cashier\Pages;

use App\Enums\PosSaleChannel;
use App\Models\User;
use App\Support\MemberCreditLedger;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class PosMemberLedgerPage extends Page
{
    protected static ?string $navigationLabel = 'Member ledger';

    protected static ?string $title = 'Member ledger';

    protected static ?string $slug = 'member-ledger';

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected string $view = 'filament.cashier.pos-member-ledger';

    public string $memberSearch = '';

    public ?int $selectedMemberId = null;

    /** Empty shows every channel. */
    public string $channelFilter = '';

    public function mount(): void
    {
        $member = request()->integer('member');

        if ($member > 0 && User::query()->members()->whereKey($member)->exists()) {
            $this->selectedMemberId = $member;
        }
    }

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('Member ledger');
    }

    /**
     * @return Collection<int, User>|EloquentCollection<int, User>
     */
    public function getMemberSearchResults(): Collection|EloquentCollection
    {
        $term = trim($this->memberSearch);

        if (strlen($term) < 2) {
            return collect();
        }

        return User::query()
            ->members()
            ->matchingSearch($term)
            ->orderedByName()
            ->limit(15)
            ->get();
    }

    public function selectMember(int $memberId): void
    {
        if (User::query()->members()->whereKey($memberId)->exists()) {
            $this->selectedMemberId = $memberId;
            $this->memberSearch = '';
        }
    }

    public function clearMember(): void
    {
        $this->selectedMemberId = null;
        $this->channelFilter = '';
    }

    public function getSelectedMember(): ?User
    {
        return $this->selectedMemberId === null
            ? null
            : User::query()->members()->find($this->selectedMemberId);
    }

    public function getChannelFilter(): ?PosSaleChannel
    {
        return PosSaleChannel::tryFrom($this->channelFilter);
    }

    /**
     * @return Collection<int, array{
     *     type: string,
     *     date: \Illuminate\Support\Carbon,
     *     reference: string,
     *     channel: ?PosSaleChannel,
     *     description: string,
     *     charge: float,
     *     payment: float,
     *     balance: float
     * }>
     */
    public function getLedgerEntries(): Collection
    {
        $member = $this->getSelectedMember();

        if (! $member instanceof User) {
            return collect();
        }

        return (new MemberCreditLedger($member))
            ->entries($this->getChannelFilter())
            ->reverse()
            ->values();
    }

    /**
     * @return array{charged: float, paid: float, balance: float, entry_count: int}
     */
    public function getLedgerSummary(): array
    {
        $member = $this->getSelectedMember();

        if (! $member instanceof User) {
            return ['charged' => 0.0, 'paid' => 0.0, 'balance' => 0.0, 'entry_count' => 0];
        }

        return (new MemberCreditLedger($member))->summary($this->getChannelFilter());
    }
}
