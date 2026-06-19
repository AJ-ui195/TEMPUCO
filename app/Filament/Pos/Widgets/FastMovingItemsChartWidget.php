<?php

namespace App\Filament\Pos\Widgets;

use App\Support\FastMovingItemsReport;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FastMovingItemsChartWidget extends ChartWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '22rem';

    protected string $color = 'info';

    protected string $view = 'filament.pos.widgets.fast-moving-items-chart';

    public static function canView(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'inventory';
    }

    public function getHeading(): ?string
    {
        return __('Fast moving items');
    }

    public function getDescription(): ?string
    {
        $report = new FastMovingItemsReport;

        return __('Top 10 products by quantity sold — :period.', [
            'period' => $report->periodLabel(),
        ]);
    }

    /**
     * @return Collection<int, array{rank: int, name: string, sku: ?string, quantity_sold: int, revenue: float}>
     */
    public function getTopItems(): Collection
    {
        return (new FastMovingItemsReport)->top(10);
    }

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function getOptions(): ?array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                    'align' => 'end',
                    'labels' => [
                        'usePointStyle' => true,
                        'pointStyle' => 'circle',
                        'padding' => 18,
                        'font' => [
                            'size' => 12,
                            'weight' => '600',
                        ],
                    ],
                ],
                'tooltip' => [
                    'backgroundColor' => 'rgba(15, 23, 42, 0.94)',
                    'titleFont' => ['size' => 13, 'weight' => 'bold'],
                    'bodyFont' => ['size' => 12],
                    'padding' => 12,
                    'cornerRadius' => 8,
                    'displayColors' => true,
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                    'ticks' => [
                        'maxRotation' => 40,
                        'minRotation' => 0,
                        'font' => ['size' => 11],
                    ],
                ],
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => __('Quantity sold'),
                        'font' => ['size' => 11, 'weight' => '600'],
                    ],
                    'grid' => [
                        'color' => 'rgba(148, 163, 184, 0.22)',
                    ],
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => __('Revenue (₱)'),
                        'font' => ['size' => 11, 'weight' => '600'],
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $items = $this->getTopItems();

        if ($items->isEmpty()) {
            return [
                'labels' => [__('No sales yet')],
                'datasets' => [
                    [
                        'label' => __('Quantity sold'),
                        'data' => [0],
                        'borderColor' => 'rgba(148, 163, 184, 0.6)',
                        'backgroundColor' => 'rgba(148, 163, 184, 0.1)',
                        'fill' => true,
                        'tension' => 0.35,
                    ],
                ],
            ];
        }

        return [
            'labels' => $items
                ->map(fn (array $item): string => '#'.$item['rank'].' · '.Str::limit($item['name'], 20))
                ->all(),
            'datasets' => [
                [
                    'label' => __('Quantity sold'),
                    'data' => $items
                        ->map(fn (array $item): int => $item['quantity_sold'])
                        ->all(),
                    'yAxisID' => 'y',
                    'fill' => true,
                    'backgroundColor' => 'rgba(14, 165, 233, 0.18)',
                    'borderColor' => 'rgb(2, 132, 199)',
                    'borderWidth' => 3,
                    'pointBackgroundColor' => 'rgb(2, 132, 199)',
                    'pointBorderColor' => '#ffffff',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 5,
                    'pointHoverRadius' => 7,
                    'tension' => 0.38,
                ],
                [
                    'label' => __('Revenue (₱)'),
                    'data' => $items
                        ->map(fn (array $item): float => $item['revenue'])
                        ->all(),
                    'yAxisID' => 'y1',
                    'fill' => true,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'borderWidth' => 2,
                    'borderDash' => [6, 4],
                    'pointBackgroundColor' => 'rgb(16, 185, 129)',
                    'pointBorderColor' => '#ffffff',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 4,
                    'pointHoverRadius' => 6,
                    'tension' => 0.38,
                ],
            ],
        ];
    }
}
