<x-filament-panels::page>
    <div class="sl-hub">
        <div class="sl-acc" role="navigation" aria-label="{{ __('Salary loan sections') }}">
            <div @class(['sl-acc-card', 'is-open' => $openSection === 'regular'])>
                <button
                    type="button"
                    class="sl-acc-head"
                    wire:click="expandSection('regular')"
                    aria-expanded="{{ $openSection === 'regular' ? 'true' : 'false' }}"
                >
                    <span class="sl-acc-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <rect x="3" y="6" width="18" height="13" rx="2" />
                            <path d="M3 10h18M8 6V4h8v2" />
                        </svg>
                    </span>
                    <span class="sl-acc-title">{{ __('Regular loan') }}</span>
                    <span class="sl-acc-chevron" aria-hidden="true">{{ $openSection === 'regular' ? '˅' : '›' }}</span>
                </button>
                <div class="sl-acc-panel">
                    <div class="sl-acc-panel-inner">
                        <button
                            type="button"
                            @class(['sl-acc-sub', 'is-active' => $selected === 'regular-1'])
                            wire:click="selectSalaryLoan('regular-1')"
                        >
                            {{ __('Salary Loan 1') }}
                        </button>
                        <button
                            type="button"
                            @class(['sl-acc-sub', 'is-active' => $selected === 'regular-2'])
                            wire:click="selectSalaryLoan('regular-2')"
                        >
                            {{ __('Salary Loan 2') }}
                        </button>
                    </div>
                </div>
            </div>

            <div @class(['sl-acc-card', 'is-open' => $openSection === 'individual'])>
                <button
                    type="button"
                    class="sl-acc-head"
                    wire:click="expandSection('individual')"
                    aria-expanded="{{ $openSection === 'individual' ? 'true' : 'false' }}"
                >
                    <span class="sl-acc-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M7 3h10a2 2 0 0 1 2 2v16l-7-3-7 3V5a2 2 0 0 1 2-2Z" />
                            <path d="M9 8h6M9 12h6" />
                        </svg>
                    </span>
                    <span class="sl-acc-title">{{ __('Individual ledger') }}</span>
                    <span class="sl-acc-chevron" aria-hidden="true">{{ $openSection === 'individual' ? '˅' : '›' }}</span>
                </button>
                <div class="sl-acc-panel">
                    <div class="sl-acc-panel-inner">
                        <button
                            type="button"
                            @class(['sl-acc-sub', 'is-active' => $selected === 'individual-1'])
                            wire:click="selectSalaryLoan('individual-1')"
                        >
                            {{ __('Salary Loan 1') }}
                        </button>
                        <button
                            type="button"
                            @class(['sl-acc-sub', 'is-active' => $selected === 'individual-2'])
                            wire:click="selectSalaryLoan('individual-2')"
                        >
                            {{ __('Salary Loan 2') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="sl-hub-body">
            {!! $this->accordionLedgerHtml() !!}
        </div>
    </div>

    <style>
        .sl-hub {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            max-width: 72rem;
        }

        .sl-acc {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            align-items: start;
            gap: 0.75rem;
            width: 100%;
            max-width: 52rem;
        }

        .sl-acc-card {
            background: #fff;
            border: 1px solid #bfdbfe;
            border-radius: 0.9rem;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .sl-acc-head {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.85rem 1rem;
            border: 0;
            background: linear-gradient(180deg, #1e3a8a 0%, #1d4ed8 100%);
            color: #fff;
            cursor: pointer;
            text-align: left;
        }

        .sl-acc-card.is-open .sl-acc-head {
            background: linear-gradient(180deg, #0f172a 0%, #1e3a8a 100%);
        }

        .sl-acc-icon {
            width: 1.65rem;
            height: 1.65rem;
            display: grid;
            place-items: center;
            border-radius: 0.45rem;
            background: rgba(255, 255, 255, 0.14);
            flex-shrink: 0;
        }

        .sl-acc-icon svg {
            width: 1.05rem;
            height: 1.05rem;
        }

        .sl-acc-title {
            flex: 1;
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .sl-acc-chevron {
            font-size: 1.1rem;
            font-weight: 700;
            line-height: 1;
            opacity: 0.92;
        }

        .sl-acc-panel {
            display: grid;
            grid-template-rows: 0fr;
            transition: grid-template-rows 220ms ease;
            background: #eff6ff;
        }

        .sl-acc-card.is-open .sl-acc-panel {
            grid-template-rows: 1fr;
        }

        .sl-acc-panel-inner {
            overflow: hidden;
            display: flex;
            flex-direction: column;
            padding: 0 0.5rem;
        }

        .sl-acc-card.is-open .sl-acc-panel-inner {
            padding: 0.4rem 0.5rem 0.55rem;
        }

        .sl-acc-sub {
            border: 0;
            background: transparent;
            color: #1e3a8a;
            text-align: left;
            padding: 0.55rem 0.85rem 0.55rem 1.35rem;
            border-radius: 0.55rem;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
        }

        .sl-acc-sub:hover {
            background: rgba(29, 78, 216, 0.1);
        }

        .sl-acc-sub.is-active {
            background: #fff;
            color: #0f172a;
            box-shadow: 0 1px 4px rgba(15, 23, 42, 0.08);
        }
    </style>
</x-filament-panels::page>
