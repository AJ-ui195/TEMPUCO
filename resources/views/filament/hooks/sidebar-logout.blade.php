@auth
    @php
        $logoutFormId = 'sidebar-logout-form-' . filament()->getId();
        $sidebarCollapsible = filament()->isSidebarCollapsibleOnDesktop();
    @endphp

    <div
        @class([
            'border-gray-950/5 border-t dark:border-white/10',
            'px-6 py-3',
        ])
    >
        <ul class="-mx-2 flex flex-col gap-y-1">
            <li class="fi-sidebar-item fi-sidebar-item-has-url">
                <form
                    id="{{ $logoutFormId }}"
                    class="hidden"
                    action="{{ filament()->getLogoutUrl() }}"
                    method="post"
                >
                    @csrf
                </form>

                <button
                    type="submit"
                    form="{{ $logoutFormId }}"
                    x-on:click="window.matchMedia('(max-width: 1024px)').matches && $store.sidebar.close()"
                    @class([
                        'fi-sidebar-item-btn w-full cursor-pointer border-0 bg-transparent text-start font-[inherit]',
                    ])
                >
                    {{
                        \Filament\Support\generate_icon_html(
                            \Filament\Support\Icons\Heroicon::ArrowLeftEndOnRectangle,
                            \Filament\View\PanelsIconAlias::WIDGETS_ACCOUNT_LOGOUT_BUTTON,
                            (new \Illuminate\View\ComponentAttributeBag())->class(['fi-sidebar-item-icon']),
                            \Filament\Support\Enums\IconSize::Large,
                        )
                    }}

                    <span
                        @if ($sidebarCollapsible)
                            x-show="$store.sidebar.isOpen"
                            x-transition:enter="fi-transition-enter"
                            x-transition:enter-start="fi-transition-enter-start"
                            x-transition:enter-end="fi-transition-enter-end"
                        @endif
                        class="fi-sidebar-item-label"
                    >
                        {{ __('filament-panels::widgets/account-widget.actions.logout.label') }}
                    </span>
                </button>
            </li>
        </ul>
    </div>
@endauth
