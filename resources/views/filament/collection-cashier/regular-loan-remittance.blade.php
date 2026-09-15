<x-filament-panels::page>
    @include('filament.cashier.partials.pos-ui-styles')

    @php
        $rows = $this->getOpenLoans();
        $hiddenCount = $this->hiddenCount();
        $totalRemaining = $rows->sum('remaining');
    @endphp

    <div class="fi-pos-ui">
        <div class="pos-panel" style="padding: 1rem 1.25rem; margin-bottom: 1rem;">
            <h2 style="margin: 0; font-size: 1.125rem; font-weight: 700;">{{ __('Regular loan') }}</h2>
            <p class="pos-muted" style="margin: 0.375rem 0 1rem; font-size: 0.8125rem;">
                {{ __('Hide accounts that are not on the Landbank remittance, then enter each Landbank amount.') }}
            </p>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                <div class="pos-panel pos-stat">
                    <div class="pos-muted pos-stat-label">{{ __('On this list') }}</div>
                    <div class="pos-stat-value">{{ number_format($rows->count()) }}</div>
                </div>
                <div class="pos-panel pos-stat">
                    <div class="pos-muted pos-stat-label">{{ __('Hidden (not Landbank)') }}</div>
                    <div class="pos-stat-value">{{ number_format($hiddenCount) }}</div>
                </div>
                <div class="pos-panel pos-stat">
                    <div class="pos-muted pos-stat-label">{{ __('Remaining principal') }}</div>
                    <div class="pos-stat-value">₱{{ number_format($totalRemaining, 2) }}</div>
                </div>
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: flex-end;">
                <div style="flex: 1 1 16rem;">
                    <label for="regular-loan-member-search" class="pos-muted" style="display: block; font-size: 0.75rem; font-weight: 600; margin-bottom: 0.375rem;">
                        {{ __('Search member') }}
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
                @if ($hiddenCount > 0)
                    <button type="button" wire:click="restoreHidden" class="pos-btn-secondary" style="width: auto; white-space: nowrap;">
                        {{ __('Restore hidden') }}
                    </button>
                @endif
            </div>
        </div>

        @if ($rows->isEmpty())
            <div class="pos-panel" style="padding: 2.5rem 1.5rem; text-align: center;">
                <p class="pos-muted" style="margin: 0; font-size: 0.9375rem;">
                    @if (trim($memberSearch) !== '')
                        {{ __('No open regular loans match your search.') }}
                    @elseif ($hiddenCount > 0)
                        {{ __('Every remaining account is hidden. Restore hidden to bring them back.') }}
                    @else
                        {{ __('No members currently have an open regular loan.') }}
                    @endif
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
                                <th style="padding: 0.75rem 1rem;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                @php
                                    $loan = $row['loan'];
                                    $member = $loan->member;
                                    $loanId = (int) $loan->id;
                                @endphp
                                <tr wire:key="regular-remittance-{{ $loanId }}">
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
                                        <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                            <button
                                                type="button"
                                                wire:click="recordRemittance({{ $loanId }})"
                                                class="pos-btn-primary"
                                                style="width: auto;"
                                            >
                                                {{ __('Record') }}
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="hideLoan({{ $loanId }})"
                                                class="pos-btn-danger"
                                                style="width: auto; min-width: 2.5rem; justify-content: center;"
                                                title="{{ __('Hide — not on the Landbank list') }}"
                                                aria-label="{{ __('Hide :member from this list', ['member' => $member?->name ?? $loanId]) }}"
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
</x-filament-panels::page>
