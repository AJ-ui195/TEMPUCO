<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PosCanteenInventoryItem extends Model
{
    protected $fillable = [
        'name',
        'sku',
        'quantity',
        'unit_price',
        'cost',
        'is_active',
        'menu_slot',
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
            'is_active' => 'boolean',
            'menu_slot' => 'integer',
        ];
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
