<p class="mt-4 text-center text-sm text-gray-600 dark:text-gray-400">
    {{ __('Signing in with a member account? Use the member portal.') }}
    <a
        href="{{ \Filament\Facades\Filament::getPanel('user')->getLoginUrl() }}"
        class="font-medium text-primary-600 underline decoration-primary-600/30 transition-colors hover:text-primary-500 hover:decoration-primary-500 dark:text-primary-400 dark:decoration-primary-400/30 dark:hover:text-primary-300 dark:hover:decoration-primary-300"
    >
        {{ __('Member sign in') }}
    </a>
</p>
