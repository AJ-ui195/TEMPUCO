<?php

namespace App\Filament\Pos\Pages;

use App\Models\PosInventoryItem;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class InventoryDashboard extends BaseDashboard
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    #[\Override]
    public function content(Schema $schema): Schema
    {
        $totalItems = PosInventoryItem::query()->count();
        $activeItems = PosInventoryItem::query()->where('is_active', true)->count();
        $lowStockItems = PosInventoryItem::query()
            ->where('is_active', true)
            ->whereNotNull('reorder_level')
            ->whereColumn('quantity', '<=', 'reorder_level')
            ->count();

        return $schema
            ->components([
                Section::make(__('Welcome'))
                    ->description(__('You are signed in to the inventory portal. Use the sidebar to manage products, suppliers, and branches.')),
                Section::make(__('Inventory overview'))
                    ->description(__('Total: :total · Active: :active · Low stock: :low', [
                        'total' => $totalItems,
                        'active' => $activeItems,
                        'low' => $lowStockItems,
                    ])),
            ]);
    }
}
