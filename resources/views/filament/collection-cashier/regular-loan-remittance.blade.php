<x-filament-panels::page>
    @include('filament.cashier.partials.pos-ui-styles')

    @php
        $listed = $this->getListedLoans();
        $addable = $this->getAddableLoans();
        $listedCount = $listed->count();
        $addableCount = $this->addableCount();
        $totalRemaining = $listed->sum('remaining');
        $totalInstallment = $listed->sum('installment');
        $totalLandbank = $this->getTotalLandbankAmount();
        $searching = trim($memberSearch) !== '';
    @endphp

    <div class="fi-pos-ui rl-screen">
        <div class="pos-panel col-pay-head" style="padding: 1rem 1.25rem; margin-bottom: 1rem;">
            <div class="rl-head-top">
                <div>
                    <h2 class="col-pay-title" style="margin: 0;">{{ __('Regular loan') }}</h2>
                    
                </div>
                <div class="rl-head-tools">
                    <label>
                        <span>{{ __('Date') }}</span>
                        <input type="date" wire:model.live="paymentDate" class="pos-input">
                    </label>
                    <label>
                        <span>{{ __('O.R. #') }}</span>
                        <input
                            type="text"
                            wire:model.live.debounce.400ms="officialReceiptNo"
                            wire:keydown.enter.prevent="searchOfficialReceipt"
                            wire:blur="searchOfficialReceipt"
                            class="pos-input"
                            placeholder="{{ __('Search O.R. #') }}"
                            autocomplete="off"
                        >
                    </label>
                    <div class="rl-head-print">
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
                </div>
            </div>
            @if ($paymentError)
                <p style="margin: 0.75rem 0 0; font-size: 0.8125rem; font-weight: 600; color: rgb(185 28 28);">{{ $paymentError }}</p>
            @endif

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
                                <th class="rl-landbank-col" style="padding: 0.75rem 1rem; font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">{{ __('Landbank amount') }}</th>
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
                                    <td class="rl-landbank-col" style="padding: 0.875rem 1rem; vertical-align: middle;">
                                        <div class="rl-landbank-box">
                                            <input
                                                type="text"
                                                inputmode="decimal"
                                                autocomplete="off"
                                                x-mask:dynamic="$money($input, '.', ',', 2)"
                                                wire:model="amounts.{{ $loanId }}"
                                                placeholder="{{ __('Amount from Landbank') }}"
                                                class="pos-input rl-landbank-input"
                                                aria-label="{{ __('Landbank amount for :member', ['member' => $member?->name ?? $loanId]) }}"
                                            />
                                            @if (! empty($rowErrors[$loanId]))
                                                <p style="margin: 0.375rem 0 0; font-size: 0.75rem; font-weight: 600; color: rgb(185 28 28);">{{ $rowErrors[$loanId] }}</p>
                                            @endif
                                        </div>
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
                        <tfoot>
                            <tr style="font-weight: 700;">
                                <td colspan="3" style="padding: 0.875rem 1rem; text-align: right;">{{ __('Total') }}</td>
                                <td class="rl-landbank-col" style="padding: 0.875rem 1rem;">
                                    <div class="rl-landbank-box rl-landbank-total">₱{{ number_format($totalLandbank, 2) }}</div>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @endif

        <div class="rl-pay-bar">
            <div class="col-pay-actions" role="group" aria-label="{{ __('Payment actions') }}">
                <button type="button" wire:click="cancelOfficialReceipt" class="col-pay-action col-pay-action--cancel">
                    {{ __('Cancelled OR#') }}
                </button>
                <button type="button" wire:click="deletePaymentDraft" class="col-pay-action col-pay-action--delete">
                    {{ __('Delete payment') }}
                </button>
                <button type="button" wire:click="updatePayment" class="col-pay-action col-pay-action--update">
                    {{ __('Update payment') }}
                </button>
                <button type="button" wire:click="savePayment" class="col-pay-action col-pay-action--save">
                    {{ __('Save payment') }}
                </button>
            </div>
        </div>

        @if ($showSavedModal)
            <div class="pos-credit-modal" wire:key="saved-payment">
                <div class="pos-credit-modal__backdrop" wire:click="closeSavedModal"></div>
                <div class="pos-credit-modal__dialog col-pay-saved col-pay-saved--{{ $savedTone }}">
                    <div class="col-pay-saved-icon" aria-hidden="true">
                        @if ($savedTone === 'delete')
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 6h18" /><path d="M8 6V4h8v2" /><path d="M19 6l-1 14H6L5 6" />
                            </svg>
                        @elseif ($savedTone === 'cancel')
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="9" /><path d="M15 9 9 15" /><path d="m9 9 6 6" />
                            </svg>
                        @else
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 6 9 17l-5-5" />
                            </svg>
                        @endif
                    </div>
                    <h3>{{ $savedTitle }}</h3>
                    <p>{{ $savedMessage }}</p>
                    <button type="button" wire:click="closeSavedModal">{{ __('OK') }}</button>
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
        .rl-landbank-col { text-align: center; width: 13rem; }
        .rl-landbank-box {
            width: 11rem;
            max-width: 100%;
            margin-inline: auto;
        }
        .rl-landbank-input {
            width: 100%;
            text-align: center;
            font-variant-numeric: tabular-nums;
        }
        .rl-landbank-total {
            text-align: center;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }
        .apds-print-only { display: none; }
        .col-pay-head { display: flex; flex-direction: column; }
        .col-pay-title { font-size: 1.15rem; font-weight: 800; letter-spacing: 0.04em; text-transform: uppercase; color: #1e3a8a; }
        .rl-head-top {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
        }
        .rl-head-tools {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            justify-content: flex-end;
            gap: 0.75rem;
        }
        .rl-head-tools label span {
            display: block;
            text-align: center;
            font-size: 0.7rem;
            font-weight: 700;
            margin-bottom: 0.3rem;
        }
        .fi-pos-ui .rl-head-tools .pos-input {
            width: 10.5rem;
            text-align: center;
        }
        .rl-head-print { display: flex; align-items: center; }
        .rl-pay-bar {
            display: flex;
            justify-content: flex-end;
            margin: 0.75rem 1rem 1.15rem;
            padding-right: 0.25rem;
        }
        .col-pay-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 0.4rem;
        }
        .col-pay-action {
            border: 0;
            border-radius: 0.45rem;
            padding: 0.4rem 0.85rem;
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1.2;
            cursor: pointer;
            color: #fff;
            width: auto;
            white-space: nowrap;
        }
        .col-pay-action--cancel { background: #e2e8f0; color: #0f172a; }
        .col-pay-action--delete { background: #ef5b6a; }
        .col-pay-action--update { background: #f5b942; color: #1f2937; }
        .col-pay-action--save { background: #2563eb; }
        .fi-pos-ui .pos-credit-modal { position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .fi-pos-ui .pos-credit-modal__backdrop { position: absolute; inset: 0; background: rgba(15, 23, 42, 0.55); }
        .fi-pos-ui .pos-credit-modal__dialog { position: relative; width: 100%; max-width: 22rem; padding: 1.25rem; border-radius: 0.75rem; background: #fff; box-shadow: 0 20px 45px rgba(15, 23, 42, 0.25); max-height: 90vh; overflow: auto; }
        .dark .fi-pos-ui .pos-credit-modal__dialog { background: rgb(30 41 59); color: #fff; }
        .fi-pos-ui .col-pay-saved { max-width: 20rem; padding: 1.5rem 1.35rem 1.25rem; text-align: center; }
        .col-pay-saved-icon {
            width: 3rem;
            height: 3rem;
            margin: 0 auto 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: rgb(220 252 231);
            color: rgb(22 163 74);
        }
        .col-pay-saved--update .col-pay-saved-icon { background: rgb(254 243 199); color: rgb(180 83 9); }
        .col-pay-saved--delete .col-pay-saved-icon { background: rgb(254 226 226); color: rgb(185 28 28); }
        .col-pay-saved--cancel .col-pay-saved-icon { background: rgb(226 232 240); color: rgb(51 65 85); }
        .dark .col-pay-saved-icon { background: rgb(20 83 45); color: rgb(134 239 172); }
        .dark .col-pay-saved--update .col-pay-saved-icon { background: rgb(120 53 15); color: rgb(253 224 71); }
        .dark .col-pay-saved--delete .col-pay-saved-icon { background: rgb(127 29 29); color: rgb(252 165 165); }
        .dark .col-pay-saved--cancel .col-pay-saved-icon { background: rgb(51 65 85); color: rgb(226 232 240); }
        .col-pay-saved--delete button { background: #ef5b6a; }
        .col-pay-saved--delete button:hover { background: #dc2626; }
        .col-pay-saved--update button { background: #d97706; }
        .col-pay-saved--update button:hover { background: #b45309; }
        .col-pay-saved--cancel button { background: #475569; }
        .col-pay-saved--cancel button:hover { background: #334155; }
        .col-pay-saved h3 { margin: 0 0 0.4rem; font-size: 1.125rem; font-weight: 800; }
        .col-pay-saved p { margin: 0 0 1.15rem; font-size: 0.875rem; line-height: 1.45; color: rgb(71 85 105); }
        .dark .col-pay-saved p { color: rgb(148 163 184); }
        .col-pay-saved button {
            width: 100%;
            border: 0;
            border-radius: 0.55rem;
            padding: 0.7rem 1rem;
            font-size: 0.875rem;
            font-weight: 700;
            cursor: pointer;
            color: #fff;
            background: #2563eb;
        }
        .col-pay-saved button:hover { background: #1d4ed8; }
        @media (max-width: 900px) {
            .rl-head-tools { justify-content: center; }
            .rl-head-top { justify-content: center; text-align: center; }
            .rl-pay-bar { justify-content: center; }
        }

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
