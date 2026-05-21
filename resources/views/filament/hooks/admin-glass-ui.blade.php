<style>
    /* Gradient backdrop (dashboard & app pages, not login). */
    .fi-panel-admin.fi-body:has(.fi-layout) {
        background: linear-gradient(
            145deg,
            #dbeafe 0%,
            #f1f5f9 35%,
            #fef3c7 65%,
            #e2e8f0 100%
        ) !important;
    }

    html.dark .fi-panel-admin.fi-body:has(.fi-layout) {
        background: linear-gradient(
            145deg,
            #0f172a 0%,
            #1e293b 45%,
            #292524 100%
        ) !important;
    }

    .fi-panel-admin .fi-topbar {
        background: rgba(255, 255, 255, 0.72) !important;
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.5);
        box-shadow: 0 4px 24px rgba(15, 23, 42, 0.06) !important;
        --tw-ring-color: rgba(255, 255, 255, 0.4) !important;
    }

    html.dark .fi-panel-admin .fi-topbar {
        background: rgba(15, 23, 42, 0.75) !important;
        border-bottom-color: rgba(255, 255, 255, 0.08);
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.2) !important;
        --tw-ring-color: rgba(255, 255, 255, 0.1) !important;
    }

    .fi-panel-admin .fi-section:not(.fi-section-not-contained):not(.fi-aside),
    .fi-panel-admin .fi-section.fi-aside > .fi-section-content-ctn,
    .fi-panel-admin .fi-wi-stats-overview-stat,
    .fi-panel-admin .fi-ta-ctn,
    .fi-panel-admin .fi-welcome-widget {
        background: rgba(255, 255, 255, 0.55) !important;
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
        border: 1px solid rgba(255, 255, 255, 0.55) !important;
        box-shadow:
            0 8px 32px rgba(15, 23, 42, 0.08),
            inset 0 1px 0 rgba(255, 255, 255, 0.6) !important;
        --tw-ring-color: rgba(255, 255, 255, 0.35) !important;
    }

    html.dark .fi-panel-admin .fi-section:not(.fi-section-not-contained):not(.fi-aside),
    html.dark .fi-panel-admin .fi-section.fi-aside > .fi-section-content-ctn,
    html.dark .fi-panel-admin .fi-wi-stats-overview-stat,
    html.dark .fi-panel-admin .fi-ta-ctn,
    html.dark .fi-panel-admin .fi-welcome-widget {
        background: rgba(30, 41, 59, 0.55) !important;
        border-color: rgba(255, 255, 255, 0.12) !important;
        box-shadow:
            0 8px 32px rgba(0, 0, 0, 0.25),
            inset 0 1px 0 rgba(255, 255, 255, 0.08) !important;
        --tw-ring-color: rgba(255, 255, 255, 0.1) !important;
    }

    .fi-panel-admin .fi-welcome-widget .fi-account-widget-main {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
    }

    .fi-panel-admin .fi-wi-stats-overview-stat-chart .fi-wi-stats-overview-stat-chart-bg-color {
        opacity: 0.35;
    }
</style>
