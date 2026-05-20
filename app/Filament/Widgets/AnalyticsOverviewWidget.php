<?php

namespace App\Filament\Widgets;

use App\Enums\LoanStatus;
use App\Enums\UserRole;
use App\Models\Loan;
use App\Models\LoanPayment;
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

        $memberLoansQuery = Loan::query()
            ->whereHas('user', fn ($query) => $query->where('role', UserRole::User))
            ->whereBetween('created_at', [$monthStart, $monthEnd]);

        $totalMemberLoanAmount = (float) (clone $memberLoansQuery)->sum('loan_amount');
        $memberLoanCount = (clone $memberLoansQuery)->count();

        $approvedLoansCount = Loan::query()
            ->where('status', LoanStatus::Approved)
            ->whereBetween('approved_at', [$monthStart, $monthEnd])
            ->count();

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
