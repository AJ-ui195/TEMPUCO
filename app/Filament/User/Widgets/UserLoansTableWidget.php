<?php

namespace App\Filament\User\Widgets;

use App\Enums\LoanStatus;
use App\Models\Contracts\MemberLoan;
use App\Models\Member;
use App\Support\MemberLoans;
use App\Support\PrintMemberLoan;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class UserLoansTableWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = false;

    /**
     * @var view-string
     */
    protected string $view = 'filament.user.widgets.user-loans-table';

    /**
     * @return Collection<int, MemberLoan>
     */
    public function loans(): Collection
    {
        $user = Filament::auth()->user();

        if (! $user instanceof Member) {
            return collect();
        }

        return MemberLoans::forMember($user);
    }

    public function printUrl(MemberLoan $loan): string
    {
        return PrintMemberLoan::printUrl($loan);
    }

    public function isApproved(MemberLoan $loan): bool
    {
        return $loan->status === LoanStatus::Approved;
    }
}
