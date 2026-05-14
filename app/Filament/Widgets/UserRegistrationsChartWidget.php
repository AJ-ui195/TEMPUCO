<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;

class UserRegistrationsChartWidget extends ChartWidget
{
    protected static ?int $sort = -2;

    protected ?string $heading = 'User registrations';

    protected ?string $description = 'New accounts per day over the last 30 days';

    protected int | string | array $columnSpan = 'full';

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
            $counts[] = User::whereDate('created_at', $day)->count();
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Registrations',
                    'data' => $counts,
                ],
            ],
        ];
    }
}
