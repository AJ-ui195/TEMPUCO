<?php

namespace App\Filament\CollectionCashier\Widgets;

use App\Enums\PosSaleChannel;
use App\Models\InvoiceFeePayment;
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

        $loanToday = round($this->loanOfficialReceiptTotalForDate($today) + $this->invoiceCollectionTotalForDate($today), 2);

        $canteenMonth = (float) PosCreditPayment::query()
            ->where('sale_channel', PosSaleChannel::Canteen)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->sum('amount');

        $loanMonth = round(
            $this->loanOfficialReceiptTotalBetween($monthStart, $monthEnd)
            + $this->invoiceCollectionTotalBetween($monthStart, $monthEnd),
            2,
        );

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
                ->description(__('All loan collections (O.R. + Invoice) and canteen'))
                ->descriptionIcon('heroicon-m-arrow-down-tray')
                ->color('success'),
            Stat::make(__('Collected this month'), '₱'.number_format($canteenMonth + $loanMonth, 2))
                ->description(__('All loan collections (O.R. + Invoice) and canteen'))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('success'),
        ];
    }

    protected function loanOfficialReceiptTotalForDate(string $date): float
    {
        return (float) LoanPayment::query()
            ->whereDate('received_at', $date)
            ->sum('amount');
    }

    protected function invoiceCollectionTotalForDate(string $date): float
    {
        return (float) InvoiceFeePayment::query()
            ->whereDate('received_at', $date)
            ->sum('amount');
    }

    protected function loanOfficialReceiptTotalBetween($start, $end): float
    {
        return (float) LoanPayment::query()
            ->whereBetween('received_at', [$start, $end])
            ->sum('amount');
    }

    protected function invoiceCollectionTotalBetween($start, $end): float
    {
        return (float) InvoiceFeePayment::query()
            ->whereBetween('received_at', [$start, $end])
            ->sum('amount');
    }
}
