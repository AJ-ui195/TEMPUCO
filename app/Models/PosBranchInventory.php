<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosBranchInventory extends Model
{
    protected $table = 'pos_branch_inventory';

    protected $fillable = [
        'pos_branch_id',
        'pos_inventory_item_id',
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

    public function isExpired(): bool
    {
        return $this->expiration_date !== null
            && $this->expiration_date->toDateString() <= now()->toDateString();
    }

    public function isExpiringSoon(int $withinDays = 14): bool
    {
        if ($this->expiration_date === null || $this->isExpired()) {
            return false;
        }

        return $this->expiration_date->toDateString() <= now()->copy()->addDays($withinDays)->toDateString();
    }

    /**
     * Branch stock that expires today or within the next N days.
     *
     * @param  Builder<PosBranchInventory>  $query
     * @return Builder<PosBranchInventory>
     */
    public function scopeExpiringWithin(Builder $query, int $withinDays = 14): Builder
    {
        $today = now()->toDateString();
        $until = now()->copy()->addDays($withinDays)->toDateString();

        return $query
            ->transferred()
            ->whereNotNull($query->qualifyColumn('expiration_date'))
            ->whereDate($query->qualifyColumn('expiration_date'), '>=', $today)
            ->whereDate($query->qualifyColumn('expiration_date'), '<=', $until)
            ->whereHas('inventoryItem', fn (Builder $item) => $item->active());
    }

    /**
     * @param  Builder<PosBranchInventory>  $query
     * @return Builder<PosBranchInventory>
     */
    public function scopeTransferred(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('quantity'), '>', 0);
    }
}
