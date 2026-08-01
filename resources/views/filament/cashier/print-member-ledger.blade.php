<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $ledgers->count() === 1 ? __('Member ledger') . ' — ' . $ledgers->first()['member']->name : __('Member ledgers') }}</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 1.5rem;
            background: #f3f4f6;
            color: #111827;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            font-size: 12px;
        }

        /* One statement per member, each printed on its own page. */
        .statement {
            max-width: 8.5in;
            margin: 0 auto 1.5rem;
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 1.5rem;
        }

        .doc-head {
            display: flex;
            align-items: center;
            gap: 1rem;
            text-align: center;
            border-bottom: 2px solid #111827;
            padding-bottom: 0.75rem;
            margin-bottom: 1rem;
        }

        .doc-logo, .doc-head-spacer { flex: 0 0 72px; }
        .doc-logo img { display: block; width: 72px; height: auto; }
        .doc-head-text { flex: 1; }
        .doc-brand { font-size: 15px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
        .doc-org { font-size: 11px; color: #4b5563; margin-top: 0.125rem; }
        .doc-title { font-size: 13px; font-weight: 700; margin-top: 0.5rem; }
        .doc-meta { font-size: 11px; color: #4b5563; margin-top: 0.25rem; }

        .member-name { font-size: 13px; font-weight: 700; }
        .member-contact { font-size: 11px; color: #4b5563; margin-top: 0.125rem; }
        .page-count { font-size: 11px; color: #6b7280; margin-top: 0.25rem; }

        table { width: 100%; border-collapse: collapse; margin-top: 0.625rem; }
        th, td { padding: 0.375rem 0.5rem; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        thead th { background: #f9fafb; font-size: 11px; text-transform: uppercase; letter-spacing: 0.03em; text-align: left; border-bottom: 1px solid #9ca3af; }
        .num { text-align: right; white-space: nowrap; }
        .muted { color: #6b7280; }
        .ref { display: block; font-size: 10px; color: #6b7280; }
        tfoot td { border-top: 2px solid #111827; border-bottom: none; font-weight: 700; }

        .empty { padding: 0.75rem 0.5rem; font-size: 11px; color: #6b7280; }

        .signature { margin-top: 2.5rem; display: flex; justify-content: space-between; gap: 2rem; font-size: 11px; }
        .signature div { flex: 1; border-top: 1px solid #111827; padding-top: 0.25rem; text-align: center; color: #4b5563; }

        .actions { display: flex; gap: 0.5rem; justify-content: center; margin-top: 1rem; }

        button {
            font: inherit;
            cursor: pointer;
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            border: 1px solid transparent;
        }

        .btn-primary { color: #fff; background: #0284c7; }
        .btn-secondary { color: #374151; background: #fff; border-color: #d1d5db; }

        @media print {
            body { padding: 0; background: #fff; }
            .doc-logo img { print-color-adjust: exact; -webkit-print-color-adjust: exact; }

            .statement {
                border: none;
                border-radius: 0;
                padding: 0;
                margin: 0;
                max-width: none;
            }

            .statement + .statement { break-before: page; }

            .actions { display: none; }
            thead { display: table-header-group; }
        }
    </style>
</head>
<body>
    @if ($ledgers->count() > 1)
        <section class="statement">
            @include('filament.cashier.partials.ledger-print-header', ['title' => __('Member credit ledgers — summary')])

            <div class="member-name">{{ __('Outstanding balances') }}</div>
            <div class="page-count">
                {{ trans_choice(':count member statement follows|:count member statements follow', $ledgers->count(), ['count' => $ledgers->count()]) }}
            </div>

            <table>
                <thead>
                    <tr>
                        <th>{{ __('Member') }}</th>
                        <th class="num">{{ __('Charged') }}</th>
                        <th class="num">{{ __('Paid') }}</th>
                        <th class="num">{{ __('Balance') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ledgers as $ledger)
                        <tr>
                            <td>{{ $ledger['member']->name }}</td>
                            <td class="num">₱{{ number_format($ledger['summary']['charged'], 2) }}</td>
                            <td class="num">₱{{ number_format($ledger['summary']['paid'], 2) }}</td>
                            <td class="num">₱{{ number_format($ledger['summary']['balance'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td class="num">{{ __('Total outstanding') }}</td>
                        <td class="num">—</td>
                        <td class="num">—</td>
                        <td class="num">₱{{ number_format($grandBalance, 2) }}</td>
                    </tr>
                </tfoot>
            </table>

            <div class="signature">
                <div>{{ __('Prepared by') }}</div>
                <div>{{ __('Verified by') }}</div>
            </div>
        </section>
    @endif

    @forelse ($ledgers as $ledger)
        @php
            $ledgerMember = $ledger['member'];
            $entries = $ledger['entries'];
            $summary = $ledger['summary'];
        @endphp

        <section class="statement">
            @include('filament.cashier.partials.ledger-print-header', ['title' => __('Member credit ledger')])

            <div class="member-name">{{ $ledgerMember->name }}</div>
            <div class="member-contact">
                {{ $ledgerMember->email }}
                @if ($ledgerMember->cellphone)
                    · {{ $ledgerMember->cellphone }}
                @endif
            </div>

            @if ($entries->isEmpty() && $summary['opening'] == 0.0)
                <p class="empty">{{ __('No credit charges or payments in this period.') }}</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Details') }}</th>
                            <th>{{ __('Channel') }}</th>
                            <th class="num">{{ __('Charge') }}</th>
                            <th class="num">{{ __('Payment') }}</th>
                            <th class="num">{{ __('Balance') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($summary['opening'] != 0.0)
                            <tr>
                                <td class="muted">—</td>
                                <td colspan="2">{{ __('Balance forward') }}</td>
                                <td class="num">—</td>
                                <td class="num">—</td>
                                <td class="num">₱{{ number_format($summary['opening'], 2) }}</td>
                            </tr>
                        @endif

                        @foreach ($entries as $entry)
                            <tr>
                                <td class="muted">{{ \App\Support\PhilippineTime::format($entry['date']) }}</td>
                                <td>
                                    {{ $entry['description'] }}
                                    <span class="ref">{{ $entry['reference'] }}</span>
                                </td>
                                <td>{{ $entry['channel']?->getLabel() ?? '—' }}</td>
                                <td class="num">{{ $entry['charge'] > 0 ? '₱'.number_format($entry['charge'], 2) : '—' }}</td>
                                <td class="num">{{ $entry['payment'] > 0 ? '₱'.number_format($entry['payment'], 2) : '—' }}</td>
                                <td class="num">₱{{ number_format($entry['balance'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="num">{{ __('Totals') }}</td>
                            <td class="num">₱{{ number_format($summary['charged'], 2) }}</td>
                            <td class="num">₱{{ number_format($summary['paid'], 2) }}</td>
                            <td class="num">₱{{ number_format($summary['balance'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            @endif

            <div class="signature">
                <div>{{ __('Prepared by') }}</div>
                <div>{{ __('Verified by') }}</div>
            </div>
        </section>
    @empty
        <section class="statement">
            @include('filament.cashier.partials.ledger-print-header', ['title' => __('Member credit ledgers')])

            <p class="empty">{{ __('No members have credit activity for the selected filters.') }}</p>
        </section>
    @endforelse

    <div class="actions">
        <button type="button" class="btn-primary" onclick="window.print()">{{ __('Print') }}</button>
        <button type="button" class="btn-secondary" onclick="window.close()">{{ __('Close') }}</button>
    </div>

    @if ($autoPrint ?? false)
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
