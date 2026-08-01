<?php

namespace App\Http\Controllers;

use App\Enums\PosSaleChannel;
use App\Models\User;
use App\Support\PrintMemberLedger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PrintMemberLedgerController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        abort_unless(
            $user?->isCashier() || $user?->isCanteenCashier() || $user?->isAdmin(),
            403,
        );

        $memberId = $request->integer('member');
        $member = $memberId > 0
            ? User::query()->members()->find($memberId)
            : null;

        abort_if($memberId > 0 && ! $member instanceof User, 404);

        return view('filament.cashier.print-member-ledger', PrintMemberLedger::viewData(
            $member,
            PosSaleChannel::tryFrom((string) $request->query('channel')),
            $this->parseDate($request->query('from'))?->startOfDay(),
            $this->parseDate($request->query('to'))?->endOfDay(),
            autoPrint: $request->boolean('auto'),
        ));
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
