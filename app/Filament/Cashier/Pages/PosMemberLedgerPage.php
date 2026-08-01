<?php

namespace App\Filament\Cashier\Pages;

use App\Enums\PosSaleChannel;
use App\Models\User;
use App\Support\MemberCreditLedger;
use App\Support\PrintMemberLedger;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
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

    /** Empty dates show the whole history. */
    public string $fromDate = '';

    public string $toDate = '';

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

    public function clearDates(): void
    {
        $this->fromDate = '';
        $this->toDate = '';
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

    public function getFromDate(): ?Carbon
    {
        return $this->parseDate($this->fromDate)?->startOfDay();
    }

    public function getToDate(): ?Carbon
    {
        return $this->parseDate($this->toDate)?->endOfDay();
    }

    public function getRangeLabel(): string
    {
        $from = $this->getFromDate();
        $to = $this->getToDate();

        if ($from && $to) {
            return $from->format('M j, Y').' — '.$to->format('M j, Y');
        }

        if ($from) {
            return __('From :date', ['date' => $from->format('M j, Y')]);
        }

        if ($to) {
            return __('Up to :date', ['date' => $to->format('M j, Y')]);
        }

        return __('All dates');
    }

    /** Printable ledger for the member on screen. */
    public function getPrintMemberUrl(): ?string
    {
        $member = $this->getSelectedMember();

        return $member instanceof User
            ? PrintMemberLedger::url($member, $this->getChannelFilter(), $this->fromDate, $this->toDate)
            : null;
    }

    /** Printable ledger for every member with activity in the current filters. */
    public function getPrintAllUrl(): string
    {
        return PrintMemberLedger::url(null, $this->getChannelFilter(), $this->fromDate, $this->toDate);
    }

    public function getMembersWithActivityCount(): int
    {
        return MemberCreditLedger::membersWithActivity(
            $this->getChannelFilter(),
            $this->getFromDate(),
            $this->getToDate(),
        )->count();
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
            ->entries($this->getChannelFilter(), $this->getFromDate(), $this->getToDate())
            ->reverse()
            ->values();
    }

    /**
     * @return array{opening: float, charged: float, paid: float, balance: float, entry_count: int}
     */
    public function getLedgerSummary(): array
    {
        $member = $this->getSelectedMember();

        if (! $member instanceof User) {
            return ['opening' => 0.0, 'charged' => 0.0, 'paid' => 0.0, 'balance' => 0.0, 'entry_count' => 0];
        }

        return (new MemberCreditLedger($member))->summary(
            $this->getChannelFilter(),
            $this->getFromDate(),
            $this->getToDate(),
        );
    }

    private function parseDate(string $value): ?Carbon
    {
        if (trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
