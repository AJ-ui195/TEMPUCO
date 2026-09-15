<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTypedLoan;
use Illuminate\Database\Eloquent\Model;

class LoanCertification extends Model
{
    use BelongsToTypedLoan;

    protected $fillable = [
        'character_loan_id',
        'quick_loan_id',
        'regular_loan_id',
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
}
