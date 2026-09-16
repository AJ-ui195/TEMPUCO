<?php

namespace App\Models\Concerns;

use App\Models\CharacterLoan;
use App\Models\Contracts\MemberLoan;
use App\Models\QuickLoan;
use App\Models\RegularLoan;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTypedLoan
{
    public function characterLoan(): BelongsTo
    {
        return $this->belongsTo(CharacterLoan::class);
    }

    public function quickLoan(): BelongsTo
    {
        return $this->belongsTo(QuickLoan::class);
    }

    public function regularLoan(): BelongsTo
    {
        return $this->belongsTo(RegularLoan::class);
    }

    public function loan(): ?MemberLoan
    {
        return $this->characterLoan ?? $this->quickLoan ?? $this->regularLoan;
    }
}
