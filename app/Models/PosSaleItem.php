<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosSaleItem extends Model
{
    protected $fillable = [
        'pos_sale_id',
        'pos_inventory_item_id',
        'pos_canteen_inventory_item_id',
        'quantity',
        'unit_price',
        'line_total',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class, 'pos_sale_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(PosInventoryItem::class, 'pos_inventory_item_id');
    }

    public function canteenInventoryItem(): BelongsTo
    {
        return $this->belongsTo(PosCanteenInventoryItem::class, 'pos_canteen_inventory_item_id');
    }

    public function catalogProduct(): PosInventoryItem|PosCanteenInventoryItem|null
    {
        return $this->inventoryItem ?? $this->canteenInventoryItem;
    }

    public function productName(): string
    {
        return $this->catalogProduct()?->name ?? __('Unknown item');
    }

    public function productSku(): ?string
    {
        return $this->catalogProduct()?->sku;
    }
}
