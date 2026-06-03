<?php

namespace App\Filament\Pos\Concerns;

use Filament\Pages\Page;

trait InteractsWithInventoryPanel
{
    /**
     * @param  class-string<Page>  $pageClass
     * @param  array<string, mixed>  $parameters
     */
    protected static function inventoryPageUrl(string $pageClass, array $parameters = []): string
    {
        return $pageClass::getUrl($parameters, panel: 'inventory');
    }
}
