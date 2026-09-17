<?php

namespace App\Http\Controllers;

use App\Enums\LoanStatus;
use App\Filament\CollectionCashier\Pages\RegularLoanRemittance;
use App\Models\RegularLoan;
use App\Support\RecordMemberLoanPayment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PrintApdsListController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        abort_unless(
            $user?->isCollectionCashier() || $user?->isAdmin(),
            403,
        );

        $ids = collect(explode(',', (string) $request->query('loans')))
            ->map(fn (string $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        $rows = RegularLoan::query()
            ->whereIn('id', $ids->all())
            ->where('status', LoanStatus::Approved)
            ->with(['member', 'payments'])
            ->get()
            ->map(function (RegularLoan $loan): ?array {
                $remaining = RecordMemberLoanPayment::remainingPrincipal($loan);

                if ($remaining <= RecordMemberLoanPayment::EPSILON) {
                    return null;
                }

                return [
                    'loan' => $loan,
                    'remaining' => $remaining,
                    'installment' => RegularLoanRemittance::installmentHint($loan),
                ];
            })
            ->filter()
            ->sortBy(fn (array $row): string => strtolower((string) ($row['loan']->member?->name ?? '')))
            ->values();

        /** @var Collection<int, array{loan: RegularLoan, remaining: float, installment: float}> $rows */
        return view('filament.collection-cashier.print-apds-list', [
            'listed' => $rows,
            'totalInstallment' => round((float) $rows->sum('installment'), 2),
        ]);
    }
}
