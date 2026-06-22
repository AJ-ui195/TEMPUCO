<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosCanteenInventoryItem extends Model
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

    public function isLowStock(): bool
    {
        if ($this->reorder_level === null) {
            return false;
        }

        return $this->quantity <= $this->reorder_level;
    }

    /**
     * @param  Builder<PosCanteenInventoryItem>  $query
     * @return Builder<PosCanteenInventoryItem>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('is_active'), true);
    }

    /**
     * @param  Builder<PosCanteenInventoryItem>  $query
     * @return Builder<PosCanteenInventoryItem>
     */
    public function scopeMatchingSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $inner) use ($term): void {
            $inner->where($inner->qualifyColumn('name'), 'like', "%{$term}%")
                ->orWhere($inner->qualifyColumn('sku'), 'like', "%{$term}%");
        });
    }

    /**
     * @param  Builder<PosCanteenInventoryItem>  $query
     * @return Builder<PosCanteenInventoryItem>
     */
    public function scopeLowStockCatalog(Builder $query): Builder
    {
        return $query
            ->active()
            ->whereNotNull($query->qualifyColumn('reorder_level'))
            ->whereColumn(
                $query->qualifyColumn('quantity'),
                '<=',
                $query->qualifyColumn('reorder_level'),
            )
            ->orderedByName();
    }

    public function stockQuantityLabel(): string
    {
        return $this->name.' ('.$this->quantity.')';
    }

    /**
     * @param  Builder<PosCanteenInventoryItem>  $query
     * @return Builder<PosCanteenInventoryItem>
     */
    public function scopeOrderedByName(Builder $query): Builder
    {
        return $query->orderBy($query->qualifyColumn('name'));
    }
}
