<?php

namespace App\Models;

use App\Enums\PosSaleChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosCreditPayment extends Model
{
    protected $fillable = [
        'member_id',
        'cashier_id',
        'sale_channel',
        'amount',
        'reference',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sale_channel' => PosSaleChannel::class,
            'amount' => 'decimal:2',
        ];
    }

    public static function referenceExists(string $reference): bool
    {
        return static::query()->where('reference', $reference)->exists();
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }
}
