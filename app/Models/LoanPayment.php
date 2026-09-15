<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTypedLoan;
use App\Support\LedgerChronology;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class LoanPayment extends Model
{
    use BelongsToTypedLoan;

    protected $fillable = [
        'character_loan_id',
        'quick_loan_id',
        'regular_loan_id',
        'amount',
        'kind',
        'interest_applied',
        'principal_applied',
        'official_receipt_no',
        'received_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'interest_applied' => 'decimal:2',
            'principal_applied' => 'decimal:2',
            'received_at' => 'datetime',
        ];
    }

    /**
     * Ledger order is when the payment was saved, not O.R. number.
     *
     * @param  iterable<int, self>  $payments
     * @return Collection<int, self>
     */
    public static function inRecordedOrder(iterable $payments): Collection
    {
        return LedgerChronology::sortByStoredTime(
            collect($payments),
            fn (self $payment) => $payment->created_at ?? $payment->received_at,
            fn (self $payment): int => (int) $payment->id,
        );
    }
}
