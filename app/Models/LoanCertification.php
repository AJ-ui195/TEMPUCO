<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanCertification extends Model
{
    protected $fillable = [
        'loan_id',
        'borrower_name',
        'fixed_savings_deposits',
        'standing_loan',
        'date_of_birth',
        'home_address',
        'treasurer_signed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fixed_savings_deposits' => 'decimal:2',
            'standing_loan' => 'decimal:2',
            'date_of_birth' => 'date',
            'treasurer_signed_at' => 'date',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }
}
