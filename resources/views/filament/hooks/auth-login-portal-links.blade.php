@if (! in_array($currentPanel ?? null, ['pos', 'pos-canteen'], true))
    @php
        $panels = [
            'user' => [
                'loginLabel' => __('Member sign in'),
            ],
            'pos' => [
                'loginLabel' => __('Grocery cashier sign in'),
            ],
            'pos-canteen' => [
                'loginLabel' => __('Canteen cashier sign in'),
            ],
            'admin' => [
                'loginLabel' => __('Admin sign in'),
            ],
        ];
    @endphp

    <p class="mt-4 text-center text-sm text-gray-600 dark:text-gray-400">
        {{ __('Use a different portal?') }}
    </p>

    <ul class="mt-2 space-y-1 text-center text-sm">
        @foreach ($panels as $panelId => $panelInfo)
            @continue($panelId === ($currentPanel ?? null))
            @php($panelInstance = \Filament\Facades\Filament::getPanel($panelId, isStrict: false))
            @if ($panelInstance)
                <li>
                    <a
                        href="{{ $panelInstance->getLoginUrl() }}"
                        class="font-medium text-primary-600 underline decoration-primary-600/30 transition-colors hover:text-primary-500 hover:decoration-primary-500 dark:text-primary-400 dark:decoration-primary-400/30 dark:hover:text-primary-300 dark:hover:decoration-primary-300"
                    >
                        {{ $panelInfo['loginLabel'] }}
                    </a>
                </li>
            @endif
        @endforeach
    </ul>
@endif
