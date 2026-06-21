<?php

namespace App\Http\Controllers;

use App\Enums\LoanStatus;
use App\Models\Loan;
use App\Support\PrintMemberLoan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PrintMemberLoanController extends Controller
{
    public function __invoke(Request $request, Loan $loan): View
    {
        $authUser = $request->user();

        abort_unless(
            $authUser?->isAdmin() || $authUser?->id === $loan->user_id,
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
