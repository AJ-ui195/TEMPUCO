<x-filament-panels::page>
    @include('filament.cashier.partials.pos-ui-styles')

    @php
        $member = $this->getSelectedMember();
        $entries = $this->getLedgerEntries();
        $summary = $this->getLedgerSummary();
        $results = $this->getMemberSearchResults();
    @endphp

    <div class="fi-pos-ui">
        <div class="pos-panel" style="padding: 1rem 1.25rem; margin-bottom: 1rem;">
            <h2 style="margin: 0; font-size: 1.125rem; font-weight: 700;">{{ __('Member ledger') }}</h2>
            <p class="pos-muted" style="margin: 0.375rem 0 1rem; font-size: 0.8125rem;">
                {{ __('Every credit charge and payment for one member, oldest balance carried forward.') }}
            </p>

            @if ($member)
                <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem;">
                    <div>
                        <div style="font-weight: 700; font-size: 1rem;">{{ $member->name }}</div>
                        <div class="pos-muted" style="font-size: 0.8125rem; margin-top: 0.125rem;">
                            {{ $member->email }}
                            @if ($member->cellphone)
                                · {{ $member->cellphone }}
                            @endif
                        </div>
                    </div>
                    <button type="button" wire:click="clearMember" class="pos-btn-secondary" style="width: auto; padding: 0.5rem 0.875rem; font-size: 0.8125rem;">
                        {{ __('Choose another member') }}
                    </button>
                </div>
            @else
                <label for="ledger-member-search" class="pos-muted" style="display: block; font-size: 0.75rem; font-weight: 600; margin-bottom: 0.375rem;">
                    {{ __('Search member') }}
                </label>
                <input
                    id="ledger-member-search"
                    type="search"
                    wire:model.live.debounce.300ms="memberSearch"
                    autocomplete="off"
                    placeholder="{{ __('Name, email, or phone…') }}"
                    class="pos-input"
                />

                @if (strlen(trim($memberSearch)) >= 2)
                    <div class="pos-panel" style="margin-top: 0.75rem; max-height: 16rem; overflow-y: auto; padding: 0;">
                        @forelse ($results as $result)
                            <button type="button" wire:click="selectMember({{ $result->id }})" class="pos-search-result">
                                <span>
                                    <span style="display: block; font-weight: 600; font-size: 0.875rem;">{{ $result->name }}</span>
                                    <span class="pos-muted" style="font-size: 0.75rem;">
                                        {{ $result->email }}
                                        @if ($result->cellphone)
                                            · {{ $result->cellphone }}
                                        @endif
                                    </span>
                                </span>
                            </button>
                        @empty
                            <p class="pos-muted" style="margin: 0; padding: 1rem 0.75rem; font-size: 0.8125rem; text-align: center;">
                                {{ __('No members match your search.') }}
                            </p>
                        @endforelse
                    </div>
                @endif
            @endif
        </div>

        <div class="pos-panel" style="padding: 1rem 1.25rem; margin-bottom: 1rem;">
            <div style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 0.75rem;">
                <div>
                    <label for="ledger-from-date" class="pos-muted" style="display: block; font-size: 0.75rem; font-weight: 600; margin-bottom: 0.375rem;">
                        {{ __('From date') }}
                    </label>
                    <input id="ledger-from-date" type="date" wire:model.live="fromDate" class="pos-input" style="width: auto;" />
                </div>
                <div>
                    <label for="ledger-to-date" class="pos-muted" style="display: block; font-size: 0.75rem; font-weight: 600; margin-bottom: 0.375rem;">
                        {{ __('To date') }}
                    </label>
                    <input id="ledger-to-date" type="date" wire:model.live="toDate" class="pos-input" style="width: auto;" />
                </div>

                @if ($fromDate !== '' || $toDate !== '')
                    <button type="button" wire:click="clearDates" class="pos-btn-secondary" style="width: auto; padding: 0.5rem 0.875rem; font-size: 0.8125rem;">
                        {{ __('Clear dates') }}
                    </button>
                @endif

                <div style="margin-inline-start: auto; display: flex; flex-wrap: wrap; gap: 0.5rem;">
                    @if ($member)
                        <a href="{{ $this->getPrintMemberUrl() }}" target="_blank" rel="noopener" class="pos-btn-secondary" style="width: auto; padding: 0.5rem 0.875rem; font-size: 0.8125rem; text-decoration: none;">
                            {{ __('Print this ledger') }}
                        </a>
                    @endif
                    <a href="{{ $this->getPrintAllUrl() }}" target="_blank" rel="noopener" class="pos-btn-secondary" style="width: auto; padding: 0.5rem 0.875rem; font-size: 0.8125rem; text-decoration: none;">
                        {{ __('Print all member ledgers (:count)', ['count' => $this->getMembersWithActivityCount()]) }}
                    </a>
                </div>
            </div>

            <p class="pos-muted" style="margin: 0.75rem 0 0; font-size: 0.75rem;">
                {{ __('Showing :range. Printing uses the same date and channel filters.', ['range' => $this->getRangeLabel()]) }}
            </p>
        </div>

        @if ($member)
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                @if ($summary['opening'] != 0.0)
                    <div class="pos-panel pos-stat">
                        <div class="pos-muted pos-stat-label">{{ __('Balance forward') }}</div>
                        <div class="pos-stat-value">₱{{ number_format($summary['opening'], 2) }}</div>
                    </div>
                @endif
                <div class="pos-panel pos-stat">
                    <div class="pos-muted pos-stat-label">{{ __('Total charged') }}</div>
                    <div class="pos-stat-value">₱{{ number_format($summary['charged'], 2) }}</div>
                </div>
                <div class="pos-panel pos-stat">
                    <div class="pos-muted pos-stat-label">{{ __('Total paid') }}</div>
                    <div class="pos-stat-value" style="color: rgb(3 105 161);">₱{{ number_format($summary['paid'], 2) }}</div>
                </div>
                <div class="pos-panel pos-stat">
                    <div class="pos-muted pos-stat-label">{{ __('Balance') }}</div>
                    <div class="pos-stat-value" style="color: {{ $summary['balance'] > 0 ? 'rgb(4 120 87)' : 'rgb(100 116 139)' }};">
                        ₱{{ number_format($summary['balance'], 2) }}
                    </div>
                </div>
            </div>

            <div class="pos-panel" style="padding: 0; overflow: hidden;">
                <div class="pos-cart-header" style="padding: 0.75rem 1rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem;">
                    <span style="font-weight: 700; font-size: 0.9375rem;">{{ __('Account activity') }}</span>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.375rem;">
                        <button
                            type="button"
                            wire:click="$set('channelFilter', '')"
                            @class(['pos-period-btn', 'pos-period-btn--active' => $channelFilter === ''])
                        >
                            {{ __('All') }}
                        </button>
                        @foreach (\App\Enums\PosSaleChannel::cases() as $channel)
                            <button
                                type="button"
                                wire:click="$set('channelFilter', '{{ $channel->value }}')"
                                @class(['pos-period-btn', 'pos-period-btn--active' => $channelFilter === $channel->value])
                            >
                                {{ $channel->getLabel() }}
                            </button>
                        @endforeach
                    </div>
                </div>

                @if ($entries->isEmpty())
                    <p class="pos-muted" style="margin: 0; padding: 2.5rem 1rem; text-align: center; font-size: 0.875rem;">
                        {{ __('No credit charges or payments for this member in :range.', ['range' => $this->getRangeLabel()]) }}
                    </p>
                @else
                    <div style="overflow-x: auto;">
                        <table class="pos-table" style="width: 100%; border-collapse: collapse; font-size: 0.8125rem;">
                            <thead>
                                <tr style="text-align: left;">
                                    <th style="padding: 0.5rem 0.75rem;">{{ __('Date') }}</th>
                                    <th style="padding: 0.5rem 0.75rem;">{{ __('Details') }}</th>
                                    <th style="padding: 0.5rem 0.75rem;">{{ __('Channel') }}</th>
                                    <th style="padding: 0.5rem 0.75rem; text-align: end;">{{ __('Charge') }}</th>
                                    <th style="padding: 0.5rem 0.75rem; text-align: end;">{{ __('Payment') }}</th>
                                    <th style="padding: 0.5rem 0.75rem; text-align: end;">{{ __('Balance') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($entries as $entry)
                                    <tr>
                                        <td class="pos-muted" style="padding: 0.5rem 0.75rem; white-space: nowrap;">
                                            {{ \App\Support\PhilippineTime::format($entry['date']) }}
                                        </td>
                                        <td style="padding: 0.5rem 0.75rem;">
                                            <span style="font-weight: 600;">{{ $entry['description'] }}</span>
                                            <span class="pos-muted" style="display: block; font-size: 0.6875rem;">{{ $entry['reference'] }}</span>
                                        </td>
                                        <td style="padding: 0.5rem 0.75rem;">{{ $entry['channel']?->getLabel() ?? '—' }}</td>
                                        <td style="padding: 0.5rem 0.75rem; text-align: end;">
                                            {{ $entry['charge'] > 0 ? '₱'.number_format($entry['charge'], 2) : '—' }}
                                        </td>
                                        <td style="padding: 0.5rem 0.75rem; text-align: end; color: {{ $entry['payment'] > 0 ? 'rgb(3 105 161)' : 'inherit' }};">
                                            {{ $entry['payment'] > 0 ? '₱'.number_format($entry['payment'], 2) : '—' }}
                                        </td>
                                        <td style="padding: 0.5rem 0.75rem; text-align: end; font-weight: 700;">
                                            ₱{{ number_format($entry['balance'], 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                                @if ($summary['opening'] != 0.0)
                                    <tr>
                                        <td class="pos-muted" style="padding: 0.5rem 0.75rem; white-space: nowrap;">—</td>
                                        <td colspan="2" style="padding: 0.5rem 0.75rem; font-weight: 600;">{{ __('Balance forward') }}</td>
                                        <td style="padding: 0.5rem 0.75rem; text-align: end;">—</td>
                                        <td style="padding: 0.5rem 0.75rem; text-align: end;">—</td>
                                        <td style="padding: 0.5rem 0.75rem; text-align: end; font-weight: 700;">
                                            ₱{{ number_format($summary['opening'], 2) }}
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                            <tfoot>
                                <tr style="border-top: 2px solid rgb(203 213 225); font-weight: 700;">
                                    <td colspan="3" style="padding: 0.75rem; text-align: end;">{{ __('Totals') }}</td>
                                    <td style="padding: 0.75rem; text-align: end;">₱{{ number_format($summary['charged'], 2) }}</td>
                                    <td style="padding: 0.75rem; text-align: end;">₱{{ number_format($summary['paid'], 2) }}</td>
                                    <td style="padding: 0.75rem; text-align: end;">₱{{ number_format($summary['balance'], 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>
