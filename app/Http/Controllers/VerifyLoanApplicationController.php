<?php

namespace App\Http\Controllers;

use App\Enums\LoanStatus;
use App\Models\Loan;
use App\Support\LoanApplicationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VerifyLoanApplicationController extends Controller
{
    public function __invoke(Request $request, Loan $loan, LoanApplicationService $applications)
    {
        try {
            $already = $loan->status === LoanStatus::Pending && $loan->email_verified_at !== null;

            $verified = $applications->confirmFromEmail(
                $loan,
                (string) $request->query('token', ''),
            );
        } catch (ValidationException $exception) {
            return response()->view('loans.verify-email', [
                'ok' => false,
                'already' => false,
                'title' => __('Confirmation failed'),
                'message' => collect($exception->errors())->flatten()->first()
                    ?? __('This confirmation link is invalid or has expired.'),
                'loan' => $loan,
            ], 403);
        }

        return view('loans.verify-email', [
            'ok' => true,
            'already' => $already,
            'title' => $already
                ? __('Application already confirmed')
                : __('Application confirmed'),
            'message' => $already
                ? __('This loan application was already confirmed and is waiting for TEMPUCO review.')
                : __('Thank you. Your loan application has been confirmed and sent to TEMPUCO for review. An admin can now approve or reject it.'),
            'loan' => $verified,
        ]);
    }
}
