<x-filament-panels::page>
    @include('filament.cashier.partials.pos-ui-styles')

    @php
        $report = $this->report();
        $summary = $this->getSummary();
        $orRows = $report->officialReceiptRows();
        $inRows = $report->invoiceSheetRows();
        $orTotals = $report->officialReceiptTotals();
        $inTotals = $report->invoiceSheetTotals();
        $years = range(now()->year, now()->year - 5);
        $months = collect(range(1, 12))->mapWithKeys(fn (int $m): array => [
            $m => \Carbon\Carbon::createFromDate($this->year, $m, 1)->format('F'),
        ]);
        $showOr = $report->includesOfficialReceipts();
        $showIn = $report->includesInvoices();
        $coop = 'DIGOS CITY NATIONAL HIGH SCHOOL TEACHERS AND EMPLOYEES MULTI-PURPOSE COOPERATIVE (DICNHS TEMPUCO)';
        $amt = static fn (float $value): string => $value >= 0.005 ? number_format($value, 2) : '';

        $orLabels = [
            'character_short_term' => __('Character Short-term'),
            'character' => __('Character'),
            'salary_loan' => __('Salary loan'),
            'share_capital' => __('Share Capital'),
            'insurance' => __('Insurance'),
            'quick_loan' => __('Quick Loan'),
            'others' => __('Others'),
        ];

        $screenRows = collect();

        foreach ($orRows as $row) {
            $parts = [];
            foreach ($orLabels as $key => $label) {
                if (($row[$key] ?? 0) >= 0.005) {
                    $parts[] = $label;
                }
            }

            $screenRows->push([
                'date' => $row['date'],
                'reference' => $row['or_no'],
                'receipt_kind' => __('O.R.'),
                'source' => implode(' · ', $parts) ?: __('Loan'),
                'particulars' => '',
                'member' => $row['name'],
                'amount' => $row['total'],
            ]);
        }

        foreach ($inRows as $row) {
            $parts = [];
            foreach ([
                __('Interest') => $row['interest'],
                __('Surcharge') => $row['surcharge'],
                __('Membership fee') => $row['membership_fee'],
                __('Others') => $row['others'],
            ] as $label => $value) {
                if ($value >= 0.005) {
                    $parts[] = $label.' ₱'.number_format($value, 2);
                }
            }

            $screenRows->push([
                'date' => $row['date'],
                'reference' => $row['or_no'],
                'receipt_kind' => __('Invoice'),
                'source' => __('Invoice fees'),
                'particulars' => implode(' · ', $parts),
                'member' => $row['name'],
                'amount' => $row['total'],
            ]);
        }

        $screenRows = $screenRows->sortBy([
            ['date', 'asc'],
            ['reference', 'asc'],
        ])->values();
    @endphp

    <div class="fi-pos-ui cr-screen">
        <div class="pos-panel" style="padding: 1rem 1.25rem; margin-bottom: 1rem;">
            <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; justify-content: space-between;">
                <div>
                    <h2 style="margin: 0; font-size: 1.125rem; font-weight: 700;">{{ __('Generate Collection report') }}</h2>
                    <p class="pos-muted" style="margin: 0.25rem 0 0; font-size: 0.8125rem;">
                        {{ $report->periodLabel() }} · {{ $report->receiptKindLabel() }}
                    </p>
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                    <button type="button" onclick="window.print()" class="pos-btn-secondary">{{ __('Print') }}</button>
                    <button type="button" wire:click="exportCsv" class="pos-btn-primary">{{ __('Download CSV') }}</button>
                </div>
            </div>

            <div class="collection-report-filters">
                <div class="collection-report-filter">
                    <div class="collection-report-filter-label">{{ __('Period') }}</div>
                    <div class="collection-report-filter-buttons">
                        <button type="button" wire:click="$set('period', '{{ \App\Filament\CollectionCashier\Pages\GenerateCollectionReport::PERIOD_TODAY }}')" @class(['pos-period-btn', 'pos-period-btn--active' => $period === \App\Filament\CollectionCashier\Pages\GenerateCollectionReport::PERIOD_TODAY])>{{ __('Today') }}</button>
                        <button type="button" wire:click="$set('period', '{{ \App\Filament\CollectionCashier\Pages\GenerateCollectionReport::PERIOD_MONTH }}')" @class(['pos-period-btn', 'pos-period-btn--active' => $period === \App\Filament\CollectionCashier\Pages\GenerateCollectionReport::PERIOD_MONTH])>{{ __('Per month') }}</button>
                        <button type="button" wire:click="$set('period', '{{ \App\Filament\CollectionCashier\Pages\GenerateCollectionReport::PERIOD_YEAR }}')" @class(['pos-period-btn', 'pos-period-btn--active' => $period === \App\Filament\CollectionCashier\Pages\GenerateCollectionReport::PERIOD_YEAR])>{{ __('Per year') }}</button>
                        <button type="button" wire:click="$set('period', '{{ \App\Filament\CollectionCashier\Pages\GenerateCollectionReport::PERIOD_CUSTOM }}')" @class(['pos-period-btn', 'pos-period-btn--active' => $period === \App\Filament\CollectionCashier\Pages\GenerateCollectionReport::PERIOD_CUSTOM])>{{ __('Select dates') }}</button>
                    </div>
                </div>

                <div class="collection-report-filter">
                    <div class="collection-report-filter-label">{{ __('Receipt') }}</div>
                    <div class="collection-report-seg" role="group" aria-label="{{ __('Receipt') }}">
                        <button type="button" wire:click="$set('receiptKind', '{{ \App\Filament\CollectionCashier\Pages\GenerateCollectionReport::KIND_ALL }}')" @class(['collection-report-seg-btn', 'is-active' => $receiptKind === \App\Filament\CollectionCashier\Pages\GenerateCollectionReport::KIND_ALL])>{{ __('All') }}</button>
                        <button type="button" wire:click="$set('receiptKind', '{{ \App\Filament\CollectionCashier\Pages\GenerateCollectionReport::KIND_OR }}')" @class(['collection-report-seg-btn', 'is-active' => $receiptKind === \App\Filament\CollectionCashier\Pages\GenerateCollectionReport::KIND_OR])>{{ __('O.R.') }}</button>
                        <button type="button" wire:click="$set('receiptKind', '{{ \App\Filament\CollectionCashier\Pages\GenerateCollectionReport::KIND_IN }}')" @class(['collection-report-seg-btn', 'is-active' => $receiptKind === \App\Filament\CollectionCashier\Pages\GenerateCollectionReport::KIND_IN])>{{ __('Invoice') }}</button>
                    </div>
                </div>
            </div>

            @if ($period === \App\Filament\CollectionCashier\Pages\GenerateCollectionReport::PERIOD_MONTH)
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

            @if ($period === \App\Filament\CollectionCashier\Pages\GenerateCollectionReport::PERIOD_YEAR)
                <div style="max-width: 10rem; margin-top: 1rem;">
                    <label class="pos-muted" style="display: block; font-size: 0.75rem; font-weight: 600; margin-bottom: 0.25rem;">{{ __('Year') }}</label>
                    <select wire:model.live="year" class="pos-select">
                        @foreach ($years as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if ($period === \App\Filament\CollectionCashier\Pages\GenerateCollectionReport::PERIOD_CUSTOM)
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(11rem, 12rem)); gap: 0.75rem; margin-top: 1rem;">
                    <div>
                        <label for="collection-report-from-date" class="pos-muted" style="display: block; font-size: 0.75rem; font-weight: 600; margin-bottom: 0.25rem;">{{ __('From date') }}</label>
                        <input id="collection-report-from-date" type="date" wire:model.live="fromDate" max="{{ $toDate }}" class="pos-input" />
                    </div>
                    <div>
                        <label for="collection-report-to-date" class="pos-muted" style="display: block; font-size: 0.75rem; font-weight: 600; margin-bottom: 0.25rem;">{{ __('To date') }}</label>
                        <input id="collection-report-to-date" type="date" wire:model.live="toDate" min="{{ $fromDate }}" class="pos-input" />
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
                <div class="pos-muted pos-stat-label">{{ __('O.R. collections') }}</div>
                <div class="pos-stat-value">₱{{ number_format($orTotals['total'], 2) }}</div>
            </div>
            <div class="pos-panel pos-stat">
                <div class="pos-muted pos-stat-label">{{ __('Invoice collections') }}</div>
                <div class="pos-stat-value">₱{{ number_format($inTotals['total'], 2) }}</div>
            </div>
            <div class="pos-panel pos-stat">
                <div class="pos-muted pos-stat-label">{{ __('Grand total') }}</div>
                <div class="pos-stat-value">₱{{ number_format($summary['total'], 2) }}</div>
            </div>
        </div>

        <div class="pos-panel" style="padding: 0; overflow: hidden;">
            @if ($screenRows->isEmpty())
                <div style="padding: 2.5rem 1.5rem; text-align: center;">
                    <p class="pos-muted" style="margin: 0; font-size: 0.9375rem;">{{ __('No collections in this period.') }}</p>
                </div>
            @else
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.8125rem;">
                        <thead>
                            <tr style="text-align: left; border-bottom: 1px solid rgb(203 213 225);">
                                <th style="padding: 0.75rem;">{{ __('Date') }}</th>
                                <th style="padding: 0.75rem;">{{ __('Reference') }}</th>
                                <th style="padding: 0.75rem;">{{ __('Kind') }}</th>
                                <th style="padding: 0.75rem;">{{ __('Source') }}</th>
                                <th style="padding: 0.75rem;">{{ __('Particulars') }}</th>
                                <th style="padding: 0.75rem;">{{ __('Member') }}</th>
                                <th style="padding: 0.75rem; text-align: end;">{{ __('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($screenRows as $row)
                                <tr style="border-bottom: 1px solid rgb(226 232 240);">
                                    <td style="padding: 0.5rem 0.75rem; white-space: nowrap;">
                                        {{ \App\Support\PhilippineTime::format($row['date'], 'M j, Y g:i A') }}
                                    </td>
                                    <td style="padding: 0.5rem 0.75rem; font-weight: 600;">{{ $row['reference'] ?: '—' }}</td>
                                    <td style="padding: 0.5rem 0.75rem;">{{ $row['receipt_kind'] }}</td>
                                    <td style="padding: 0.5rem 0.75rem;">{{ $row['source'] }}</td>
                                    <td style="padding: 0.5rem 0.75rem;">{{ $row['particulars'] ?: '—' }}</td>
                                    <td style="padding: 0.5rem 0.75rem;">{{ $row['member'] }}</td>
                                    <td style="padding: 0.5rem 0.75rem; text-align: end; font-weight: 600;">
                                        ₱{{ number_format($row['amount'], 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="border-top: 2px solid rgb(203 213 225); font-weight: 700;">
                                <td colspan="6" style="padding: 0.75rem; text-align: end;">{{ __('Grand total') }}</td>
                                <td style="padding: 0.75rem; text-align: end;">₱{{ number_format($summary['total'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div id="collection-report-print" class="cr-print-only">
        @if ($showOr)
            <section class="cr-sheet">
                <header class="cr-letterhead">
                    <img src="{{ asset('images/DICNHSLOGO1.png') }}" alt="TEMPUCO" class="cr-logo">
                    <div class="cr-coop">{{ $coop }}</div>
                    <div class="cr-city">{{ __('Digos City, Davao del Sur') }}</div>
                    <div class="cr-title">{{ __('CASHIER\'S COLLECTION REPORT') }}</div>
                    <div class="cr-dates">{{ $report->printedDateRange() }}</div>
                    <div class="cr-section">{{ __('O.R. COLLECTIONS') }}</div>
                </header>
                <table class="cr-table">
                    <thead>
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('OR #') }}</th>
                            <th>{{ __('Character Short-term') }}</th>
                            <th>{{ __('Character') }}</th>
                            <th>{{ __('Salary loan') }}</th>
                            <th>{{ __('Share Capital') }}</th>
                            <th>{{ __('Insurance') }}</th>
                            <th>{{ __('Quick Loan') }}</th>
                            <th>{{ __('Others') }}</th>
                            <th>{{ __('Total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orRows as $row)
                            <tr>
                                <td>{{ \App\Support\PhilippineTime::format($row['date'], 'm/d/Y') }}</td>
                                <td class="cr-name">{{ $row['name'] }}</td>
                                <td>{{ $row['or_no'] ?: '—' }}</td>
                                <td class="cr-num">{{ $amt($row['character_short_term']) }}</td>
                                <td class="cr-num">{{ $amt($row['character']) }}</td>
                                <td class="cr-num">{{ $amt($row['salary_loan']) }}</td>
                                <td class="cr-num">{{ $amt($row['share_capital']) }}</td>
                                <td class="cr-num">{{ $amt($row['insurance']) }}</td>
                                <td class="cr-num">{{ $amt($row['quick_loan']) }}</td>
                                <td class="cr-num">{{ $amt($row['others']) }}</td>
                                <td class="cr-num">{{ number_format($row['total'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="cr-empty">{{ __('No O.R. collections in this period.') }}</td></tr>
                        @endforelse
                    </tbody>
                    @if ($orRows->isNotEmpty())
                        <tfoot>
                            <tr>
                                <td colspan="3">{{ __('Total') }}</td>
                                <td class="cr-num">{{ $amt($orTotals['character_short_term']) }}</td>
                                <td class="cr-num">{{ $amt($orTotals['character']) }}</td>
                                <td class="cr-num">{{ $amt($orTotals['salary_loan']) }}</td>
                                <td class="cr-num">{{ $amt($orTotals['share_capital']) }}</td>
                                <td class="cr-num">{{ $amt($orTotals['insurance']) }}</td>
                                <td class="cr-num">{{ $amt($orTotals['quick_loan']) }}</td>
                                <td class="cr-num">{{ $amt($orTotals['others']) }}</td>
                                <td class="cr-num">{{ number_format($orTotals['total'], 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </section>
        @endif

        @if ($showIn)
            <section class="cr-sheet">
                <header class="cr-letterhead">
                    <img src="{{ asset('images/DICNHSLOGO1.png') }}" alt="TEMPUCO" class="cr-logo">
                    <div class="cr-coop">{{ $coop }}</div>
                    <div class="cr-city">{{ __('Digos City, Davao del Sur') }}</div>
                    <div class="cr-title">{{ __('CASHIER\'S COLLECTION REPORT') }}</div>
                    <div class="cr-dates">{{ $report->printedDateRange() }}</div>
                    <div class="cr-section">{{ __('INVOICE COLLECTIONS') }}</div>
                </header>
                <table class="cr-table">
                    <thead>
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('OR #') }}</th>
                            <th>{{ __('Interest') }}</th>
                            <th>{{ __('Surcharge') }}</th>
                            <th>{{ __('Membership fee') }}</th>
                            <th>{{ __('Others') }}</th>
                            <th>{{ __('Total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($inRows as $row)
                            <tr>
                                <td>{{ \App\Support\PhilippineTime::format($row['date'], 'm/d/Y') }}</td>
                                <td class="cr-name">{{ $row['name'] }}</td>
                                <td>{{ $row['or_no'] ?: '—' }}</td>
                                <td class="cr-num">{{ $amt($row['interest']) }}</td>
                                <td class="cr-num">{{ $amt($row['surcharge']) }}</td>
                                <td class="cr-num">{{ $amt($row['membership_fee']) }}</td>
                                <td class="cr-num">{{ $amt($row['others']) }}</td>
                                <td class="cr-num">{{ number_format($row['total'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="cr-empty">{{ __('No invoice collections in this period.') }}</td></tr>
                        @endforelse
                    </tbody>
                    @if ($inRows->isNotEmpty())
                        <tfoot>
                            <tr>
                                <td colspan="3">{{ __('Total') }}</td>
                                <td class="cr-num">{{ $amt($inTotals['interest']) }}</td>
                                <td class="cr-num">{{ $amt($inTotals['surcharge']) }}</td>
                                <td class="cr-num">{{ $amt($inTotals['membership_fee']) }}</td>
                                <td class="cr-num">{{ $amt($inTotals['others']) }}</td>
                                <td class="cr-num">{{ number_format($inTotals['total'], 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </section>
        @endif
    </div>

    <style>
        .collection-report-filters {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 1rem 1.5rem;
            align-items: end;
            margin-top: 1.25rem;
            padding-top: 1rem;
            border-top: 1px solid rgb(226 232 240);
        }

        .dark .collection-report-filters { border-top-color: rgb(55 65 81); }

        .collection-report-filter-label {
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: rgb(100 116 139);
            margin-bottom: 0.5rem;
        }

        .dark .collection-report-filter-label { color: rgb(148 163 184); }

        .collection-report-filter-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .collection-report-seg {
            display: inline-flex;
            overflow: hidden;
            border-radius: 0.5rem;
            border: 1px solid rgb(203 213 225);
            background: rgb(241 245 249);
        }

        .dark .collection-report-seg {
            border-color: rgb(75 85 99);
            background: rgb(17 24 39);
        }

        .collection-report-seg-btn {
            margin: 0;
            padding: 0.5rem 1.1rem;
            font-size: 0.8125rem;
            font-weight: 700;
            border: 0;
            border-right: 1px solid rgb(203 213 225);
            background: transparent;
            color: rgb(71 85 105);
            cursor: pointer;
            white-space: nowrap;
        }

        .collection-report-seg-btn:last-child { border-right: 0; }

        .dark .collection-report-seg-btn {
            border-right-color: rgb(75 85 99);
            color: rgb(203 213 225);
        }

        .collection-report-seg-btn.is-active {
            background: rgb(2 132 199);
            color: #fff;
        }

        .dark .collection-report-seg-btn.is-active {
            background: rgb(14 165 233);
            color: #fff;
        }

        .cr-print-only { display: none; }

        .cr-sheet {
            background: #fff;
            color: #000;
            padding: 0;
        }

        .cr-letterhead { text-align: center; margin-bottom: 0.75rem; }

        .cr-logo {
            width: 56px;
            height: 56px;
            object-fit: contain;
            margin: 0 auto 0.35rem;
            display: block;
        }

        .cr-coop {
            font-size: 0.72rem;
            font-weight: 700;
            line-height: 1.35;
            max-width: 46rem;
            margin: 0 auto;
        }

        .cr-city { font-size: 0.8rem; margin-top: 0.15rem; }

        .cr-title {
            font-size: 0.95rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            margin-top: 0.45rem;
        }

        .cr-dates { font-size: 0.8rem; font-weight: 700; margin-top: 0.15rem; }

        .cr-section {
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            margin-top: 0.55rem;
            border: 1px solid #000;
            padding: 0.3rem 0.5rem;
        }

        .cr-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.7rem;
            color: #000;
        }

        .cr-table th,
        .cr-table td {
            border: 1px solid #000;
            padding: 0.28rem 0.35rem;
            vertical-align: middle;
        }

        .cr-table th { font-weight: 700; text-align: center; background: #fff; }
        .cr-table .cr-name { text-align: left; }
        .cr-table .cr-num { text-align: right; white-space: nowrap; }
        .cr-table .cr-empty { text-align: center; padding: 1.25rem; }
        .cr-table tfoot td { font-weight: 700; }

        @media (max-width: 48rem) {
            .collection-report-filters { grid-template-columns: 1fr; }
        }

        @media print {
            @page {
                size: landscape;
                margin: 10mm;
            }

            body * { visibility: hidden; }

            #collection-report-print,
            #collection-report-print * { visibility: visible; }

            #collection-report-print {
                display: block;
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }

            .cr-screen { display: none !important; visibility: hidden !important; }

            .cr-sheet {
                page-break-after: always;
                break-after: page;
            }

            .cr-sheet:last-child {
                page-break-after: auto;
                break-after: auto;
            }
        }
    </style>
</x-filament-panels::page>
