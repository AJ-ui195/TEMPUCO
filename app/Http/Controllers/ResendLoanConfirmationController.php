<?php

namespace App\Http\Controllers;

use App\Support\LoanApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ResendLoanConfirmationController extends Controller
{
    public function __invoke(Request $request, string $type, int $loan, LoanApplicationService $applications): RedirectResponse
    {
        $member = $request->user('member');

        abort_unless($member !== null, 403);

        $record = $applications->resolve($type, $loan);

        try {
            $applications->resendConfirmation($member, $record);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('status', __('We sent another confirmation link to your email.'));
    }
}
