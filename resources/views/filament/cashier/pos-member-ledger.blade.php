<x-filament-panels::page>
    @include('filament.cashier.partials.pos-ui-styles')

    @php
        $member = $this->getSelectedMember();
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
            <div class="pos-panel" style="padding: 0.75rem 1rem; margin-bottom: 1rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem;">
                <span style="font-weight: 700; font-size: 0.9375rem;">{{ __('Individual ledger') }}</span>
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

            @include('filament.user.canteen-credit-ledger', [
                'user' => $member,
                'entries' => (new \App\Support\MemberCreditLedger($member))->entries(
                    $this->getChannelFilter(),
                    $this->getFromDate(),
                    $this->getToDate(),
                ),
            ])
        @endif
    </div>
</x-filament-panels::page>
