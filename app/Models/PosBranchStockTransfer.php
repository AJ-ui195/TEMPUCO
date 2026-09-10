<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosBranchStockTransfer extends Model
{
    protected $fillable = [
        'pos_branch_id',
        'pos_inventory_item_id',
        'recorded_by',
        'quantity',
        'expiration_date',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'expiration_date' => 'date',
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

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
