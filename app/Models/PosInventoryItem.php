<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosInventoryItem extends Model
{
    protected $fillable = [
        'name',
        'sku',
        'description',
        'supplier_id',
        'quantity',
        'unit_price',
        'cost',
        'reorder_level',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'cost' => 'decimal:2',
            'reorder_level' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(PosSupplier::class, 'supplier_id');
    }

    public function branchInventory(): HasMany
    {
        return $this->hasMany(PosBranchInventory::class, 'pos_inventory_item_id');
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(PosBranch::class, 'pos_branch_inventory', 'pos_inventory_item_id', 'pos_branch_id')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function isLowStock(): bool
    {
        if ($this->reorder_level === null) {
            return false;
        }

        return $this->quantity <= $this->reorder_level;
    }

    public function isLowStockAtBranch(int $branchQuantity): bool
    {
        if ($this->reorder_level === null) {
            return false;
        }

        return $branchQuantity <= $this->reorder_level;
    }
}
