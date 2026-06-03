<?php

namespace App\Filament\Pos\Resources\Suppliers\Pages;

use App\Filament\Pos\Resources\Suppliers\SupplierResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSuppliers extends ManageRecords
{
    protected static string $resource = SupplierResource::class;

    protected static ?string $title = 'Suppliers';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('Add supplier')),
        ];
    }
}
