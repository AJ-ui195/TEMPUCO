<?php

namespace App\Filament\Pos\Resources\Members\Pages;

use App\Filament\Pos\Resources\Members\MemberResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageMembers extends ManageRecords
{
    protected static string $resource = MemberResource::class;

    protected static ?string $title = 'Members';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('Add member'))
                ->modalHeading(__('Add member'))
                ->successNotificationTitle(__('Member added')),
        ];
    }
}
