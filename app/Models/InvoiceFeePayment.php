<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceFeePayment extends Model
{
    protected $fillable = [
        'member_id',
        'invoice_no',
        'interest',
        'surcharge',
        'membership_fee',
        'others',
        'amount',
        'collection_method',
        'received_by',
        'received_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'interest' => 'decimal:2',
            'surcharge' => 'decimal:2',
            'membership_fee' => 'decimal:2',
            'others' => 'decimal:2',
            'amount' => 'decimal:2',
            'received_at' => 'datetime',
        ];
    }

    public function totalAmount(): float
    {
        return round(
            (float) $this->interest
            + (float) $this->surcharge
            + (float) $this->membership_fee
            + (float) $this->others,
            2,
        );
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
