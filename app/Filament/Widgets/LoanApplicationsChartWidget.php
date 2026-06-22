<?php

namespace App\Filament\Widgets;

use App\Models\Loan;
use Filament\Widgets\ChartWidget;

class LoanApplicationsChartWidget extends ChartWidget
{
    protected static ?int $sort = -1;

    protected int | string | array $columnSpan = 'full';

    public function getHeading(): ?string
    {
        return __('Loan applications');
    }

    public function getDescription(): ?string
    {
        return __('Member loan applications per day over the last 30 days');
    }

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $labels = [];
        $counts = [];

        for ($i = 29; $i >= 0; $i--) {
            $day = now()->subDays($i)->startOfDay();
            $labels[] = $day->format('M j');
            $counts[] = Loan::query()
                ->whereHas('user', fn ($query) => $query->members())
                ->whereLoanDate($day)
                ->count();
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => __('Applications'),
                    'data' => $counts,
                ],
            ],
        ];
    }
}
