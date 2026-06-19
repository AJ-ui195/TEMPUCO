<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    /**
     * @param  Builder<PosInventoryItem>  $query
     * @return Builder<PosInventoryItem>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('is_active'), true);
    }

    /**
     * @param  Builder<PosInventoryItem>  $query
     * @return Builder<PosInventoryItem>
     */
    public function scopeMatchingSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $inner) use ($term): void {
            $inner->where($inner->qualifyColumn('name'), 'like', "%{$term}%")
                ->orWhere($inner->qualifyColumn('sku'), 'like', "%{$term}%");
        });
    }

    /**
     * @param  Builder<PosInventoryItem>  $query
     * @return Builder<PosInventoryItem>
     */
    public function scopeOrderedByName(Builder $query): Builder
    {
        return $query->orderBy($query->qualifyColumn('name'));
    }
}
