<x-filament-panels::page>
    @include('filament.cashier.partials.pos-ui-styles')

    @php
        $report = $this->report();
        $summary = $this->getSummary();
        $sales = $report->sales();
        $years = range(now()->year, now()->year - 5);
        $months = collect(range(1, 12))->mapWithKeys(fn (int $m): array => [
            $m => \Carbon\Carbon::createFromDate($this->year, $m, 1)->format('F'),
        ]);
    @endphp

    <div class="fi-pos-ui" id="pos-sales-report-print">
        <div class="pos-panel" style="padding: 1rem 1.25rem; margin-bottom: 1rem;">
            <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; justify-content: space-between;">
                <div>
                    <h2 style="margin: 0; font-size: 1.125rem; font-weight: 700;">{{ __('Sales report') }}</h2>
                    <p class="pos-muted" style="margin: 0.25rem 0 0; font-size: 0.8125rem;">
                        {{ $report->periodLabel() }}
                    </p>
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                    <button
                        type="button"
                        onclick="window.print()"
                        class="pos-btn-secondary"
                    >
                        {{ __('Print') }}
                    </button>
                    <button
                        type="button"
                        wire:click="exportCsv"
                        class="pos-btn-primary"
                    >
                        {{ __('Download CSV') }}
                    </button>
                </div>
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 1rem;">
                <button
                    type="button"
                    wire:click="$set('period', '{{ \App\Filament\Cashier\Pages\PosSalesReportPage::PERIOD_TODAY }}')"
                    @class(['pos-period-btn', 'pos-period-btn--active' => $period === \App\Filament\Cashier\Pages\PosSalesReportPage::PERIOD_TODAY])
                >
                    {{ __('Today') }}
                </button>
                <button
                    type="button"
                    wire:click="$set('period', '{{ \App\Filament\Cashier\Pages\PosSalesReportPage::PERIOD_MONTH }}')"
                    @class(['pos-period-btn', 'pos-period-btn--active' => $period === \App\Filament\Cashier\Pages\PosSalesReportPage::PERIOD_MONTH])
                >
                    {{ __('Per month') }}
                </button>
                <button
                    type="button"
                    wire:click="$set('period', '{{ \App\Filament\Cashier\Pages\PosSalesReportPage::PERIOD_YEAR }}')"
                    @class(['pos-period-btn', 'pos-period-btn--active' => $period === \App\Filament\Cashier\Pages\PosSalesReportPage::PERIOD_YEAR])
                >
                    {{ __('Per year') }}
                </button>
            </div>

            @if ($period === \App\Filament\Cashier\Pages\PosSalesReportPage::PERIOD_MONTH)
                <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 10rem)); gap: 0.75rem; margin-top: 1rem;">
                    <div>
                        <label class="pos-muted" style="display: block; font-size: 0.75rem; font-weight: 600; margin-bottom: 0.25rem;">{{ __('Year') }}</label>
                        <select wire:model.live="year" class="pos-select">
                            @foreach ($years as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="pos-muted" style="display: block; font-size: 0.75rem; font-weight: 600; margin-bottom: 0.25rem;">{{ __('Month') }}</label>
                        <select wire:model.live="month" class="pos-select">
                            @foreach ($months as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif

            @if ($period === \App\Filament\Cashier\Pages\PosSalesReportPage::PERIOD_YEAR)
                <div style="max-width: 10rem; margin-top: 1rem;">
                    <label class="pos-muted" style="display: block; font-size: 0.75rem; font-weight: 600; margin-bottom: 0.25rem;">{{ __('Year') }}</label>
                    <select wire:model.live="year" class="pos-select">
                        @foreach ($years as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr)); gap: 1rem; margin-bottom: 1rem;">
            <div class="pos-panel pos-stat">
                <div class="pos-muted pos-stat-label">{{ __('Transactions') }}</div>
                <div class="pos-stat-value">{{ number_format($summary['transaction_count']) }}</div>
            </div>
            <div class="pos-panel pos-stat">
                <div class="pos-muted pos-stat-label">{{ __('Total sales') }}</div>
                <div class="pos-stat-value">₱{{ number_format($summary['total_revenue'], 2) }}</div>
            </div>
            <div class="pos-panel pos-stat">
                <div class="pos-muted pos-stat-label">{{ __('Items sold') }}</div>
                <div class="pos-stat-value">{{ number_format($summary['items_sold']) }}</div>
            </div>
        </div>

        <div class="pos-panel" style="padding: 0; overflow: hidden;">
            <div class="pos-cart-header" style="padding: 0.75rem 1rem;">
                <span style="font-weight: 700; font-size: 0.9375rem;">{{ __('Transactions') }}</span>
            </div>

            @if ($sales->isEmpty())
                <p class="pos-muted" style="margin: 0; padding: 2rem 1rem; text-align: center; font-size: 0.875rem;">
                    {{ __('No sales found for this period.') }}
                </p>
            @else
                <div style="overflow-x: auto;">
                    <table class="pos-table" style="width: 100%; border-collapse: collapse; font-size: 0.8125rem;">
                        <thead>
                            <tr style="text-align: left;">
                                <th style="padding: 0.5rem 0.75rem;">{{ __('Reference') }}</th>
                                <th style="padding: 0.5rem 0.75rem;">{{ __('Date & time') }}</th>
                                <th style="padding: 0.5rem 0.75rem;">{{ __('Cashier') }}</th>
                                <th style="padding: 0.5rem 0.75rem; text-align: end;">{{ __('Items') }}</th>
                                <th style="padding: 0.5rem 0.75rem; text-align: end;">{{ __('Total') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sales as $sale)
                                <tr>
                                    <td style="padding: 0.5rem 0.75rem; font-weight: 600;">{{ $sale->reference }}</td>
                                    <td class="pos-muted" style="padding: 0.5rem 0.75rem;">
                                        {{ $sale->created_at?->format('M j, Y g:i A') }}
                                    </td>
                                    <td style="padding: 0.5rem 0.75rem;">{{ $sale->user?->name ?? '—' }}</td>
                                    <td style="padding: 0.5rem 0.75rem; text-align: end;">{{ $sale->items->sum('quantity') }}</td>
                                    <td style="padding: 0.5rem 0.75rem; text-align: end; font-weight: 600;">
                                        ₱{{ number_format((float) $sale->total, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="border-top: 2px solid rgb(203 213 225); font-weight: 700;">
                                <td colspan="4" style="padding: 0.75rem; text-align: end;">{{ __('Grand total') }}</td>
                                <td style="padding: 0.75rem; text-align: end;">₱{{ number_format($summary['total_revenue'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <style>
        @media print {
            body * {
                visibility: hidden;
            }

            #pos-sales-report-print,
            #pos-sales-report-print * {
                visibility: visible;
            }

            #pos-sales-report-print {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
        }
    </style>
</x-filament-panels::page>
