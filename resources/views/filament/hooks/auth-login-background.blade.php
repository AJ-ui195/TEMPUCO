<style>
    .fi-body:has(.fi-simple-layout) {
        background-color: transparent;
    }

    .fi-simple-layout {
        position: relative;
        background-image: url("{{ asset('images/BGPic.jpg') }}");
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        background-attachment: fixed;
    }

    .fi-simple-layout::before {
        content: '';
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.4);
        pointer-events: none;
        z-index: 0;
    }

    .fi-simple-layout > * {
        position: relative;
        z-index: 1;
    }

    .fi-simple-main {
        background-color: rgba(255, 255, 255, 0.2) !important;
        backdrop-filter: blur(5px);
        -webkit-backdrop-filter: blur(5px);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
        --tw-ring-color: rgba(255, 255, 255, 0.35);
    }

    html.dark .fi-simple-main {
        background-color: rgba(17, 24, 39, 0.45) !important;
        --tw-ring-color: rgba(255, 255, 255, 0.15);
    }

    /* Login fields use Filament rings, which inherit the faint glass --tw-ring-color. */
    .fi-simple-layout .fi-input-wrp {
        border: 2px solid rgb(251 191 36) !important;
        background-color: rgba(255, 255, 255, 0.95) !important;
        box-shadow: none !important;
        --tw-ring-shadow: 0 0 #0000 !important;
        --tw-ring-color: rgb(251 191 36) !important;
    }

    html.dark .fi-simple-layout .fi-input-wrp {
        background-color: rgba(15, 23, 42, 0.88) !important;
        border-color: rgb(251 191 36) !important;
    }

    .fi-simple-layout .fi-input-wrp:focus-within {
        border-color: rgb(245 158 11) !important;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.35) !important;
    }

    /* MFA 6-digit boxes */
    .fi-simple-layout .fi-one-time-code-input-ctn {
        height: 3.5rem;
        padding: 0.25rem 0.35rem;
        border: 2px solid rgb(251 191 36);
        border-radius: 0.75rem;
        background-color: rgba(15, 23, 42, 0.4);
        box-sizing: content-box;
    }

    .fi-simple-layout .fi-one-time-code-input-ctn > .fi-one-time-code-input-digit-field {
        background-color: #ffffff !important;
        border: 2px solid rgb(217 119 6) !important;
        box-shadow: none !important;
    }

    .fi-simple-layout .fi-one-time-code-input-ctn > .fi-one-time-code-input-digit-field.fi-active {
        border-color: rgb(245 158 11) !important;
        box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.45) !important;
    }

    .fi-simple-layout input[type='text'].fi-one-time-code-input {
        color: #0f172a !important;
        -webkit-text-fill-color: #0f172a !important;
        font-size: 1.35rem;
        font-weight: 700;
        caret-color: rgb(217 119 6);
    }
</style>
