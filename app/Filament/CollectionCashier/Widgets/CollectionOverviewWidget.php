<?php

namespace App\Filament\CollectionCashier\Widgets;

use App\Enums\PosSaleChannel;
use App\Models\LoanPayment;
use App\Models\Member;
use App\Models\PosCreditPayment;
use App\Support\MemberCollectionAccounts;
use App\Support\MemberCreditsLedger;
use App\Support\PhilippineTime;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CollectionOverviewWidget extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = -3;

    protected function getStats(): array
    {
        $today = PhilippineTime::now()->toDateString();
        $monthStart = PhilippineTime::now()->copy()->startOfMonth();
        $monthEnd = PhilippineTime::now()->copy()->endOfMonth();
        $portfolio = MemberCollectionAccounts::portfolio();

        $canteenToday = (float) PosCreditPayment::query()
            ->where('sale_channel', PosSaleChannel::Canteen)
            ->whereDate('created_at', $today)
            ->sum('amount');

        $loanToday = (float) LoanPayment::query()
            ->whereDate('received_at', $today)
            ->sum('amount');

        $canteenMonth = (float) PosCreditPayment::query()
            ->where('sale_channel', PosSaleChannel::Canteen)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->sum('amount');

        $loanMonth = (float) LoanPayment::query()
            ->whereBetween('received_at', [$monthStart, $monthEnd])
            ->sum('amount');

        return [
            Stat::make(__('Members'), number_format(Member::query()->count()))
                ->description(__('Registered members'))
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
            Stat::make(__('Open loans'), number_format($portfolio['approved_with_balance']))
                ->description(__('Approved loans with remaining principal'))
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),
            Stat::make(__('Loan outstanding'), '₱'.number_format($portfolio['loan_outstanding'], 2))
                ->description(__('Remaining principal on approved loans'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),
            Stat::make(__('Canteen receivables'), '₱'.number_format(MemberCreditsLedger::canteenOutstandingTotal(), 2))
                ->description(__('Unpaid canteen credit'))
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('info'),
            Stat::make(__('Collected today'), '₱'.number_format($canteenToday + $loanToday, 2))
                ->description(__('Loan payments + canteen collections'))
                ->descriptionIcon('heroicon-m-arrow-down-tray')
                ->color('success'),
            Stat::make(__('Collected this month'), '₱'.number_format($canteenMonth + $loanMonth, 2))
                ->description(__('Loan payments + canteen collections'))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('success'),
        ];
    }
}
