<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Support\MemberAccount;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VerifyMemberAccountController extends Controller
{
    public function __invoke(Request $request, Member $member)
    {
        try {
            $already = $member->hasVerifiedEmail();

            $verified = MemberAccount::confirmFromEmail(
                $member,
                (string) $request->query('token', ''),
            );
        } catch (ValidationException $exception) {
            return response()->view('members.verify-email', [
                'ok' => false,
                'already' => false,
                'title' => __('Confirmation failed'),
                'message' => collect($exception->errors())->flatten()->first()
                    ?? __('This confirmation link is invalid or has expired.'),
                'member' => $member,
            ], 403);
        }

        return view('members.verify-email', [
            'ok' => true,
            'already' => $already,
            'title' => $already
                ? __('Email already confirmed')
                : __('Email confirmed'),
            'message' => $already
                ? __('This member account was already confirmed. You can sign in to the Members Portal.')
                : __('Thank you. Your email is confirmed and your TEMPUCO member account is ready. You can now sign in to the Members Portal.'),
            'member' => $verified,
        ]);
    }
}
