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
</style>
