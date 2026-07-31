<?php

namespace App\Filament\Cashier\Pages;

use App\Enums\PosSaleChannel;
use App\Models\PosCreditPayment;
use App\Models\User;
use App\Support\MemberCreditsLedger;
use App\Support\MemberPosCredit;
use App\Support\SettleMemberCredit;
use BackedEnum;
use Filament\Notifications\Notification;
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

    public ?int $paymentMemberId = null;

    public string $paymentChannel = '';

    public string $paymentAmount = '';

    public ?string $paymentError = null;

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

    public function openPaymentModal(int $memberId, string $channel): void
    {
        $member = $this->findMember($memberId);
        $saleChannel = PosSaleChannel::tryFrom($channel);

        if (! $member instanceof User || ! $saleChannel instanceof PosSaleChannel) {
            return;
        }

        $outstanding = (new MemberPosCredit($member))->outstandingFor($saleChannel);

        if ($outstanding <= 0) {
            Notification::make()
                ->title(__('Nothing to pay'))
                ->body(__(':member has no outstanding :channel balance.', [
                    'member' => $member->name,
                    'channel' => strtolower($saleChannel->getLabel()),
                ]))
                ->warning()
                ->send();

            return;
        }

        $this->paymentMemberId = $member->id;
        $this->paymentChannel = $saleChannel->value;
        $this->paymentAmount = number_format($outstanding, 2, '.', '');
        $this->paymentError = null;
    }

    public function closePaymentModal(): void
    {
        $this->paymentMemberId = null;
        $this->paymentChannel = '';
        $this->paymentAmount = '';
        $this->paymentError = null;
    }

    public function useFullPaymentAmount(): void
    {
        $outstanding = $this->getPaymentOutstanding();

        if ($outstanding > 0) {
            $this->paymentAmount = number_format($outstanding, 2, '.', '');
            $this->paymentError = null;
        }
    }

    public function getPaymentMember(): ?User
    {
        return $this->paymentMemberId === null
            ? null
            : $this->findMember($this->paymentMemberId);
    }

    public function getPaymentSaleChannel(): ?PosSaleChannel
    {
        return PosSaleChannel::tryFrom($this->paymentChannel);
    }

    public function getPaymentOutstanding(): float
    {
        $member = $this->getPaymentMember();
        $channel = $this->getPaymentSaleChannel();

        if (! $member instanceof User || ! $channel instanceof PosSaleChannel) {
            return 0;
        }

        return (new MemberPosCredit($member))->outstandingFor($channel);
    }

    public function getPaymentRemainingPreview(): float
    {
        return round(max(0, $this->getPaymentOutstanding() - (float) $this->paymentAmount), 2);
    }

    /**
     * @return Collection<int, PosCreditPayment>
     */
    public function getRecentPayments(int $memberId): Collection
    {
        return PosCreditPayment::query()
            ->where('member_id', $memberId)
            ->with('cashier')
            ->latest()
            ->limit(3)
            ->get();
    }

    public function recordPayment(): void
    {
        $member = $this->getPaymentMember();
        $channel = $this->getPaymentSaleChannel();

        if (! $member instanceof User || ! $channel instanceof PosSaleChannel) {
            $this->closePaymentModal();

            return;
        }

        $amount = round((float) $this->paymentAmount, 2);
        $outstanding = $this->getPaymentOutstanding();

        if ($amount < 0.01) {
            $this->paymentError = __('Enter the amount the member is paying.');

            return;
        }

        if ($amount - $outstanding > SettleMemberCredit::EPSILON) {
            $this->paymentError = __('Payment cannot be more than the ₱:amount balance.', [
                'amount' => number_format($outstanding, 2),
            ]);

            return;
        }

        $result = SettleMemberCredit::apply($member, $channel, $amount, auth()->user());

        $remaining = $result['remaining_balance'];

        Notification::make()
            ->title(__('Payment recorded'))
            ->body($remaining > 0
                ? __(':member paid ₱:paid — remaining :channel balance: ₱:remaining', [
                    'member' => $member->name,
                    'paid' => number_format($result['applied'], 2),
                    'channel' => strtolower($channel->getLabel()),
                    'remaining' => number_format($remaining, 2),
                ])
                : __(':member paid ₱:paid — :channel credit fully settled.', [
                    'member' => $member->name,
                    'paid' => number_format($result['applied'], 2),
                    'channel' => strtolower($channel->getLabel()),
                ]))
            ->success()
            ->send();

        $this->closePaymentModal();
    }

    protected function findMember(int $memberId): ?User
    {
        return User::query()->members()->find($memberId);
    }
}
