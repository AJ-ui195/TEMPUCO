<x-filament-widgets::widget class="fi-welcome-widget">
    <div class="fi-account-widget-main">
        <h2 class="fi-account-widget-heading">
            {{ __('filament-panels::widgets/account-widget.welcome', ['app' => config('app.name')]) }}
        </h2>

        <p class="fi-account-widget-user-name">
            {{ filament()->getUserName(filament()->auth()->user()) }}
        </p>
    </div>
</x-filament-widgets::widget>
