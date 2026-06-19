<?php

namespace App\Models;

use App\Enums\PosSaleChannel;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSale extends Model
{
    protected $fillable = [
        'pos_branch_id',
        'user_id',
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

    public function isCreditSale(): bool
    {
        return $this->user?->role === UserRole::User;
    }

    public function paymentTypeLabel(): string
    {
        return $this->isCreditSale() ? __('Credit') : __('Cash');
    }

    public function reportPartyLabel(): string
    {
        return $this->user?->name ?? '—';
    }

    public function reportPartyColumnLabel(): string
    {
        return $this->isCreditSale() ? __('Member') : __('Cashier');
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosSaleItem::class, 'pos_sale_id');
    }
}
