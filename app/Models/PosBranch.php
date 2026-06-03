<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosBranch extends Model
{
    protected $fillable = [
        'branch_number',
        'name',
        'code',
        'address',
        'phone',
        'branch_holder',
        'is_active',
        'has_pos',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'branch_number' => 'integer',
            'is_active' => 'boolean',
            'has_pos' => 'boolean',
        ];
    }

    /**
     * @param  Builder<PosBranch>  $query
     * @return Builder<PosBranch>
     */
    public function scopeForPos(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('has_pos', true);
    }

    public function branchInventory(): HasMany
    {
        return $this->hasMany(PosBranchInventory::class, 'pos_branch_id');
    }

    public function inventoryItems(): BelongsToMany
    {
        return $this->belongsToMany(PosInventoryItem::class, 'pos_branch_inventory', 'pos_branch_id', 'pos_inventory_item_id')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function ensureInventoryPivotRecords(): void
    {
        $linkedIds = $this->inventoryItems()->pluck('pos_inventory_items.id');

        PosInventoryItem::query()
            ->whereNotIn('id', $linkedIds)
            ->pluck('id')
            ->each(function (int $itemId): void {
                $this->inventoryItems()->attach($itemId, ['quantity' => 0]);
            });
    }

    /**
     * @return Collection<int, PosInventoryItem>
     */
    public function monitoringInventoryItems(): Collection
    {
        $this->ensureInventoryPivotRecords();

        return PosInventoryItem::query()
            ->select([
                'pos_inventory_items.*',
                'pos_branch_inventory.quantity as branch_quantity',
            ])
            ->join('pos_branch_inventory', function ($join): void {
                $join->on('pos_inventory_items.id', '=', 'pos_branch_inventory.pos_inventory_item_id')
                    ->where('pos_branch_inventory.pos_branch_id', $this->id);
            })
            ->with('supplier')
            ->orderBy('pos_inventory_items.name')
            ->get();
    }
}
