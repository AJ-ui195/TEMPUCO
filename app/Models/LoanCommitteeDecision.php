<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanCommitteeDecision extends Model
{
    protected $fillable = [
        'loan_id',
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

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }
}
