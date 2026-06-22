<?php

namespace App\Filament\Canteen\Resources\InventoryItems\Pages;

use App\Filament\Canteen\Resources\InventoryItems\CanteenInventoryItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCanteenInventoryItems extends ManageRecords
{
    protected static string $resource = CanteenInventoryItemResource::class;

    protected static ?string $title = 'Products';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('Add product')),
        ];
    }
}
