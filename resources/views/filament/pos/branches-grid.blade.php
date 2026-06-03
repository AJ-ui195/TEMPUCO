@php
    use App\Filament\Pos\Pages\BranchInventoryPage;
@endphp

<div class="fi-branches-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(14rem, 1fr)); gap: 1rem;">
    @foreach ($branches as $branch)
        <div
            style="display: flex; flex-direction: column; border: 1px solid rgba(148, 163, 184, 0.35); border-radius: 0.75rem; background: rgba(255, 255, 255, 0.6); overflow: hidden;"
        >
            <button
                type="button"
                wire:click="mountAction('viewBranch', { branch: {{ $branch->id }} })"
                style="display: flex; flex: 1; flex-direction: column; align-items: flex-start; gap: 0.5rem; padding: 1rem 1.25rem; text-align: left; border: none; background: transparent; cursor: pointer; font: inherit; color: inherit; width: 100%;"
            >
                <span style="font-size: 0.75rem; font-weight: 600; color: #0284c7;">
                    {{ __('Branch :number', ['number' => $branch->branch_number]) }}
                </span>
                <span style="font-size: 0.9375rem; font-weight: 600; line-height: 1.35;">
                    {{ $branch->name }}
                </span>
                <span style="font-size: 0.8125rem; color: #64748b;">
                    {{ $branch->code }}
                </span>
                @if (! $branch->is_active)
                    <span style="font-size: 0.75rem; color: #b45309;">{{ __('Inactive') }}</span>
                @elseif (! $branch->has_pos)
                    <span style="font-size: 0.75rem; color: #64748b;">{{ __('No POS') }}</span>
                @endif
            </button>
            <div style="display: flex; flex-direction: column; gap: 0.5rem; padding: 0 1rem 1rem;">
                <a
                    href="{{ BranchInventoryPage::getUrl(['branch' => $branch->id], panel: 'inventory') }}"
                    style="display: block; width: 100%; padding: 0.5rem 0.75rem; font-size: 0.8125rem; font-weight: 600; color: #fff; background: #0284c7; border: none; border-radius: 0.5rem; cursor: pointer; text-align: center; text-decoration: none; box-sizing: border-box;"
                >
                    {{ __('View inventory') }}
                </a>
                <button
                    type="button"
                    wire:click.stop="mountAction('editBranch', { branch: {{ $branch->id }} })"
                    style="width: 100%; padding: 0.5rem 0.75rem; font-size: 0.8125rem; font-weight: 600; color: #0284c7; background: rgba(14, 165, 233, 0.08); border: 1px solid rgba(14, 165, 233, 0.25); border-radius: 0.5rem; cursor: pointer;"
                >
                    {{ __('Edit') }}
                </button>
            </div>
        </div>
    @endforeach
</div>
