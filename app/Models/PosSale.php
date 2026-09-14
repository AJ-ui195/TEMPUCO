<?php

namespace App\Models;

use App\Enums\PosSaleChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSale extends Model
{
    protected $fillable = [
        'pos_branch_id',
        'member_id',
        'cashier_id',
        'sale_channel',
        'total',
        'amount_paid',
        'change_amount',
        'reference',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sale_channel' => PosSaleChannel::class,
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'change_amount' => 'decimal:2',
        ];
    }

    public function outstandingAmount(): float
    {
        return max(0, round((float) $this->total - (float) $this->amount_paid, 2));
    }

    public function isUnsettled(): bool
    {
        return $this->outstandingAmount() > 0;
    }

    public function hasMember(): bool
    {
        return $this->member_id !== null;
    }

    public function isCreditSale(): bool
    {
        return $this->hasMember() && $this->isUnsettled();
    }

    public function paymentTypeLabel(): string
    {
        return $this->isCreditSale() ? __('Credit') : __('Cash');
    }

    public function memberName(): ?string
    {
        return $this->member?->name;
    }

    public function cashierName(): ?string
    {
        return $this->cashier?->name;
    }

    public function reportPartyLabel(): string
    {
        return $this->memberName() ?? $this->cashierName() ?? '—';
    }

    public function reportPartyColumnLabel(): string
    {
        return $this->hasMember() ? __('Member') : __('Cashier');
    }

    public static function referenceExists(string $reference): bool
    {
        $column = 'reference';

        return static::query()->where($column, $reference)->exists();
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(PosBranch::class, 'pos_branch_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosSaleItem::class, 'pos_sale_id');
    }

    /** Lines still part of the sale — `total` only covers these. */
    public function activeItems(): HasMany
    {
        return $this->items()->notVoided();
    }

    public function voidedItems(): HasMany
    {
        return $this->items()->whereHas('voidRecord');
    }

    public function hasVoidedItems(): bool
    {
        return $this->voidedItems()->exists();
    }

    /** True once every line has been voided, which leaves the sale worth nothing. */
    public function isFullyVoided(): bool
    {
        return $this->items()->exists() && ! $this->activeItems()->exists();
    }
}
