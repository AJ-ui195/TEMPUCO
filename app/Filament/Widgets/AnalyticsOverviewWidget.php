<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class AnalyticsOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -3;

    protected ?string $heading = 'Analytics overview';

    protected function getStats(): array
    {
        $totalUsers = User::count();

        $thisWeek = User::where('created_at', '>=', now()->startOfWeek())->count();
        $lastWeek = User::where('created_at', '>=', now()->subWeek()->startOfWeek())
            ->where('created_at', '<', now()->startOfWeek())
            ->count();

        $verified = User::whereNotNull('email_verified_at')->count();
        $verificationRate = $totalUsers > 0 ? (int) round(100 * $verified / $totalUsers) : 0;

        $pendingJobs = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->count();

        $activeUsers = (int) DB::table('sessions')
            ->where('last_activity', '>=', now()->subDay()->getTimestamp())
            ->whereNotNull('user_id')
            ->distinct()
            ->count('user_id');

        $last7Days = collect(range(6, 0))
            ->map(fn (int $d) => User::whereDate('created_at', now()->subDays($d)->toDateString())->count())
            ->reverse()
            ->values()
            ->all();

        $delta = $thisWeek - $lastWeek;
        if ($lastWeek > 0) {
            $weekDescription = ($delta >= 0 ? '+' : '') . $delta . ' vs last week';
            $weekTrend = $delta >= 0 ? 'success' : 'danger';
            $weekIcon = $delta >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
        } else {
            $weekDescription = $thisWeek > 0 ? 'New activity this week' : 'No sign-ups this week';
            $weekTrend = $thisWeek > 0 ? 'success' : 'gray';
            $weekIcon = $thisWeek > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-minus-small';
        }

        return [
            Stat::make('Total users', number_format($totalUsers))
                ->description('All registered accounts')
                ->descriptionIcon('heroicon-m-users')
                ->chart($last7Days)
                ->color('primary'),

            Stat::make('New this week', number_format($thisWeek))
                ->description($weekDescription)
                ->descriptionIcon($weekIcon)
                ->descriptionColor($weekTrend)
                ->color('success'),

            Stat::make('Verified email', number_format($verified).' ('.$verificationRate.'%)')
                ->description('Users who verified their email')
                ->descriptionIcon('heroicon-m-envelope-open')
                ->color('info'),

            Stat::make('Active users (24h)', number_format($activeUsers))
                ->description('Distinct accounts with a recent session')
                ->descriptionIcon('heroicon-m-signal')
                ->color('warning'),

            Stat::make('Queue depth', number_format($pendingJobs))
                ->description('Jobs waiting to run')
                ->descriptionIcon('heroicon-m-queue-list')
                ->color($pendingJobs > 50 ? 'danger' : 'gray'),

            Stat::make('Failed jobs', number_format($failedJobs))
                ->description('Jobs that exhausted retries')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->descriptionColor($failedJobs > 0 ? 'danger' : 'success')
                ->color($failedJobs > 0 ? 'danger' : 'success'),
        ];
    }
}
