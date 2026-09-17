<x-filament-panels::page>
    @include('filament.cashier.partials.pos-ui-styles')

    @php
        $listed = $this->getListedLoans();
        $addable = $this->getAddableLoans();
        $listedCount = $listed->count();
        $addableCount = $this->addableCount();
        $totalRemaining = $listed->sum('remaining');
        $totalInstallment = $listed->sum('installment');
        $searching = trim($memberSearch) !== '';
    @endphp

    <div class="fi-pos-ui rl-screen">
        <div class="pos-panel" style="padding: 1rem 1.25rem; margin-bottom: 1rem;">
            <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: flex-start; justify-content: space-between;">
                <div>
                    <h2 style="margin: 0; font-size: 1.125rem; font-weight: 700;">{{ __('Regular loan') }}</h2>
                    <p class="pos-muted" style="margin: 0.375rem 0 0; font-size: 0.8125rem;">
                        {{ __('Add accounts to the APDS list, then record Landbank amounts or print the PDF.') }}
                    </p>
                </div>
                <button
                    type="button"
                    onclick="window.print()"
                    class="pos-btn-primary"
                    style="width: auto; white-space: nowrap;"
                    @disabled($listedCount === 0)
                >
                    {{ __('Print APDS PDF') }}
                </button>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr)); gap: 1rem; margin: 1rem 0;">
                <div class="pos-panel pos-stat">
                    <div class="pos-muted pos-stat-label">{{ __('On APDS list') }}</div>
                    <div class="pos-stat-value">{{ number_format($listedCount) }}</div>
                </div>
                <div class="pos-panel pos-stat">
                    <div class="pos-muted pos-stat-label">{{ __('Not on list') }}</div>
                    <div class="pos-stat-value">{{ number_format(max(0, $addableCount)) }}</div>
                </div>
                <div class="pos-panel pos-stat">
                    <div class="pos-muted pos-stat-label">{{ __('Remaining principal') }}</div>
                    <div class="pos-stat-value">₱{{ number_format($totalRemaining, 2) }}</div>
                </div>
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: flex-end;">
                <div style="flex: 1 1 16rem;">
                    <label for="regular-loan-member-search" class="pos-muted" style="display: block; font-size: 0.75rem; font-weight: 600; margin-bottom: 0.375rem;">
                        {{ __('Search to add') }}
                    </label>
                    <input
                        id="regular-loan-member-search"
                        type="search"
                        wire:model.live.debounce.300ms="memberSearch"
                        autocomplete="off"
                        placeholder="{{ __('Name, email, phone, member ID, or loan ID…') }}"
                        class="pos-input"
                    />
                </div>
                <button type="button" wire:click="addAll" class="pos-btn-secondary" style="width: auto; white-space: nowrap;" @disabled($addableCount < 1)>
                    {{ __('Add all') }}
                </button>
                @if ($listedCount > 0)
                    <button type="button" wire:click="clearList" class="pos-btn-danger" style="width: auto; white-space: nowrap;">
                        {{ __('Clear list') }}
                    </button>
                @endif
            </div>
        </div>

        @if ($addable->isNotEmpty())
            <div class="pos-panel" style="padding: 1rem 1.25rem; margin-bottom: 1rem;">
                <h3 style="margin: 0 0 0.75rem; font-size: 0.8125rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">
                    {{ $searching ? __('Matching accounts') : __('Available to add') }}
                </h3>
                <div style="overflow-x: auto;">
                    <table class="pos-table" style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                        <thead>
                            <tr>
                                <th style="text-align: left; padding: 0.5rem 0.75rem; font-size: 0.6875rem; font-weight: 700; text-transform: uppercase;">{{ __('Member') }}</th>
                                <th style="text-align: right; padding: 0.5rem 0.75rem; font-size: 0.6875rem; font-weight: 700; text-transform: uppercase;">{{ __('Remaining') }}</th>
                                <th style="text-align: right; padding: 0.5rem 0.75rem; font-size: 0.6875rem; font-weight: 700; text-transform: uppercase;">{{ __('Installment') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($addable as $row)
                                @php
                                    $loan = $row['loan'];
                                    $member = $loan->member;
                                    $loanId = (int) $loan->id;
                                @endphp
                                <tr wire:key="regular-add-{{ $loanId }}">
                                    <td style="padding: 0.65rem 0.75rem;">
                                        <div style="font-weight: 700;">{{ $member?->name ?? __('Unknown member') }}</div>
                                        <div class="pos-muted" style="font-size: 0.75rem;">
                                            {{ __('Member ID') }} #{{ $loan->member_id }} · {{ __('Loan') }} #{{ $loanId }}
                                        </div>
                                    </td>
                                    <td style="padding: 0.65rem 0.75rem; text-align: right; white-space: nowrap;">₱{{ number_format($row['remaining'], 2) }}</td>
                                    <td style="padding: 0.65rem 0.75rem; text-align: right; white-space: nowrap;">
                                        @if ($row['installment'] > 0)
                                            ₱{{ number_format($row['installment'], 2) }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td style="padding: 0.65rem 0.75rem; text-align: right;">
                                        <button type="button" wire:click="addLoan({{ $loanId }})" class="pos-btn-primary" style="width: auto;">
                                            {{ __('Add') }}
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @elseif ($searching)
            <div class="pos-panel" style="padding: 1.25rem; margin-bottom: 1rem; text-align: center;">
                <p class="pos-muted" style="margin: 0;">{{ __('No open regular loans match your search, or they are already on the list.') }}</p>
            </div>
        @endif

        @if ($listed->isEmpty())
            <div class="pos-panel" style="padding: 2.5rem 1.5rem; text-align: center;">
                <p class="pos-muted" style="margin: 0; font-size: 0.9375rem;">
                    {{ __('The APDS list is empty. Search and Add an account, or Add all.') }}
                </p>
            </div>
        @else
            <div class="pos-panel" style="padding: 0; overflow: hidden;">
                <div class="pos-table-wrap" style="overflow-x: auto;">
                    <table class="pos-table" style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                        <thead>
                            <tr>
                                <th style="text-align: left; padding: 0.75rem 1rem; font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">{{ __('Member') }}</th>
                                <th style="text-align: right; padding: 0.75rem 1rem; font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">{{ __('Remaining') }}</th>
                                <th style="text-align: right; padding: 0.75rem 1rem; font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">{{ __('Installment') }}</th>
                                <th style="text-align: left; padding: 0.75rem 1rem; font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">{{ __('Landbank amount') }}</th>
                                <th style="padding: 0.75rem 1rem; text-align: right; vertical-align: bottom;">
                                    <button type="button" wire:click="recordAllRemittances" class="pos-btn-primary" style="width: auto; white-space: nowrap;">
                                        {{ __('Record all') }}
                                    </button>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($listed as $row)
                                @php
                                    $loan = $row['loan'];
                                    $member = $loan->member;
                                    $loanId = (int) $loan->id;
                                @endphp
                                <tr wire:key="regular-listed-{{ $loanId }}">
                                    <td style="padding: 0.875rem 1rem; vertical-align: top;">
                                        <div style="font-weight: 700;">{{ $member?->name ?? __('Unknown member') }}</div>
                                        <div class="pos-muted" style="font-size: 0.75rem; margin-top: 0.125rem;">
                                            {{ __('Member ID') }} #{{ $loan->member_id }}
                                            · {{ __('Loan') }} #{{ $loanId }}
                                            @if ($member?->contact_number)
                                                · {{ $member->contact_number }}
                                            @endif
                                        </div>
                                    </td>
                                    <td style="padding: 0.875rem 1rem; text-align: right; font-weight: 700; white-space: nowrap; vertical-align: top;">
                                        ₱{{ number_format($row['remaining'], 2) }}
                                    </td>
                                    <td style="padding: 0.875rem 1rem; text-align: right; white-space: nowrap; vertical-align: top;">
                                        @if ($row['installment'] > 0)
                                            ₱{{ number_format($row['installment'], 2) }}
                                        @else
                                            <span class="pos-muted">—</span>
                                        @endif
                                    </td>
                                    <td style="padding: 0.875rem 1rem; vertical-align: top; min-width: 12rem;">
                                        <input
                                            type="text"
                                            inputmode="decimal"
                                            autocomplete="off"
                                            x-mask:dynamic="$money($input, '.', ',', 2)"
                                            wire:model="amounts.{{ $loanId }}"
                                            placeholder="{{ __('Amount from Landbank') }}"
                                            class="pos-input"
                                            aria-label="{{ __('Landbank amount for :member', ['member' => $member?->name ?? $loanId]) }}"
                                        />
                                        @if (! empty($rowErrors[$loanId]))
                                            <p style="margin: 0.375rem 0 0; font-size: 0.75rem; font-weight: 600; color: rgb(185 28 28);">{{ $rowErrors[$loanId] }}</p>
                                        @endif
                                    </td>
                                    <td style="padding: 0.875rem 1rem; vertical-align: top; white-space: nowrap;">
                                        <div style="display: flex; justify-content: flex-end;">
                                            <button
                                                type="button"
                                                wire:click="removeLoan({{ $loanId }})"
                                                class="pos-btn-danger"
                                                style="width: auto; min-width: 2.5rem; justify-content: center;"
                                                title="{{ __('Remove from APDS list') }}"
                                            >
                                                {{ __('X') }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    <div id="apds-print" class="apds-print-only">
        <header class="apds-head">
            <img src="{{ asset('images/DICNHSLOGO1.png') }}" alt="TEMPUCO" class="apds-logo">
            <div class="apds-head-text">
                <div class="apds-coop">
                    {{ __('DIGOS CITY NATIONAL HIGH SCHOOL TEACHERS') }}<br>
                    {{ __('AND EMPLOYEES MULTI-PURPOSE COOPERATIVE') }}
                </div>
                <div class="apds-city">{{ __('DIGOS CITY') }}</div>
                <div class="apds-aka">{{ __('( DICNHS-TEMPUCO )') }}</div>
            </div>
        </header>

        <div class="apds-intro-row">
            <p class="apds-intro">
                {{ __('I have the honor to submit herewith the list of teachers who availed for Automatic Payroll Deduction System (APDS) to wit:') }}
            </p>
            <div class="apds-date">{{ now()->format('F j, Y') }}</div>
        </div>

        <table class="apds-table">
            <thead>
                <tr>
                    <th class="apds-num"></th>
                    <th>{{ __('NAME') }}</th>
                    <th>{{ __('Monthly Installment') }}</th>
                    <th>{{ __('Remarks') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($listed as $index => $row)
                    <tr>
                        <td class="apds-num">{{ $index + 1 }}</td>
                        <td>{{ $row['loan']->member?->name ?? __('Unknown member') }}</td>
                        <td class="apds-amt">
                            @if ($row['installment'] > 0.005)
                                {{ number_format($row['installment'], 2) }}
                            @else
                                —
                            @endif
                        </td>
                        <td></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="apds-empty">{{ __('No accounts on the APDS list.') }}</td>
                    </tr>
                @endforelse
            </tbody>
            @if ($listed->isNotEmpty())
                <tfoot>
                    <tr>
                        <td colspan="2"></td>
                        <td class="apds-amt"><strong>{{ number_format($totalInstallment, 2) }}</strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            @endif
        </table>

        <p class="apds-thanks">{{ __('Thank you very much.') }}</p>
        <div class="apds-sign">
            <div>{{ __('PREPARED BY:') }}</div>
            <div class="apds-sign-line"></div>
            <div><strong>{{ __('Treasurer') }}</strong></div>
        </div>
    </div>

    <style>
        .apds-print-only { display: none; }

        .apds-head {
            display: flex;
            align-items: flex-start;
            gap: 0.45rem;
            margin-bottom: 0.5rem;
            font-family: "Times New Roman", Times, serif;
        }

        .apds-logo {
            width: 88px;
            height: 88px;
            object-fit: contain;
            flex-shrink: 0;
            margin-top: 0.1rem;
        }

        .apds-head-text {
            flex: 1;
            min-width: 0;
            text-align: center;
            color: #000;
            padding-top: 0.05rem;
        }

        .apds-coop {
            font-size: 13.5pt;
            font-weight: 700;
            line-height: 1.18;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .apds-city {
            font-size: 12.5pt;
            font-weight: 700;
            margin-top: 0.2rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .apds-aka {
            font-size: 12pt;
            font-weight: 700;
            margin-top: 0.05rem;
            letter-spacing: 0.03em;
        }

        .apds-intro-row {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin: 0.35rem 0 0.6rem;
        }

        .apds-intro {
            flex: 1;
            margin: 0;
            font-size: 0.8rem;
            line-height: 1.35;
            text-align: left;
            color: #000;
        }

        .apds-date {
            flex-shrink: 0;
            font-size: 0.8rem;
            font-weight: 700;
            white-space: nowrap;
            padding-top: 0.05rem;
            color: #000;
        }

        .apds-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.78rem;
            color: #000;
        }

        .apds-table th,
        .apds-table td {
            border: 1px solid #000;
            padding: 0.22rem 0.4rem;
        }

        .apds-table th {
            font-weight: 700;
            text-align: center;
            vertical-align: middle;
        }

        .apds-num { width: 2.1rem; text-align: center; }
        .apds-amt { text-align: right; white-space: nowrap; width: 8.5rem; }
        .apds-table th:last-child,
        .apds-table td:last-child { width: 7.5rem; }
        .apds-empty { text-align: center; padding: 1rem; }
        .apds-thanks { margin: 1rem 0 1.5rem; font-size: 0.85rem; color: #000; }
        .apds-sign { font-size: 0.8rem; color: #000; }
        .apds-sign-line { width: 14rem; border-bottom: 1px solid #000; margin: 2rem 0 0.25rem; }

        @media print {
            @page { size: portrait; margin: 12mm; }

            html, body {
                background: #fff !important;
            }

            .fi-topbar,
            .fi-sidebar,
            .fi-header,
            .fi-main-ctn > .fi-width-constraint > .fi-page-header,
            .rl-screen {
                display: none !important;
                visibility: hidden !important;
            }

            body * { visibility: hidden; }

            #apds-print,
            #apds-print * { visibility: visible; }

            #apds-print {
                display: block !important;
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                color: #000;
                background: #fff;
            }
        }
    </style>
</x-filament-panels::page>
