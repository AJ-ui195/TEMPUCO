<?php

namespace App\Support;

use App\Models\PosBranchInventory;
use App\Models\PosInventoryItem;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Cache;

/**
 * Alerts grocery cashiers when warehouse or branch stock is within two weeks
 * of its expiration date.
 */
final class ExpirationNotifier
{
    public const WARNING_DAYS = 14;

    public static function notifyCatalogItemIfNewlyExpiring(PosInventoryItem $item): void
    {
        if (! $item->is_active || (int) $item->quantity < 1) {
            return;
        }

        if (! $item->isExpiringSoon(self::WARNING_DAYS) && ! $item->isExpired()) {
            return;
        }

        if ($item->expiration_date === null) {
            return;
        }

        $cacheKey = self::cacheKey('catalog', $item->id, $item->expiration_date->toDateString());

        if (! Cache::add($cacheKey, true, now()->endOfDay())) {
            return;
        }

        self::dispatch(
            title: $item->isExpired()
                ? __('Expired: :name', ['name' => $item->name])
                : __('Expiring soon: :name', ['name' => $item->name]),
            body: self::catalogBody($item),
        );
    }

    public static function notifyBranchItemIfNewlyExpiring(PosBranchInventory $record): void
    {
        $record->loadMissing(['inventoryItem', 'branch']);

        $item = $record->inventoryItem;
        $branch = $record->branch;

        if (! $item || ! $branch || ! $item->is_active || (int) $record->quantity < 1) {
            return;
        }

        if (! $record->isExpiringSoon(self::WARNING_DAYS) && ! $record->isExpired()) {
            return;
        }

        if ($record->expiration_date === null) {
            return;
        }

        $cacheKey = self::cacheKey(
            'branch.'.$branch->id,
            $record->id,
            $record->expiration_date->toDateString(),
        );

        if (! Cache::add($cacheKey, true, now()->endOfDay())) {
            return;
        }

        self::dispatch(
            title: $record->isExpired()
                ? __('Expired at :branch', ['branch' => $branch->name])
                : __('Expiring soon at :branch', ['branch' => $branch->name]),
            body: __(':name — :qty in stock · expires :date.', [
                'name' => $item->name,
                'qty' => $record->quantity,
                'date' => $record->expiration_date->format('M j, Y'),
            ]),
        );
    }

    public static function notifyDashboardSummary(): void
    {
        if (session('grocery_expiration_summary_shown')) {
            return;
        }

        $catalog = self::expiringCatalogItems();
        $branch = self::expiringBranchItems();
        $total = $catalog->count() + $branch->count();

        if ($total === 0) {
            return;
        }

        session(['grocery_expiration_summary_shown' => true]);

        $lines = self::buildSummaryText($catalog->take(4), $branch->take(4));

        Notification::make()
            ->title(trans_choice(
                ':count product expires within 2 weeks|:count products expire within 2 weeks',
                $total,
                ['count' => $total],
            ))
            ->body($lines)
            ->warning()
            ->icon(Heroicon::OutlinedClock)
            ->persistent()
            ->send();
    }

    /**
     * Daily pass: one database notification for anything currently in the window.
     */
    public static function notifyDaily(): int
    {
        $cacheKey = 'pos.expiration.daily.'.now()->toDateString();

        if (! Cache::add($cacheKey, true, now()->endOfDay())) {
            return 0;
        }

        $catalog = self::expiringCatalogItems();
        $branch = self::expiringBranchItems();
        $total = $catalog->count() + $branch->count();

        if ($total === 0) {
            return 0;
        }

        $notification = Notification::make()
            ->title(trans_choice(
                ':count product expires within 2 weeks|:count products expire within 2 weeks',
                $total,
                ['count' => $total],
            ))
            ->body(self::buildSummaryText($catalog->take(8), $branch->take(8)))
            ->warning()
            ->icon(Heroicon::OutlinedClock);

        $recipients = self::groceryRecipients();

        if ($recipients->isNotEmpty()) {
            $notification->sendToDatabase($recipients, isEventDispatched: true);
        }

        return $total;
    }

    /**
     * @return Collection<int, PosInventoryItem>
     */
    public static function expiringCatalogItems(): Collection
    {
        return PosInventoryItem::query()
            ->expiringWithin(self::WARNING_DAYS)
            ->orderedByName()
            ->get();
    }

    /**
     * @return Collection<int, PosBranchInventory>
     */
    public static function expiringBranchItems(): Collection
    {
        return PosBranchInventory::query()
            ->expiringWithin(self::WARNING_DAYS)
            ->with(['inventoryItem', 'branch'])
            ->get()
            ->sortBy(fn (PosBranchInventory $row): string => $row->inventoryItem?->name ?? '')
            ->values();
    }

    /**
     * @param  Collection<int, PosInventoryItem>  $catalog
     * @param  Collection<int, PosBranchInventory>  $branch
     */
    protected static function buildSummaryText(Collection $catalog, Collection $branch): string
    {
        $parts = [];

        foreach ($catalog as $item) {
            $parts[] = self::catalogBody($item);
        }

        foreach ($branch as $row) {
            $parts[] = __(':name at :branch — expires :date', [
                'name' => $row->inventoryItem?->name ?? __('Unknown'),
                'branch' => $row->branch?->name ?? __('Branch'),
                'date' => $row->expiration_date?->format('M j, Y') ?? '—',
            ]);
        }

        return implode(' · ', $parts);
    }

    protected static function catalogBody(PosInventoryItem $item): string
    {
        return __(':name — :qty in stock · expires :date.', [
            'name' => $item->name,
            'qty' => $item->quantity,
            'date' => $item->expiration_date?->format('M j, Y') ?? '—',
        ]);
    }

    /**
     * @return SupportCollection<int, User>
     */
    protected static function groceryRecipients(): SupportCollection
    {
        return User::query()
            ->groceryCashiers()
            ->get();
    }

    protected static function cacheKey(string $scope, int $id, string $expirationDate): string
    {
        return 'pos.expiration.notify.'.$scope.'.'.$id.'.'.$expirationDate.'.'.now()->toDateString();
    }

    protected static function dispatch(string $title, string $body): void
    {
        $recipients = self::groceryRecipients();

        if ($recipients->isEmpty()) {
            return;
        }

        $notification = Notification::make()
            ->title($title)
            ->body($body)
            ->warning()
            ->icon(Heroicon::OutlinedClock);

        $notification->sendToDatabase($recipients, isEventDispatched: true);

        $user = auth()->user();

        if ($user instanceof User && $user->isCashier()) {
            $notification->send();
        }
    }
}
