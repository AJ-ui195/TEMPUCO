<?php

namespace App\Http\Controllers;

use App\Enums\LoanStatus;
use App\Models\Contracts\MemberLoan;
use App\Models\Member;
use App\Models\User;
use App\Support\MemberLoans;
use App\Support\PrintMemberLoan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PrintMemberLoanController extends Controller
{
    public function __invoke(Request $request, string $type, int $loan): View
    {
        $model = MemberLoans::modelForPrintType($type);
        abort_unless($model !== null, 404);

        /** @var MemberLoan $record */
        $record = $model::query()->findOrFail($loan);

        $authUser = $request->user('web') ?? $request->user('member');

        abort_unless(
            ($authUser instanceof User && $authUser->isAdmin())
                || ($authUser instanceof Member && $authUser->id === $record->member_id),
            403,
        );

        abort_unless(
            $record->status === LoanStatus::Approved,
            403,
            __('Loan details can only be printed for approved loans.'),
        );

        return view('filament.user.print-loan-details', PrintMemberLoan::viewData(
            $record,
            autoPrint: $request->boolean('auto'),
        ));
    }
}
