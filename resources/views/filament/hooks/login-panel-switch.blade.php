@php
    /** @var string $url */
    /** @var string $message */
@endphp

<p class="fi-login-panel-switch" style="margin-top: 1rem; text-align: center; font-size: 0.875rem; line-height: 1.5;">
    <a
        href="{{ $url }}"
        style="font-weight: 600; text-decoration: underline; text-underline-offset: 0.15em; color: inherit;"
    >
        {{ $message }}
    </a>
</p>
