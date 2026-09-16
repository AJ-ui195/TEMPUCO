<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTypedLoan;
use App\Support\LedgerChronology;
use App\Support\RoleDashboard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'receipt_kind',
        'received_at',
        'received_by',
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

    protected static function booted(): void
    {
        static::creating(function (self $payment): void {
            if ($payment->received_by !== null) {
                return;
            }

            $account = RoleDashboard::currentUser();

            if ($account instanceof User) {
                $payment->received_by = $account->id;
            }
        });
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

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
