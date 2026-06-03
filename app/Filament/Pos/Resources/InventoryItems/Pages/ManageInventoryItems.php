<?php

namespace App\Filament\Pos\Resources\InventoryItems\Pages;

use App\Filament\Pos\Resources\InventoryItems\InventoryItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageInventoryItems extends ManageRecords
{
    protected static string $resource = InventoryItemResource::class;

    protected static ?string $title = 'Inventory';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('Add product')),
        ];
    }
}
