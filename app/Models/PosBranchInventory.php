<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosBranchInventory extends Model
{
    protected $table = 'pos_branch_inventory';

    protected $fillable = [
        'pos_branch_id',
        'pos_inventory_item_id',
        'quantity',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(PosBranch::class, 'pos_branch_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(PosInventoryItem::class, 'pos_inventory_item_id');
    }
}
