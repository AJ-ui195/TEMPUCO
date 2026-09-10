<div style="display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.9375rem; line-height: 1.5;">
    <div>
        <strong>{{ __('Branch number') }}</strong><br>
        {{ $branch->branch_number }}
    </div>
    <div>
        <strong>{{ __('Branch code') }}</strong><br>
        {{ $branch->code }}
    </div>
    @if (filled($branch->phone))
        <div>
            <strong>{{ __('Phone') }}</strong><br>
            {{ $branch->phone }}
        </div>
    @endif
    @if (filled($branch->branch_holder))
        <div>
            <strong>{{ __('Branch holder') }}</strong><br>
            {{ $branch->branch_holder }}
        </div>
    @endif
    <div>
        <strong>{{ __('Status') }}</strong><br>
        {{ $branch->is_active ? __('Active') : __('Inactive') }}
    </div>
    <div>
        <strong>{{ __('POS') }}</strong><br>
        {{ $branch->has_pos ? __('Enabled') : __('Not available (head office)') }}
    </div>
    @php
        $stock = (new \App\Support\BranchStockReport($branch))->stockLeftTotals();
        $moved = (new \App\Support\BranchStockReport($branch))->transferTotals();
    @endphp
    <div>
        <strong>{{ __('On hand') }}</strong><br>
        {{ __(':products products · :units units left', [
            'products' => number_format($stock['products']),
            'units' => number_format($stock['units']),
        ]) }}
    </div>
    <div>
        <strong>{{ __('Transferred') }}</strong><br>
        {{ __(':products products · :units units sent', [
            'products' => number_format($moved['products']),
            'units' => number_format($moved['units']),
        ]) }}
    </div>
    <div style="padding-top: 0.5rem;">
        <a
            href="{{ \App\Filament\Pos\Pages\BranchInventoryPage::getUrl(['branch' => $branch->id], panel: 'pos') }}"
            wire:navigate
            style="display: block; width: 100%; padding: 0.625rem 0.875rem; font-size: 0.875rem; font-weight: 600; color: #fff; background: #0284c7; border: none; border-radius: 0.5rem; cursor: pointer; text-align: center; text-decoration: none; box-sizing: border-box;"
        >
            {{ __('View inventory') }}
        </a>
    </div>
</div>
