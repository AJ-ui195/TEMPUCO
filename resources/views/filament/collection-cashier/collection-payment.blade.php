<x-filament-panels::page>
    @include('filament.cashier.partials.pos-ui-styles')

    @php
        $member = $this->getSelectedMember();
        $accounts = $this->getAccounts();
        $results = $this->getSearchResults();
        $orKind = \App\Enums\ReceiptKind::OfficialReceipt->value;
        $invoiceKind = \App\Enums\ReceiptKind::Invoice->value;
    @endphp

    <div
        class="fi-pos-ui col-pay"
        x-data="{
            blockNonPeso(event) {
                if (event.ctrlKey || event.metaKey || event.altKey) {
                    return;
                }

                if (event.key.length !== 1) {
                    return;
                }

                if (! /[0-9.,]/.test(event.key)) {
                    event.preventDefault();
                }
            },
            stripNonPeso(el) {
                const cleaned = el.value.replace(/[^0-9.,]/g, '');

                if (el.value !== cleaned) {
                    el.value = cleaned;
                }
            },
        }"
    >
        @if (! $this->kindChosen)
            <p class="pos-muted" style="margin: 0;">{{ __('Choose Official Receipt or Invoice in the sidebar.') }}</p>
        @else
        <div class="pos-panel col-pay-head">
            <div>
                <h2 class="col-pay-title">{{ $this->receiptKind === $invoiceKind ? __('Invoice') : __('Official Receipt') }}</h2>
            </div>
            <div class="col-pay-meta">
                <label>
                    <span>{{ __('Date') }}</span>
                    <input type="date" wire:model.live="paymentDate" class="pos-input">
                </label>
                @if ($this->receiptKind === $orKind)
                    <label>
                        <span>{{ __('O.R. #') }}</span>
                        <input type="text" wire:model.live.debounce.400ms="officialReceiptNo" class="pos-input">
                    </label>
                @else
                    <label>
                        <span>{{ __('Invoice #') }}</span>
                        <input type="text" wire:model.live.debounce.400ms="invoiceNo" class="pos-input">
                    </label>
                @endif
            </div>
        </div>

        <div class="col-pay-grid">
            <div class="pos-panel col-pay-card">
                <div class="col-pay-kicker">{{ __('Account information') }}</div>
                @if ($paymentError)
                    <p style="margin: 0 0 0.75rem; font-size: 0.8125rem; font-weight: 600; color: rgb(185 28 28);">{{ $paymentError }}</p>
                @endif
                <label for="collection-member-search" class="pos-muted col-pay-label">{{ __('Full Name') }}</label>
                <div style="display: flex; gap: 0.5rem;">
                    <input
                        id="collection-member-search"
                        type="search"
                        wire:model.live.debounce.300ms="memberSearch"
                        autocomplete="off"
                        placeholder="{{ __('Type full name…') }}"
                        class="pos-input"
                    />
                    @if ($member)
                        <button type="button" wire:click="clearMember" class="pos-btn-secondary" style="width: auto; white-space: nowrap;">
                            {{ __('Clear') }}
                        </button>
                    @endif
                </div>

                @if ($results->isNotEmpty())
                    <ul class="col-pay-hits">
                        @foreach ($results as $hit)
                            <li>
                                <button type="button" wire:click="selectMember({{ $hit->id }})">
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

                @if ($member)
                    <p class="col-pay-balance" style="margin-bottom: 0.35rem;">
                        {{ __('TIN') }}:
                        <strong>{{ filled($member->tin_number) ? $member->tin_number : '—' }}</strong>
                    </p>
                    @if (! $this->isInvoiceMode())
                        <p class="col-pay-balance">
                            {{ __('Current balance') }}:
                            <strong>₱{{ number_format(collect($accounts['ledgers'])->sum('balance'), 2) }}</strong>
                        </p>
                        <div class="col-pay-chips">
                            @foreach ($accounts['ledgers'] as $type => $ledger)
                                @php($isExpanded = $this->expandedKey === $type)
                                <button
                                    type="button"
                                    wire:click="toggleAccount('{{ $type }}')"
                                    class="col-pay-chip{{ $isExpanded ? ' is-active' : '' }}"
                                >
                                    {{ $ledger['label'] }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                    <div class="col-pay-kicker" style="margin-top: 0.25rem;">{{ __('Payment details') }}</div>
                    <label class="pos-muted col-pay-label">{{ __('Type') }} *</label>
                    <select wire:model.live="collectionMethod" class="pos-input" style="margin-bottom: 0.75rem;">
                        <option value="">{{ __('Select payment type') }}</option>
                        <option value="cash">{{ __('Cash') }}</option>
                        <option value="check">{{ __('Checks') }}</option>
                    </select>
                    <label class="pos-muted col-pay-label">{{ __('Payment amount') }}</label>
                    <input
                        type="text"
                        inputmode="decimal"
                        autocomplete="off"
                        x-on:focus="$el.select()"
                        x-on:keydown="blockNonPeso($event)"
                        x-on:input="stripNonPeso($el)"
                        wire:blur="normalizePaymentAmount"
                        wire:model.live.debounce.300ms="paymentAmount"
                        class="pos-input"
                        style="margin-bottom: 0.5rem;"
                    />
                @endif
            </div>

            <div class="pos-panel col-pay-card col-pay-card--pay">
                <div class="col-pay-kicker">{{ __('Payment') }}</div>
                @if (! $member)
                    <p class="pos-muted" style="margin: 0;">{{ __('Select a member to collect.') }}</p>
                @else
                    <p style="margin: 0 0 0.75rem; font-weight: 700;">{{ $member->name }}</p>
                    @if (! $this->isInvoiceMode())
                        <button type="button" wire:click="openChargeModal" class="pos-btn-secondary" style="width: auto; margin-bottom: 0.85rem;">
                            {{ __('Charge to canteen credit') }}
                        </button>
                    @endif
                    @if ($this->isInvoiceMode())
                        <div class="col-pay-bill">
                            @foreach ($this->getInvoiceFeeLabels() as $type => $label)
                                <div class="col-pay-bill-row">
                                    <span class="col-pay-bill-label">{{ $label }}</span>
                                    <input
                                        type="text"
                                        inputmode="decimal"
                                        autocomplete="off"
                                        x-on:focus="$el.select()"
                                        x-on:keydown="blockNonPeso($event)"
                                        x-on:input="stripNonPeso($el)"
                                        wire:blur="normalizeInvoiceFees"
                                        wire:model.live.debounce.300ms="{{ match ($type) {
                                            'interest' => 'invoiceInterest',
                                            'surcharge' => 'invoiceSurcharge',
                                            'membership_fee' => 'invoiceMembershipFee',
                                            default => 'invoiceOthers',
                                        } }}"
                                        class="col-pay-bill-input"
                                    />
                                </div>
                            @endforeach
                            <div class="col-pay-bill-sum">
                                <span>{{ __('total') }}</span>
                                <strong>{{ number_format($this->getTotalAmount(), 2) }}</strong>
                            </div>
                        </div>
                    @else
                    @php($selectedLedger = $this->getExpandedLedger())
                    @if ($selectedLedger)
                        <div class="col-pay-bill">
                            <div class="col-pay-bill-row">
                                <span class="col-pay-bill-label">{{ $selectedLedger['label'] }}</span>
                                <input
                                    type="text"
                                    inputmode="decimal"
                                    autocomplete="off"
                                    x-on:focus="$el.select()"
                                    x-on:keydown="blockNonPeso($event)"
                                    x-on:input="stripNonPeso($el)"
                                    wire:blur="normalizePaymentAmount"
                                    wire:model.live.debounce.300ms="paymentAmount"
                                    class="col-pay-bill-input"
                                />
                            </div>
                            <div class="col-pay-bill-sum">
                                <span>{{ __('total') }}</span>
                                <strong>{{ number_format($this->getTotalAmount(), 2) }}</strong>
                            </div>
                        </div>
                    @endif
                    @endif
                    <div class="col-pay-totals" aria-label="{{ __('Cash tendered, change, and total payment') }}" style="margin-top: auto; padding-top: 1rem;">
                        <label class="col-pay-total-cell">
                            <span>{{ __('Cash Tendered') }}</span>
                            <input
                                type="text"
                                inputmode="decimal"
                                autocomplete="off"
                                placeholder="{{ __('Enter amount') }}"
                                x-mask:dynamic="$money($input, '.', ',', 2)"
                                wire:model.live="cashTendered"
                            >
                        </label>
                        <label class="col-pay-total-cell">
                            <span>{{ __('Change') }}</span>
                            <input type="text" readonly value="{{ number_format($this->getChangeAmount(), 2) }}">
                        </label>
                        <label class="col-pay-total-cell">
                            <span>{{ __('Total Payment') }}</span>
                            <input type="text" readonly value="₱{{ number_format($this->getTotalAmount(), 2) }}">
                        </label>
                    </div>
                @endif
                <div class="col-pay-settle">
                    @if ($paymentError)
                        <p style="margin: 0; font-size: 0.8125rem; font-weight: 600; color: rgb(185 28 28);">{{ $paymentError }}</p>
                    @endif
                    @php($receiptCancelled = $this->isCurrentReceiptCancelled())
                    <div class="col-pay-actions" role="group" aria-label="{{ __('Payment actions') }}">
                        <button type="button" wire:click="requestPinAction('cancelOfficialReceipt')" class="col-pay-action col-pay-action--cancel">
                            {{ __('Cancelled OR#') }}
                        </button>
                        <button type="button" wire:click="requestPinAction('deletePaymentDraft')" class="col-pay-action col-pay-action--delete">
                            {{ __('Delete payment') }}
                        </button>
                        <button type="button" wire:click="requestPinAction('updatePayment')" class="col-pay-action col-pay-action--update" @disabled($receiptCancelled)>
                            {{ __('Update payment') }}
                        </button>
                        <button type="button" wire:click="savePayment" class="col-pay-action col-pay-action--save" @disabled($receiptCancelled)>
                            {{ __('Save payment') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @if ($member && ! $this->isInvoiceMode())
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
                        @if (\App\Support\MemberCollectionAccounts::isSalaryLedger($expandedLedger['type']))
                            @php($regularView = $this->getRegularScheduleView())
                            @if ($regularView['loans']->count() > 1)
                                <label class="pos-muted" style="display: block; font-size: 0.75rem; font-weight: 600; margin-bottom: 0.375rem;">
                                    {{ __('Account') }}
                                </label>
                                <select wire:model.live="regularViewLoanId" class="pos-input" style="margin-bottom: 0.75rem; max-width: 24rem;">
                                    @foreach ($regularView['loans'] as $regularLoan)
                                        <option value="{{ $regularLoan->id }}">
                                            {{ $expandedLedger['type'] === \App\Support\MemberCollectionAccounts::TYPE_SALARY_2 ? __('Salary loan 2') : __('Salary loan 1') }}
                                            #{{ $regularLoan->id }}
                                            — ₱{{ number_format((float) $regularLoan->loan_amount, 2) }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                            @include('filament.user.regular-loan-schedule', [
                                'schedule' => $regularView['schedule'],
                                'borrowerName' => $member->name,
                                'blankAmounts' => $regularView['blankAmounts'],
                                'loan' => $regularView['loan'],
                                'paidCash' => $regularView['paidCash'],
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

        @php($collectAccount = ($this->kindChosen && $this->collectKey && is_array($accounts)) ? $this->findOpenAccount($this->collectKey) : null)
        @php($collectLedger = ($this->collectKey && is_array($accounts)) ? ($accounts['ledgers'][$this->collectKey] ?? null) : null)

        @if ($member && $collectAccount && $this->collectModalOpen)
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
                            <option value="{{ \App\Support\CharacterLoanLedgerEntries::KIND_PRINCIPAL }}">{{ __('Released amount') }}</option>
                        </select>
                    @endif

                    <label for="collection-amount" style="display: block; font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.375rem;">{{ __('Amount (₱)') }}</label>
                    <input
                        id="collection-amount"
                        type="text"
                        inputmode="decimal"
                        autocomplete="off"
                        x-on:focus="$el.select()"
                        x-on:keydown="blockNonPeso($event)"
                        x-on:input="stripNonPeso($el)"
                        wire:blur="normalizePaymentAmount"
                        wire:model.live.debounce.300ms="paymentAmount"
                        class="pos-input"
                        style="margin-bottom: 0.75rem;"
                    />

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
                        <div class="pos-muted pos-stat-label">{{ __('Remaining credit limit') }}</div>
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

        @include('filament.collection-cashier.partials.member-edit-pin-modal')

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
        @endif
    </div>

    <style>
        .col-pay { display: flex; flex-direction: column; gap: 1rem; }
        .col-pay-head { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 1rem; padding: 1rem 1.25rem; }
        .col-pay-title { margin: 0; font-size: 1.15rem; font-weight: 800; letter-spacing: 0.04em; text-transform: uppercase; color: #1e3a8a; }
        .col-pay-meta { display: grid; grid-template-columns: repeat(2, minmax(8rem, 10rem)); gap: 0.75rem; }
        .col-pay-meta span { display: block; font-size: 0.7rem; font-weight: 700; margin-bottom: 0.3rem; }
        .col-pay-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 1rem; align-items: stretch; }
        .col-pay-card { padding: 1rem 1.25rem; }
        .col-pay-card--pay { display: flex; flex-direction: column; min-height: 100%; }
        .col-pay-kicker { font-size: 0.72rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 0.75rem; }
        .col-pay-label { display: block; font-size: 0.75rem; font-weight: 600; margin-bottom: 0.375rem; }
        .col-pay-hits { list-style: none; margin: 0.5rem 0 0; padding: 0; border: 1px solid rgb(226 232 240); border-radius: 0.5rem; overflow: hidden; }
        .col-pay-hits button { width: 100%; text-align: left; padding: 0.75rem 1rem; background: transparent; border: 0; border-bottom: 1px solid rgb(226 232 240); cursor: pointer; }
        .col-pay-balance { margin: 0.85rem 0 0; font-size: 0.875rem; }
        .col-pay-chips {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 0.35rem;
            margin: 0.7rem 0 1rem;
        }
        .col-pay-chip {
            border: 1px solid rgb(203 213 225);
            background: transparent;
            color: inherit;
            border-radius: 0.45rem;
            padding: 0.4rem 0.25rem;
            font-size: 0.62rem;
            font-weight: 700;
            line-height: 1.2;
            cursor: pointer;
            text-align: center;
        }
        .dark .col-pay-chip { border-color: rgba(255,255,255,0.16); }
        .col-pay-chip.is-active { outline: 2px solid currentColor; }
        .col-pay-bill { display: flex; flex-direction: column; margin: 0.35rem 0 0.5rem; }
        .col-pay-bill-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.55rem 0 0.4rem;
        }
        .col-pay-bill-label {
            min-width: 0;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .col-pay-bill-input {
            flex: 0 0 8.5rem;
            width: 8.5rem;
            box-sizing: border-box;
            text-align: right;
            font-variant-numeric: tabular-nums;
            border: 1px solid rgb(203 213 225);
            border-radius: 999px;
            padding: 0.4rem 0.75rem;
            font-size: 0.875rem;
            background: transparent;
            color: inherit;
        }
        .dark .col-pay-bill-input {
            border-color: rgba(255, 255, 255, 0.28);
            background: transparent;
            color: inherit;
        }
        .dark .col-pay-bill-sum { border-top-color: rgba(255, 255, 255, 0.16); }
        .col-pay-bill-sum {
            display: flex;
            align-items: baseline;
            justify-content: flex-end;
            gap: 0.55rem;
            margin: 0;
            padding: 0.4rem 0 0.15rem;
            border-top: 1px solid rgb(203 213 225);
            font-size: 0.875rem;
        }
        .col-pay-bill-sum span { text-transform: lowercase; font-weight: 500; }
        .col-pay-bill-sum strong {
            flex: 0 0 8.5rem;
            width: 8.5rem;
            box-sizing: border-box;
            padding: 0 0.75rem;
            border: 1px solid transparent;
            text-align: right;
            font-variant-numeric: tabular-nums;
            font-weight: 700;
            font-size: 0.875rem;
        }
        .col-pay-settle { display: flex; flex-direction: column; gap: 0.65rem; margin-top: auto; padding-top: 1rem; }
        .col-pay-totals {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.45rem;
            padding: 0.65rem;
            border-radius: 0.65rem;
            background: #000;
            border: 2px solid #000;
            color: #fff;
        }
        .col-pay-total-cell {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: rgb(203 213 225);
        }
        .col-pay-total-cell input {
            width: 100%;
            box-sizing: border-box;
            border: 0;
            border-radius: 0.5rem;
            padding: 0.7rem 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            text-align: center;
            background: #fff;
            color: #0f172a;
        }
        .col-pay-actions {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.5rem;
        }
        .col-pay-action {
            border: 0;
            border-radius: 999px;
            padding: 0.55rem 0.35rem;
            font-size: 0.62rem;
            font-weight: 700;
            cursor: pointer;
            color: #fff;
        }
        .col-pay-action--cancel { background: #e2e8f0; color: #0f172a; }
        .col-pay-action--delete { background: #ef5b6a; }
        .col-pay-action--update { background: #f5b942; color: #1f2937; }
        .col-pay-action--save { background: #2563eb; }
        .col-pay-action:disabled { opacity: 0.4; cursor: not-allowed; }
        @media (max-width: 900px) {
            .col-pay-grid, .col-pay-meta { grid-template-columns: 1fr; }
            .col-pay-chips { grid-template-columns: repeat(5, minmax(0, 1fr)); }
            .col-pay-totals, .col-pay-actions { grid-template-columns: 1fr; }
        }
        .fi-pos-ui .pos-credit-modal { position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .fi-pos-ui .pos-credit-modal__backdrop { position: absolute; inset: 0; background: rgba(15, 23, 42, 0.55); }
        .fi-pos-ui .pos-credit-modal__dialog { position: relative; width: 100%; max-width: 22rem; padding: 1.25rem; border-radius: 0.75rem; background: #fff; box-shadow: 0 20px 45px rgba(15, 23, 42, 0.25); max-height: 90vh; overflow: auto; }
        .dark .fi-pos-ui .pos-credit-modal__dialog { background: rgb(30 41 59); color: #fff; }
        .fi-pos-ui .col-pay-saved {
            max-width: 20rem;
            padding: 1.5rem 1.35rem 1.25rem;
            text-align: center;
        }
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
        .col-pay-saved h3 {
            margin: 0 0 0.4rem;
            font-size: 1.125rem;
            font-weight: 800;
        }
        .col-pay-saved p {
            margin: 0 0 1.15rem;
            font-size: 0.875rem;
            line-height: 1.45;
            color: rgb(71 85 105);
        }
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
        .dark .fi-pos-ui ul { border-color: rgba(255,255,255,0.12) !important; }
    </style>
</x-filament-panels::page>
