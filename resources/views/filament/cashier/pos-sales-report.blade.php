<x-filament-panels::page>
    @include('filament.cashier.partials.pos-ui-styles')

    @php
        $report = $this->report();
        $summary = $this->getSummary();
        $sales = $report->sales();
        $itemsSold = $this->getItemsSoldByProduct();
        $memberPurchases = $this->getMemberPurchases();
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
                <button
                    type="button"
                    wire:click="$set('period', '{{ \App\Filament\Cashier\Pages\PosSalesReportPage::PERIOD_CUSTOM }}')"
                    @class(['pos-period-btn', 'pos-period-btn--active' => $period === \App\Filament\Cashier\Pages\PosSalesReportPage::PERIOD_CUSTOM])
                >
                    {{ __('Select dates') }}
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

            @if ($period === \App\Filament\Cashier\Pages\PosSalesReportPage::PERIOD_CUSTOM)
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(11rem, 12rem)); gap: 0.75rem; margin-top: 1rem;">
                    <div>
                        <label for="report-from-date" class="pos-muted" style="display: block; font-size: 0.75rem; font-weight: 600; margin-bottom: 0.25rem;">{{ __('From date') }}</label>
                        <input
                            id="report-from-date"
                            type="date"
                            wire:model.live="fromDate"
                            max="{{ $toDate }}"
                            class="pos-input"
                        />
                    </div>
                    <div>
                        <label for="report-to-date" class="pos-muted" style="display: block; font-size: 0.75rem; font-weight: 600; margin-bottom: 0.25rem;">{{ __('To date') }}</label>
                        <input
                            id="report-to-date"
                            type="date"
                            wire:model.live="toDate"
                            min="{{ $fromDate }}"
                            class="pos-input"
                        />
                    </div>
                </div>
            @endif
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr)); gap: 1rem; margin-bottom: 1rem;">
            <div class="pos-panel pos-stat">
                <div class="pos-muted pos-stat-label">{{ __('Transactions') }}</div>
                <div class="pos-stat-value">{{ number_format($summary['transaction_count']) }}</div>
            </div>
            <div class="pos-panel pos-stat">
                <div class="pos-muted pos-stat-label">{{ __('Cash sales') }}</div>
                <div class="pos-stat-value">₱{{ number_format($summary['total_revenue'], 2) }}</div>
                <div class="pos-muted" style="font-size: 0.6875rem; margin-top: 0.25rem;">
                    {{ trans_choice(':count cash sale|:count cash sales', $summary['cash_transaction_count'], ['count' => $summary['cash_transaction_count']]) }}
                </div>
            </div>
            @if ($summary['credit_transaction_count'] > 0)
                <div class="pos-panel pos-stat">
                    <div class="pos-muted pos-stat-label">{{ __('Credit sales') }}</div>
                    <div class="pos-stat-value" style="color: rgb(4 120 87);">₱{{ number_format($summary['credit_sales_total'], 2) }}</div>
                    <div class="pos-muted" style="font-size: 0.6875rem; margin-top: 0.25rem;">
                        {{ trans_choice(':count credit sale|:count credit sales', $summary['credit_transaction_count'], ['count' => $summary['credit_transaction_count']]) }}
                        · {{ __('Not included in cash total') }}
                    </div>
                </div>
            @endif
            <div class="pos-panel pos-stat">
                <div class="pos-muted pos-stat-label">{{ __('Items sold') }}</div>
                <div class="pos-stat-value">{{ number_format($summary['items_sold']) }}</div>
            </div>
        </div>

        <div class="pos-panel" style="padding: 0; overflow: hidden; margin-bottom: 1rem;">
            <div class="pos-cart-header" style="padding: 0.75rem 1rem;">
                <span style="font-weight: 700; font-size: 0.9375rem;">{{ __('Items sold') }}</span>
                <span class="pos-muted" style="display: block; font-size: 0.6875rem; font-weight: 500; margin-top: 0.125rem;">
                    {{ __('Revenue column reflects cash sales only.') }}
                </span>
            </div>

            @if ($itemsSold->isEmpty())
                <p class="pos-muted" style="margin: 0; padding: 2rem 1rem; text-align: center; font-size: 0.875rem;">
                    {{ __('No items sold for this period.') }}
                </p>
            @else
                <div style="overflow-x: auto;">
                    <table class="pos-table" style="width: 100%; border-collapse: collapse; font-size: 0.8125rem;">
                        <thead>
                            <tr style="text-align: left;">
                                <th style="padding: 0.5rem 0.75rem;">{{ __('Product') }}</th>
                                <th style="padding: 0.5rem 0.75rem;">{{ __('SKU') }}</th>
                                <th style="padding: 0.5rem 0.75rem; text-align: end;">{{ __('Quantity sold') }}</th>
                                <th style="padding: 0.5rem 0.75rem; text-align: end;">{{ __('Revenue') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($itemsSold as $item)
                                <tr>
                                    <td style="padding: 0.5rem 0.75rem; font-weight: 600;">{{ $item['name'] }}</td>
                                    <td class="pos-muted" style="padding: 0.5rem 0.75rem;">{{ $item['sku'] ?? '—' }}</td>
                                    <td style="padding: 0.5rem 0.75rem; text-align: end;">{{ number_format($item['quantity_sold']) }}</td>
                                    <td style="padding: 0.5rem 0.75rem; text-align: end; font-weight: 600;">
                                        ₱{{ number_format($item['revenue'], 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="border-top: 2px solid rgb(203 213 225); font-weight: 700;">
                                <td colspan="2" style="padding: 0.75rem; text-align: end;">{{ __('Total') }}</td>
                                <td style="padding: 0.75rem; text-align: end;">{{ number_format($summary['items_sold']) }}</td>
                                <td style="padding: 0.75rem; text-align: end;">₱{{ number_format($summary['total_revenue'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>

        <div class="pos-panel" style="padding: 0; overflow: hidden; margin-bottom: 1rem;">
            <div class="pos-cart-header" style="padding: 0.75rem 1rem;">
                <span style="font-weight: 700; font-size: 0.9375rem;">{{ __('Member purchases') }}</span>
                <span class="pos-muted" style="display: block; font-size: 0.6875rem; font-weight: 500; margin-top: 0.125rem;">
                    {{ __('Total bought by each member who scanned their QR code or was selected during checkout — :period.', ['period' => $report->periodLabel()]) }}
                </span>
            </div>

            @if ($memberPurchases->isEmpty())
                <p class="pos-muted" style="margin: 0; padding: 2rem 1rem; text-align: center; font-size: 0.875rem;">
                    {{ __('No member purchases for this period.') }}
                </p>
            @else
                <div style="overflow-x: auto;">
                    <table class="pos-table" style="width: 100%; border-collapse: collapse; font-size: 0.8125rem;">
                        <thead>
                            <tr style="text-align: left;">
                                <th style="padding: 0.5rem 0.75rem;">{{ __('Member') }}</th>
                                <th style="padding: 0.5rem 0.75rem; text-align: end;">{{ __('Transactions') }}</th>
                                <th style="padding: 0.5rem 0.75rem; text-align: end;">{{ __('Total purchases') }}</th>
                                <th style="padding: 0.5rem 0.75rem; text-align: end;">{{ __('Outstanding') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($memberPurchases as $row)
                                <tr>
                                    <td style="padding: 0.5rem 0.75rem; font-weight: 600;">{{ $row['name'] }}</td>
                                    <td style="padding: 0.5rem 0.75rem; text-align: end;">{{ number_format($row['transaction_count']) }}</td>
                                    <td style="padding: 0.5rem 0.75rem; text-align: end; font-weight: 600;">
                                        ₱{{ number_format($row['total_spent'], 2) }}
                                    </td>
                                    <td style="padding: 0.5rem 0.75rem; text-align: end; color: {{ $row['outstanding'] > 0 ? 'rgb(4 120 87)' : 'inherit' }};">
                                        {{ $row['outstanding'] > 0 ? '₱'.number_format($row['outstanding'], 2) : '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="border-top: 2px solid rgb(203 213 225); font-weight: 700;">
                                <td colspan="2" style="padding: 0.75rem; text-align: end;">{{ __('Total') }}</td>
                                <td style="padding: 0.75rem; text-align: end;">₱{{ number_format($memberPurchases->sum('total_spent'), 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
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
                                <th style="padding: 0.5rem 0.75rem;">{{ __('Payment') }}</th>
                                <th style="padding: 0.5rem 0.75rem;">{{ __('Member') }}</th>
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
                                        {{ \App\Support\PhilippineTime::format($sale->created_at) }}
                                    </td>
                                    <td style="padding: 0.5rem 0.75rem;">
                                        @if ($sale->isCreditSale())
                                            <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: rgb(4 120 87); background: rgb(209 250 229);">
                                                {{ __('Credit') }}
                                            </span>
                                            @if ($sale->isUnsettled())
                                                <span class="pos-muted" style="display: block; margin-top: 0.25rem; font-size: 0.6875rem;">
                                                    {{ __('Outstanding') }}: ₱{{ number_format($sale->outstandingAmount(), 2) }}
                                                </span>
                                            @endif
                                        @else
                                            <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: rgb(3 105 161); background: rgb(224 242 254);">
                                                {{ __('Cash') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td style="padding: 0.5rem 0.75rem;">
                                        @if ($sale->memberName())
                                            <span style="font-weight: 600;">{{ $sale->memberName() }}</span>
                                        @else
                                            <span class="pos-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="pos-muted" style="padding: 0.5rem 0.75rem;">
                                        {{ $sale->cashierName() ?? '—' }}
                                    </td>
                                    <td style="padding: 0.5rem 0.75rem; text-align: end;">{{ $sale->items->sum('quantity') }}</td>
                                    <td style="padding: 0.5rem 0.75rem; text-align: end; font-weight: 600;">
                                        ₱{{ number_format((float) $sale->total, 2) }}
                                    </td>
                                </tr>
                                @foreach ($sale->items as $line)
                                    <tr class="pos-line-item">
                                        <td colspan="7" style="padding: 0.25rem 0.75rem 0.5rem 1.5rem; font-size: 0.75rem;">
                                            <span style="font-weight: 600;">{{ $line->productName() }}</span>
                                            <span class="pos-muted">
                                                · {{ $line->productSku() ?? __('No SKU') }}
                                                · {{ __('Qty') }}: {{ $line->quantity }}
                                                · ₱{{ number_format((float) $line->unit_price, 2) }}
                                                · {{ __('Line total') }}: ₱{{ number_format((float) $line->line_total, 2) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="border-top: 2px solid rgb(203 213 225); font-weight: 700;">
                                <td colspan="6" style="padding: 0.75rem; text-align: end;">{{ __('Cash grand total') }}</td>
                                <td style="padding: 0.75rem; text-align: end;">₱{{ number_format($report->cashSalesGrandTotal(), 2) }}</td>
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
