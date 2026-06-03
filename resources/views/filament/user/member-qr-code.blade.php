<div class="fi-member-qr-code mx-auto max-w-md text-center">
    <h2 class="text-xl font-semibold text-gray-950 dark:text-white">
        {{ $user->name }}
    </h2>
    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
        {{ $user->email }}
    </p>
    @if (filled($user->cellphone))
        <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ $user->cellphone }}
        </p>
    @endif

    <div style="width: 150px; height: 150px; margin: 1rem auto 0;">
        <img
            src="{{ $qrCodeDataUri }}"
            alt="{{ __('QR code for :name', ['name' => $user->name]) }}"
            width="150"
            height="150"
            style="display: block; width: 150px; height: 150px; max-width: 150px; max-height: 150px;"
        >
    </div>

    <p style="margin: 1rem 0 0; font-size: 0.875rem; color: #6b7280;">
        {{ __('Member ID') }}: {{ $user->id }}
    </p>
</div>
