<?php

namespace App\Http\Controllers;

use App\Enums\LoanStatus;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use App\Support\PrintMemberLoan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PrintMemberLoanController extends Controller
{
    public function __invoke(Request $request, Loan $loan): View
    {
        $authUser = $request->user('web') ?? $request->user('member');

        abort_unless(
            ($authUser instanceof User && $authUser->isAdmin())
                || ($authUser instanceof Member && $authUser->id === $loan->user_id),
            403,
        );

        abort_unless(
            $loan->status === LoanStatus::Approved,
            403,
            __('Loan details can only be printed for approved loans.'),
        );

        return view('filament.user.print-loan-details', PrintMemberLoan::viewData(
            $loan,
            autoPrint: $request->boolean('auto'),
        ));
    }
}
