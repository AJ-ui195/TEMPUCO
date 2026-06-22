<?php

namespace App\Support;

use App\Models\PosCanteenInventoryItem;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

final class CanteenLowStockNotifier
{
    public static function notifyCatalogItemIfNewlyLow(PosCanteenInventoryItem $item, ?int $previousQuantity): void
    {
        if (! $item->is_active || $item->reorder_level === null) {
            return;
        }

        if (! $item->isLowStock()) {
            return;
        }

        if ($previousQuantity !== null && $previousQuantity <= $item->reorder_level) {
            return;
        }

        self::dispatch(
            title: __('Low stock: :name', ['name' => $item->name]),
            body: __(':qty in stock (reorder at :level).', [
                'qty' => $item->quantity,
                'level' => $item->reorder_level,
            ]),
        );
    }

    public static function notifyDashboardSummary(): void
    {
        if (session('canteen_low_stock_summary_shown')) {
            return;
        }

        $items = self::lowStockCatalogItems();

        if ($items->isEmpty()) {
            return;
        }

        session(['canteen_low_stock_summary_shown' => true]);

        $lines = self::buildLowStockSummaryText($items->take(5));

        if ($items->count() > 5) {
            $lines .= ' …';
        }

        Notification::make()
            ->title(trans_choice(
                ':count canteen product is low on stock|:count canteen products are low on stock',
                $items->count(),
                ['count' => $items->count()],
            ))
            ->body($lines)
            ->warning()
            ->icon(Heroicon::OutlinedExclamationTriangle)
            ->persistent()
            ->send();
    }

    /**
     * @return Collection<int, PosCanteenInventoryItem>
     */
    public static function lowStockCatalogItems(): Collection
    {
        return PosCanteenInventoryItem::query()
            ->lowStockCatalog()
            ->get();
    }

    /**
     * @param  Collection<int, PosCanteenInventoryItem>  $items
     */
    protected static function buildLowStockSummaryText(Collection $items): string
    {
        $parts = [];

        foreach ($items as $item) {
            $parts[] = $item->stockQuantityLabel();
        }

        return implode(', ', $parts);
    }

    /**
     * @return SupportCollection<int, User>
     */
    protected static function canteenRecipients(): SupportCollection
    {
        return User::query()
            ->canteenCashiers()
            ->get();
    }

    protected static function dispatch(string $title, string $body): void
    {
        $recipients = self::canteenRecipients();

        if ($recipients->isEmpty()) {
            return;
        }

        $notification = Notification::make()
            ->title($title)
            ->body($body)
            ->warning()
            ->icon(Heroicon::OutlinedExclamationTriangle);

        $notification->sendToDatabase($recipients, isEventDispatched: true);

        $user = auth()->user();

        if ($user instanceof User && $user->isCanteenCashier()) {
            $notification->send();
        }
    }
}
