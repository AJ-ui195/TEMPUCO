<style>
    :root {
        --fi-sidebar-dark-bg: #0f172a;
        --fi-sidebar-dark-bg-hover: rgba(255, 255, 255, 0.08);
        --fi-sidebar-dark-bg-active: rgba(255, 255, 255, 0.12);
        --fi-sidebar-dark-text: #e2e8f0;
        --fi-sidebar-dark-text-muted: #94a3b8;
        --fi-sidebar-dark-border: rgba(255, 255, 255, 0.1);
    }

    .fi-sidebar,
    .fi-sidebar.fi-sidebar-open {
        background-color: var(--fi-sidebar-dark-bg) !important;
        --tw-ring-color: var(--fi-sidebar-dark-border);
    }

    .fi-body-has-topbar .fi-sidebar,
    .fi-body-has-topbar .fi-sidebar-header {
        background-color: var(--fi-sidebar-dark-bg) !important;
        --tw-ring-color: var(--fi-sidebar-dark-border);
    }

    .fi-sidebar-header {
        background-color: var(--fi-sidebar-dark-bg) !important;
    }

    .fi-sidebar-item-label,
    .fi-sidebar-database-notifications-btn-label {
        color: var(--fi-sidebar-dark-text) !important;
    }

    .fi-sidebar-group-label {
        color: var(--fi-sidebar-dark-text-muted) !important;
    }

    .fi-sidebar-item-btn > .fi-icon,
    .fi-sidebar-group-btn > .fi-icon,
    .fi-sidebar-database-notifications-btn > .fi-icon {
        color: var(--fi-sidebar-dark-text-muted) !important;
    }

    .fi-sidebar-item.fi-sidebar-item-has-url > .fi-sidebar-item-btn:hover,
    .fi-sidebar-item.fi-sidebar-item-has-url > .fi-sidebar-item-btn:focus-visible,
    .fi-sidebar-database-notifications-btn:hover,
    .fi-sidebar-database-notifications-btn:focus-visible,
    .fi-sidebar-group-dropdown-trigger-btn:hover,
    .fi-sidebar-group-dropdown-trigger-btn:focus-visible {
        background-color: var(--fi-sidebar-dark-bg-hover) !important;
    }

    .fi-sidebar-item.fi-active > .fi-sidebar-item-btn {
        background-color: var(--fi-sidebar-dark-bg-active) !important;
    }

    .fi-sidebar-footer {
        border-color: var(--fi-sidebar-dark-border);
    }

    .fi-sidebar [class*='border-gray-950'],
    .fi-sidebar [class*='border-white'] {
        border-color: var(--fi-sidebar-dark-border) !important;
    }
</style>
