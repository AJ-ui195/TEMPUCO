<p class="mt-4 text-center text-sm text-gray-600 dark:text-gray-400">
    {{ __('Administrator? The control panel uses a separate sign-in page.') }}
    <a
        href="{{ \Filament\Facades\Filament::getPanel('admin')->getLoginUrl() }}"
        class="font-medium text-primary-600 underline decoration-primary-600/30 transition-colors hover:text-primary-500 hover:decoration-primary-500 dark:text-primary-400 dark:decoration-primary-400/30 dark:hover:text-primary-300 dark:hover:decoration-primary-300"
    >
        {{ __('Admin sign in') }}
    </a>
</p>
