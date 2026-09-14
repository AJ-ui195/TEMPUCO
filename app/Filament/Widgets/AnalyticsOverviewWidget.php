<?php

namespace App\Filament\Widgets;

use App\Enums\LoanStatus;
use App\Models\LoanPayment;
use App\Support\MemberLoans;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AnalyticsOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -3;

    protected ?string $heading = 'Analytics overview';

    protected function getStats(): array
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $totalMemberLoanAmount = MemberLoans::sumAmountCreatedBetween($monthStart, $monthEnd);
        $memberLoanCount = MemberLoans::countCreatedBetween($monthStart, $monthEnd);
        $approvedLoansCount = MemberLoans::countApprovedBetween($monthStart, $monthEnd, LoanStatus::Approved);

        $totalReceived = (float) LoanPayment::query()
            ->whereBetween('received_at', [$monthStart, $monthEnd])
            ->sum('amount');

        return [
            Stat::make('Total members loan', number_format($totalMemberLoanAmount, 2))
                ->description($memberLoanCount.' '.__('application(s) this month'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('Approved loans', number_format($approvedLoansCount))
                ->description(__('Approved in').' '.now()->format('F Y'))
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Total received', number_format($totalReceived, 2))
                ->description(__('Loan payments received this month'))
                ->descriptionIcon('heroicon-m-arrow-down-tray')
                ->color('info'),
        ];
    }
}
