<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosSaleItemVoid extends Model
{
    protected $fillable = [
        'pos_sale_item_id',
        'voided_by',
        'reason',
    ];

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(PosSaleItem::class, 'pos_sale_item_id');
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }
}
