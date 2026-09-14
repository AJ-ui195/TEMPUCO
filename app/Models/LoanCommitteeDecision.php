<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTypedLoan;
use Illuminate\Database\Eloquent\Model;

class LoanCommitteeDecision extends Model
{
    use BelongsToTypedLoan;

    protected $fillable = [
        'character_loan_id',
        'quick_loan_id',
        'regular_loan_id',
        'meeting_date',
        'conditions_notes',
        'approved_amount',
        'minutes_date',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'approved_amount' => 'decimal:2',
            'meeting_date' => 'date',
            'minutes_date' => 'date',
        ];
    }
}
