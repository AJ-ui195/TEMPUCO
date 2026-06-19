<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\PosInventoryItem;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

final class LowStockNotifier
{
    public static function notifyCatalogItemIfNewlyLow(PosInventoryItem $item, ?int $previousQuantity): void
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

    public static function notifyBranchItemIfNewlyLow(
        PosInventoryItem $item,
        int $quantity,
        ?int $previousQuantity,
        string $branchName,
    ): void {
        if (! $item->is_active || $item->reorder_level === null) {
            return;
        }

        if (! $item->isLowStockAtBranch($quantity)) {
            return;
        }

        if ($previousQuantity !== null && $item->isLowStockAtBranch($previousQuantity)) {
            return;
        }

        self::dispatch(
            title: __('Low stock at :branch', ['branch' => $branchName]),
            body: __(':name — :qty in stock (reorder at :level).', [
                'name' => $item->name,
                'qty' => $quantity,
                'level' => $item->reorder_level,
            ]),
        );
    }

    public static function notifyDashboardSummary(): void
    {
        if (session('inventory_low_stock_summary_shown')) {
            return;
        }

        $items = self::lowStockCatalogItems();

        if ($items->isEmpty()) {
            return;
        }

        session(['inventory_low_stock_summary_shown' => true]);

        $lines = $items
            ->take(5)
            ->map(fn (PosInventoryItem $item): string => "{$item->name} ({$item->quantity})")
            ->implode(', ');

        if ($items->count() > 5) {
            $lines .= ' …';
        }

        Notification::make()
            ->title(trans_choice(
                ':count product is low on stock|:count products are low on stock',
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
     * @return Collection<int, PosInventoryItem>
     */
    public static function lowStockCatalogItems(): Collection
    {
        return PosInventoryItem::query()
            ->where('is_active', true)
            ->whereNotNull('reorder_level')
            ->whereColumn('quantity', '<=', 'reorder_level')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return SupportCollection<int, User>
     */
    protected static function inventoryRecipients(): SupportCollection
    {
        return User::query()
            ->where('role', UserRole::Inventory)
            ->get();
    }

    protected static function dispatch(string $title, string $body): void
    {
        $recipients = self::inventoryRecipients();

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

        if ($user instanceof User && $user->isInventory()) {
            $notification->send();
        }
    }
}
