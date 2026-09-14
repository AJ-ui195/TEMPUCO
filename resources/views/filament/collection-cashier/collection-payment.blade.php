<x-filament-panels::page>
    @include('filament.cashier.partials.pos-ui-styles')

    <style
        id="pos-receipt-iframe-styles"
        media="not all"
        data-receipt-title="{{ __('Credit payment receipt') }}"
    >@include('filament.cashier.partials.receipt-print-styles')</style>

    @php
        $member = $this->getSelectedMember();
        $accounts = $this->getAccounts();
        $results = $this->getSearchResults();
    @endphp

    <div class="fi-pos-ui">
        <div class="pos-panel" style="padding: 1rem 1.25rem; margin-bottom: 1rem;">
            <h2 style="margin: 0; font-size: 1.125rem; font-weight: 700;">{{ __('Collection payment') }}</h2>
            <p class="pos-muted" style="margin: 0.375rem 0 1rem; font-size: 0.8125rem;">
                    {{ __('Search a member, then open a ledger to collect.') }}
            </p>

            <label for="collection-member-search" class="pos-muted" style="display: block; font-size: 0.75rem; font-weight: 600; margin-bottom: 0.375rem;">
                {{ __('Search member') }}
            </label>
            <div style="display: flex; gap: 0.5rem;">
                <input
                    id="collection-member-search"
                    type="search"
                    wire:model.live.debounce.300ms="memberSearch"
                    autocomplete="off"
                    placeholder="{{ __('Name, email, phone, or member ID…') }}"
                    class="pos-input"
                />
                @if ($member)
                    <button type="button" wire:click="clearMember" class="pos-btn-secondary" style="width: auto; white-space: nowrap;">
                        {{ __('Clear') }}
                    </button>
                @endif
            </div>

            @if ($results->isNotEmpty())
                <ul style="list-style: none; margin: 0.5rem 0 0; padding: 0; border: 1px solid rgb(226 232 240); border-radius: 0.5rem; overflow: hidden;">
                    @foreach ($results as $hit)
                        <li>
                            <button
                                type="button"
                                wire:click="selectMember({{ $hit->id }})"
                                style="width: 100%; text-align: left; padding: 0.75rem 1rem; background: transparent; border: 0; border-bottom: 1px solid rgb(226 232 240); cursor: pointer;"
                            >
                                <span style="font-weight: 700;">{{ $hit->name }}</span>
                                <span class="pos-muted" style="display: block; font-size: 0.75rem;">
                                    #{{ $hit->id }}
                                    @if ($hit->email) · {{ $hit->email }} @endif
                                    @if ($hit->contact_number) · {{ $hit->contact_number }} @endif
                                </span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            @elseif (trim($memberSearch) !== '' && ! $member)
                <p class="pos-muted" style="margin: 0.75rem 0 0; font-size: 0.875rem;">{{ __('No members match your search.') }}</p>
            @endif
        </div>

        @if (! $member)
            <div class="pos-panel" style="padding: 2.5rem 1.5rem; text-align: center;">
                <p class="pos-muted" style="margin: 0; font-size: 0.9375rem;">
                    {{ __('Search and select a member to see Regular, Character, Canteen, and Quick ledgers.') }}
                </p>
            </div>
        @else
            <div class="pos-panel" style="padding: 1rem 1.25rem; margin-bottom: 1rem;">
                <div style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 0.75rem; align-items: flex-start;">
                    <div>
                        <div style="font-size: 1.125rem; font-weight: 700;">{{ $member->name }}</div>
                        <div class="pos-muted" style="font-size: 0.8125rem; margin-top: 0.25rem;">
                            {{ __('Member ID') }} #{{ $member->id }}
                            @if ($member->email) · {{ $member->email }} @endif
                            @if ($member->contact_number) · {{ $member->contact_number }} @endif
                        </div>
                    </div>
                    <button type="button" wire:click="openChargeModal" class="pos-btn-secondary" style="width: auto;">
                        {{ __('Charge to canteen credit') }}
                    </button>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr)); gap: 0.75rem; margin-top: 1rem;">
                    @foreach ($accounts['ledgers'] as $type => $ledger)
                        @php($isExpanded = $this->expandedKey === $type)
                        <button
                            type="button"
                            wire:click="toggleAccount('{{ $type }}')"
                            class="pos-panel pos-stat"
                            style="text-align: left; cursor: pointer; {{ $isExpanded ? 'outline: 2px solid rgb(4 120 87);' : '' }}"
                        >
                            <div class="pos-muted pos-stat-label">{{ $ledger['label'] }}</div>
                            <div class="pos-stat-value">₱{{ number_format($ledger['balance'], 2) }}</div>
                        </button>
                    @endforeach
                </div>
            </div>

            @php($expandedLedger = $this->getExpandedLedger())

            @if ($expandedLedger)
                <div class="pos-panel" style="padding: 0; overflow: hidden; margin-bottom: 1rem;">
                    <div class="pos-cart-header" style="padding: 0.875rem 1rem; display: flex; flex-wrap: wrap; justify-content: space-between; gap: 0.75rem; align-items: center;">
                        <div>
                            <div style="font-weight: 700;">{{ $expandedLedger['label'] }}</div>
                            <div class="pos-muted" style="font-size: 0.75rem;">{{ __('Balance') }} ₱{{ number_format($expandedLedger['balance'], 2) }}</div>
                        </div>
                        <button
                            type="button"
                            wire:click="openCollectForm('{{ $expandedLedger['type'] }}')"
                            class="pos-btn-primary"
                            style="width: auto; padding: 0.5rem 0.875rem; font-size: 0.8125rem;"
                        >
                            {{ __('Collect') }}
                        </button>
                    </div>
                    <div style="overflow-x: auto; padding: 0.75rem 1rem 1rem;">
                        @if ($expandedLedger['type'] === \App\Support\MemberCollectionAccounts::TYPE_REGULAR)
                            @php($regularView = $this->getRegularScheduleView())
                            @if ($regularView['loans']->count() > 1)
                                <label class="pos-muted" style="display: block; font-size: 0.75rem; font-weight: 600; margin-bottom: 0.375rem;">
                                    {{ __('Account') }}
                                </label>
                                <select wire:model.live="regularViewLoanId" class="pos-input" style="margin-bottom: 0.75rem; max-width: 24rem;">
                                    @foreach ($regularView['loans'] as $regularLoan)
                                        <option value="{{ $regularLoan->id }}">
                                            {{ __('Regular loan') }} #{{ $regularLoan->id }}
                                            — ₱{{ number_format((float) $regularLoan->loan_amount, 2) }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                            @include('filament.user.regular-loan-schedule', [
                                'schedule' => $regularView['schedule'],
                                'borrowerName' => $member->name,
                                'blankAmounts' => $regularView['blankAmounts'],
                            ])
                        @elseif ($expandedLedger['type'] === \App\Support\MemberCollectionAccounts::TYPE_CHARACTER)
                            @php($characterView = $this->getCharacterLedgerView())
                            @include('filament.user.character-loan-ledger', $characterView)
                        @elseif ($expandedLedger['type'] === \App\Support\MemberCollectionAccounts::TYPE_CANTEEN)
                            @php($canteenView = $this->getCanteenLedgerView())
                            @include('filament.user.canteen-credit-ledger', $canteenView)
                        @elseif ($expandedLedger['type'] === \App\Support\MemberCollectionAccounts::TYPE_QUICK)
                            @php($quickView = $this->getQuickLedgerView())
                            @include('filament.user.quick-loan-ledger', $quickView)
                        @endif
                    </div>
                </div>
            @endif
        @endif

        @php($collectAccount = ($this->collectKey && is_array($accounts)) ? $this->findOpenAccount($this->collectKey) : null)
        @php($collectLedger = ($this->collectKey && is_array($accounts)) ? ($accounts['ledgers'][$this->collectKey] ?? null) : null)

        @if ($member && $collectAccount)
            <div class="pos-credit-modal" wire:key="collect-{{ $collectKey }}">
                <div class="pos-credit-modal__backdrop" wire:click="closeCollectForm"></div>
                <div class="pos-credit-modal__dialog">
                    <h3 style="margin: 0 0 0.25rem; font-size: 1.0625rem; font-weight: 700;">{{ __('Collect payment') }}</h3>
                    <p class="pos-muted" style="margin: 0 0 1rem; font-size: 0.8125rem;">
                        {{ $member->name }} · {{ $collectAccount['label'] }}
                    </p>

                    @if ($collectLedger && collect($collectLedger['accounts'])->count() > 1)
                        <label style="display: block; font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.375rem;">{{ __('Account') }}</label>
                        <select wire:model.live="collectLoanId" class="pos-input" style="margin-bottom: 0.75rem;">
                            @foreach ($collectLedger['accounts'] as $accountOption)
                                <option value="{{ $accountOption['loan_id'] }}">
                                    {{ $accountOption['label'] }} — ₱{{ number_format($accountOption['balance'], 2) }}
                                </option>
                            @endforeach
                        </select>
                    @endif

                    <div class="pos-panel pos-stat" style="margin-bottom: 1rem;">
                        <div class="pos-muted pos-stat-label">{{ __('Outstanding') }}</div>
                        <div class="pos-stat-value">₱{{ number_format($collectAccount['balance'], 2) }}</div>
                    </div>

                    @if ($collectAccount['type'] === \App\Support\MemberCollectionAccounts::TYPE_CHARACTER)
                        <label style="display: block; font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.375rem;">{{ __('Payment type') }}</label>
                        <select wire:model.live="paymentKind" class="pos-input" style="margin-bottom: 0.75rem;">
                            <option value="{{ \App\Support\CharacterLoanLedgerEntries::KIND_INTEREST }}">{{ __('Interest') }}</option>
                            <option value="{{ \App\Support\CharacterLoanLedgerEntries::KIND_PRINCIPAL }}">{{ __('Principal') }}</option>
                        </select>
                    @endif

                    <label for="collection-amount" style="display: block; font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.375rem;">{{ __('Amount (₱)') }}</label>
                    <input
                        id="collection-amount"
                        type="text"
                        inputmode="decimal"
                        autocomplete="off"
                        x-mask:dynamic="$money($input, '.', ',', 2)"
                        wire:model="paymentAmount"
                        class="pos-input"
                        style="margin-bottom: 0.75rem;"
                    />

                    @if ($collectAccount['type'] !== \App\Support\MemberCollectionAccounts::TYPE_CANTEEN && $collectAccount['type'] !== \App\Support\MemberCollectionAccounts::TYPE_REGULAR)
                        <label for="collection-or" style="display: block; font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.375rem;">{{ __('O.R. #') }}</label>
                        <input id="collection-or" type="text" wire:model="officialReceiptNo" class="pos-input" />
                    @endif

                    @if ($paymentError)
                        <p style="margin: 0.75rem 0 0; font-size: 0.8125rem; font-weight: 600; color: rgb(185 28 28);">{{ $paymentError }}</p>
                    @endif

                    <div style="display: flex; gap: 0.5rem; margin-top: 1.25rem;">
                        <button type="button" wire:click="recordCollection" class="pos-btn-primary">{{ __('Save payment') }}</button>
                        <button type="button" wire:click="closeCollectForm" class="pos-btn-secondary">{{ __('Cancel') }}</button>
                    </div>
                </div>
            </div>
        @endif

        @php($chargeMember = $this->getChargeMember())

        @if ($chargeMember)
            <div class="pos-credit-modal" wire:key="charge-{{ $chargeMember->id }}">
                <div class="pos-credit-modal__backdrop" wire:click="closeChargeModal"></div>
                <div class="pos-credit-modal__dialog">
                    <h3 style="margin: 0 0 0.25rem; font-size: 1.0625rem; font-weight: 700;">{{ __('Charge to canteen credit') }}</h3>
                    <p class="pos-muted" style="margin: 0 0 1rem; font-size: 0.8125rem;">{{ $chargeMember->name }}</p>
                    <div class="pos-panel pos-stat" style="margin-bottom: 1rem;">
                        <div class="pos-muted pos-stat-label">{{ __('Remaining monthly limit') }}</div>
                        <div class="pos-stat-value">₱{{ number_format($this->getChargeRemainingLimit(), 2) }}</div>
                    </div>
                    <label for="charge-amount" style="display: block; font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.375rem;">{{ __('Amount to charge (₱)') }}</label>
                    <input
                        id="charge-amount"
                        type="text"
                        inputmode="decimal"
                        autocomplete="off"
                        x-mask:dynamic="$money($input, '.', ',', 2)"
                        wire:model="chargeAmount"
                        class="pos-input"
                    />
                    @if ($chargeError)
                        <p style="margin: 0.75rem 0 0; font-size: 0.8125rem; font-weight: 600; color: rgb(185 28 28);">{{ $chargeError }}</p>
                    @endif
                    <div style="display: flex; gap: 0.5rem; margin-top: 1.25rem;">
                        <button type="button" wire:click="recordCharge" class="pos-btn-primary">{{ __('Charge to account') }}</button>
                        <button type="button" wire:click="closeChargeModal" class="pos-btn-secondary">{{ __('Cancel') }}</button>
                    </div>
                </div>
            </div>
        @endif

        @if ($showReceiptModal && ($receipt = $this->getReceiptData()))
            <div class="pos-credit-modal" wire:key="receipt-{{ $receiptPaymentId }}" x-data x-init="$nextTick(() => setTimeout(() => window.posGroceryPrintReceipt?.(), 100))">
                <div class="pos-credit-modal__backdrop" wire:click="closeReceiptModal"></div>
                <div class="pos-credit-modal__dialog">
                    <h3 style="margin: 0 0 0.75rem; font-size: 1.0625rem; font-weight: 700;">{{ __('Credit payment receipt') }}</h3>
                    <div id="pos-receipt-print-area">
                        @include('filament.cashier.partials.credit-payment-receipt-body', [
                            'payment' => $receipt['payment'],
                            'cashier' => $receipt['cashier'],
                            'balanceBefore' => $receipt['balanceBefore'],
                            'balanceAfter' => $receipt['balanceAfter'],
                        ])
                    </div>
                    <div style="display: flex; gap: 0.5rem; margin-top: 1.25rem;">
                        <button type="button" onclick="window.posGroceryPrintReceipt()" class="pos-btn-primary">{{ __('Print') }}</button>
                        <button type="button" wire:click="closeReceiptModal" class="pos-btn-secondary">{{ __('Close') }}</button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <style>
        .fi-pos-ui .pos-credit-modal { position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .fi-pos-ui .pos-credit-modal__backdrop { position: absolute; inset: 0; background: rgba(15, 23, 42, 0.55); }
        .fi-pos-ui .pos-credit-modal__dialog { position: relative; width: 100%; max-width: 22rem; padding: 1.25rem; border-radius: 0.75rem; background: #fff; box-shadow: 0 20px 45px rgba(15, 23, 42, 0.25); }
        .dark .fi-pos-ui .pos-credit-modal__dialog { background: rgb(30 41 59); color: #fff; }
        .dark .fi-pos-ui ul { border-color: rgba(255,255,255,0.12) !important; }
    </style>

    <script src="{{ asset('js/pos-grocery-print-receipt.js') }}"></script>
</x-filament-panels::page>
