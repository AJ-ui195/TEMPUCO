<style>
    /* ── Members Portal: gradient backdrop ── */
    .fi-panel-user.fi-body:has(.fi-layout) {
        background: linear-gradient(
            155deg,
            #e0f2fe 0%,
            #f0f9ff 28%,
            #f8fafc 55%,
            #e0f2fe 100%
        ) !important;
    }

    html.dark .fi-panel-user.fi-body:has(.fi-layout) {
        background: linear-gradient(
            155deg,
            #0c4a6e 0%,
            #0f172a 40%,
            #1e293b 100%
        ) !important;
    }

    /* ── Topbar glass ── */
    .fi-panel-user .fi-topbar {
        background: rgba(255, 255, 255, 0.78) !important;
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border-bottom: 1px solid rgba(14, 165, 233, 0.12);
        box-shadow: 0 4px 24px rgba(14, 116, 144, 0.06) !important;
        --tw-ring-color: rgba(255, 255, 255, 0.45) !important;
    }

    html.dark .fi-panel-user .fi-topbar {
        background: rgba(15, 23, 42, 0.82) !important;
        border-bottom-color: rgba(56, 189, 248, 0.12);
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.25) !important;
        --tw-ring-color: rgba(255, 255, 255, 0.08) !important;
    }

    /* ── Sidebar accent (sky active state) ── */
    .fi-panel-user .fi-sidebar-item.fi-active > .fi-sidebar-item-btn {
        background: rgba(14, 165, 233, 0.14) !important;
        box-shadow: inset 3px 0 0 #0ea5e9;
    }

    .fi-panel-user .fi-sidebar-item.fi-active .fi-sidebar-item-label,
    .fi-panel-user .fi-sidebar-item.fi-active > .fi-sidebar-item-btn > .fi-icon {
        color: #38bdf8 !important;
    }

    /* ── Glass sections & tables ── */
    .fi-panel-user .fi-section:not(.fi-section-not-contained):not(.fi-aside),
    .fi-panel-user .fi-section.fi-aside > .fi-section-content-ctn,
    .fi-panel-user .fi-ta-ctn {
        background: rgba(255, 255, 255, 0.62) !important;
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
        border: 1px solid rgba(255, 255, 255, 0.65) !important;
        box-shadow:
            0 8px 32px rgba(14, 116, 144, 0.07),
            inset 0 1px 0 rgba(255, 255, 255, 0.7) !important;
        --tw-ring-color: rgba(255, 255, 255, 0.4) !important;
    }

    html.dark .fi-panel-user .fi-section:not(.fi-section-not-contained):not(.fi-aside),
    html.dark .fi-panel-user .fi-section.fi-aside > .fi-section-content-ctn,
    html.dark .fi-panel-user .fi-ta-ctn {
        background: rgba(30, 41, 59, 0.58) !important;
        border-color: rgba(56, 189, 248, 0.12) !important;
        box-shadow:
            0 8px 32px rgba(0, 0, 0, 0.28),
            inset 0 1px 0 rgba(255, 255, 255, 0.06) !important;
        --tw-ring-color: rgba(255, 255, 255, 0.08) !important;
    }

    .fi-panel-user .fi-section:not(.fi-section-not-contained):not(.fi-aside),
    .fi-panel-user .fi-section.fi-aside > .fi-section-content-ctn,
    .fi-panel-user .fi-section-content-ctn,
    .fi-panel-user .fi-section-content {
        overflow: visible;
    }

    .fi-panel-user .fi-main-ctn {
        max-width: 72rem;
    }

    /* ── Member Portal component library ── */
    .mp-stack {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .mp-hero {
        position: relative;
        overflow: hidden;
        border-radius: 1.25rem;
        padding: 1.75rem 1.5rem;
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 45%, #0c4a6e 100%);
        color: #fff;
        box-shadow:
            0 20px 40px rgba(2, 132, 199, 0.28),
            inset 0 1px 0 rgba(255, 255, 255, 0.2);
    }

    @media (min-width: 640px) {
        .mp-hero {
            padding: 2rem 2.25rem;
        }
    }

    .mp-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            radial-gradient(circle at 100% 0%, rgba(255, 255, 255, 0.18) 0%, transparent 45%),
            radial-gradient(circle at 0% 100%, rgba(56, 189, 248, 0.25) 0%, transparent 50%);
        pointer-events: none;
    }

    .mp-hero > * {
        position: relative;
        z-index: 1;
    }

    .mp-hero-eyebrow {
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.75);
    }

    .mp-hero-title {
        margin-top: 0.375rem;
        font-size: 1.625rem;
        font-weight: 700;
        line-height: 1.25;
        letter-spacing: -0.02em;
    }

    @media (min-width: 640px) {
        .mp-hero-title {
            font-size: 1.875rem;
        }
    }

    .mp-hero-subtitle {
        margin-top: 0.5rem;
        max-width: 36rem;
        font-size: 0.9375rem;
        line-height: 1.6;
        color: rgba(255, 255, 255, 0.82);
    }

    .mp-hero-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem 1rem;
        margin-top: 1.25rem;
        font-size: 0.8125rem;
        color: rgba(255, 255, 255, 0.78);
    }

    .mp-hero-meta span {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        max-width: 100%;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .mp-stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 10.5rem), 1fr));
        gap: 1rem;
    }

    .mp-stat-card {
        border-radius: 1rem;
        padding: 1.125rem 1.25rem;
        background: rgba(255, 255, 255, 0.72);
        border: 1px solid rgba(14, 165, 233, 0.14);
        box-shadow: 0 4px 16px rgba(14, 116, 144, 0.06);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    html.dark .mp-stat-card {
        background: rgba(30, 41, 59, 0.72);
        border-color: rgba(56, 189, 248, 0.16);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
    }

    .mp-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(14, 116, 144, 0.12);
    }

    html.dark .mp-stat-card:hover {
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
    }

    .mp-stat-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .mp-stat-label {
        font-size: 0.8125rem;
        font-weight: 600;
        color: #0369a1;
    }

    html.dark .mp-stat-label {
        color: #7dd3fc;
    }

    .mp-stat-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 0.625rem;
        background: rgba(14, 165, 233, 0.12);
        color: #0284c7;
        flex-shrink: 0;
    }

    html.dark .mp-stat-icon {
        background: rgba(56, 189, 248, 0.15);
        color: #38bdf8;
    }

    .mp-stat-value {
        margin-top: 0.625rem;
        font-size: 1.625rem;
        font-weight: 700;
        letter-spacing: -0.02em;
        color: #0f172a;
        line-height: 1.2;
    }

    html.dark .mp-stat-value {
        color: #f8fafc;
    }

    .mp-stat-hint {
        margin-top: 0.375rem;
        font-size: 0.75rem;
        color: #64748b;
    }

    html.dark .mp-stat-hint {
        color: #94a3b8;
    }

    .mp-section-title {
        font-size: 1rem;
        font-weight: 700;
        letter-spacing: -0.01em;
        color: #0f172a;
    }

    html.dark .mp-section-title {
        color: #f1f5f9;
    }

    .mp-section-desc {
        margin-top: 0.25rem;
        font-size: 0.875rem;
        color: #64748b;
    }

    html.dark .mp-section-desc {
        color: #94a3b8;
    }

    .mp-quick-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 13rem), 1fr));
        gap: 0.875rem;
    }

    .mp-quick-link {
        display: flex;
        align-items: flex-start;
        gap: 0.875rem;
        padding: 1rem 1.125rem;
        border-radius: 1rem;
        text-decoration: none;
        background: rgba(255, 255, 255, 0.72);
        border: 1px solid rgba(14, 165, 233, 0.12);
        box-shadow: 0 2px 12px rgba(14, 116, 144, 0.05);
        transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    }

    html.dark .mp-quick-link {
        background: rgba(30, 41, 59, 0.65);
        border-color: rgba(56, 189, 248, 0.14);
    }

    .mp-quick-link:hover {
        transform: translateY(-2px);
        border-color: rgba(14, 165, 233, 0.35);
        box-shadow: 0 8px 24px rgba(14, 116, 144, 0.12);
    }

    html.dark .mp-quick-link:hover {
        border-color: rgba(56, 189, 248, 0.35);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
    }

    .mp-quick-link-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.75rem;
        background: linear-gradient(135deg, #0ea5e9, #0284c7);
        color: #fff;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(14, 165, 233, 0.35);
    }

    .mp-quick-link-title {
        font-size: 0.9375rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.3;
    }

    html.dark .mp-quick-link-title {
        color: #f1f5f9;
    }

    .mp-quick-link-desc {
        margin-top: 0.25rem;
        font-size: 0.8125rem;
        line-height: 1.45;
        color: #64748b;
    }

    html.dark .mp-quick-link-desc {
        color: #94a3b8;
    }

    .mp-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.375rem;
        padding: 0.5rem 0.875rem;
        font-size: 0.8125rem;
        font-weight: 600;
        border-radius: 0.625rem;
        cursor: pointer;
        transition: background 0.15s ease, transform 0.15s ease, box-shadow 0.15s ease;
        border: none;
        text-decoration: none;
    }

    .mp-btn:active {
        transform: scale(0.98);
    }

    .mp-btn-primary {
        color: #fff;
        background: linear-gradient(135deg, #0ea5e9, #0284c7);
        box-shadow: 0 4px 14px rgba(14, 165, 233, 0.35);
    }

    .mp-btn-primary:hover {
        background: linear-gradient(135deg, #38bdf8, #0ea5e9);
        box-shadow: 0 6px 18px rgba(14, 165, 233, 0.45);
    }

    .mp-btn-secondary {
        color: #0369a1;
        background: rgba(14, 165, 233, 0.1);
        border: 1px solid rgba(14, 165, 233, 0.22);
    }

    html.dark .mp-btn-secondary {
        color: #7dd3fc;
        background: rgba(56, 189, 248, 0.1);
        border-color: rgba(56, 189, 248, 0.22);
    }

    .mp-btn-secondary:hover {
        background: rgba(14, 165, 233, 0.16);
    }

    .mp-credit-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 14rem), 1fr));
        gap: 1rem;
    }

    .mp-credit-card {
        border-radius: 1rem;
        padding: 1.25rem;
        background: rgba(255, 255, 255, 0.75);
        border: 1px solid rgba(14, 165, 233, 0.14);
        box-shadow: 0 4px 16px rgba(14, 116, 144, 0.06);
    }

    html.dark .mp-credit-card {
        background: rgba(30, 41, 59, 0.72);
        border-color: rgba(56, 189, 248, 0.14);
    }

    .mp-total-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.25rem;
        border-radius: 0.875rem;
        background: linear-gradient(135deg, rgba(14, 165, 233, 0.12), rgba(2, 132, 199, 0.08));
        border: 1px solid rgba(14, 165, 233, 0.2);
        font-weight: 700;
        color: #0f172a;
    }

    html.dark .mp-total-bar {
        color: #f1f5f9;
        background: linear-gradient(135deg, rgba(56, 189, 248, 0.12), rgba(2, 132, 199, 0.1));
        border-color: rgba(56, 189, 248, 0.2);
    }

    .mp-total-bar-value {
        font-size: 1.125rem;
        color: #0284c7;
    }

    html.dark .mp-total-bar-value {
        color: #38bdf8;
    }

    .mp-qr-card {
        max-width: 24rem;
        margin-inline: auto;
        padding: 1.75rem 1.5rem;
        border-radius: 1.25rem;
        text-align: center;
        background: rgba(255, 255, 255, 0.78);
        border: 1px solid rgba(14, 165, 233, 0.14);
        box-shadow: 0 12px 32px rgba(14, 116, 144, 0.1);
    }

    html.dark .mp-qr-card {
        background: rgba(30, 41, 59, 0.75);
        border-color: rgba(56, 189, 248, 0.14);
    }

    .mp-qr-frame {
        display: inline-flex;
        padding: 0.875rem;
        margin-top: 1.25rem;
        border-radius: 1rem;
        background: #fff;
        box-shadow: inset 0 0 0 1px rgba(14, 165, 233, 0.12);
    }

    html.dark .mp-qr-frame {
        background: #f8fafc;
    }

    .mp-qr-frame img {
        display: block;
        width: min(160px, 100%);
        height: auto;
        aspect-ratio: 1;
    }

    .mp-qr-name {
        font-size: 1.125rem;
        font-weight: 700;
        color: #0f172a;
    }

    html.dark .mp-qr-name {
        color: #f1f5f9;
    }

    .mp-qr-detail {
        margin-top: 0.25rem;
        font-size: 0.875rem;
        color: #64748b;
    }

    html.dark .mp-qr-detail {
        color: #94a3b8;
    }

    .mp-qr-badge {
        display: inline-block;
        margin-top: 1rem;
        padding: 0.375rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        color: #0369a1;
        background: rgba(14, 165, 233, 0.12);
    }

    html.dark .mp-qr-badge {
        color: #7dd3fc;
        background: rgba(56, 189, 248, 0.12);
    }

    .mp-table-wrap {
        overflow-x: auto;
        border-radius: 0.75rem;
        border: 1px solid rgba(14, 165, 233, 0.12);
    }

    html.dark .mp-table-wrap {
        border-color: rgba(56, 189, 248, 0.12);
    }

    .mp-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }

    .mp-table thead tr {
        background: rgba(14, 165, 233, 0.08);
        border-bottom: 1px solid rgba(14, 165, 233, 0.14);
        text-align: left;
    }

    html.dark .mp-table thead tr {
        background: rgba(56, 189, 248, 0.08);
        border-bottom-color: rgba(56, 189, 248, 0.14);
    }

    .mp-table th {
        padding: 0.625rem 0.875rem;
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #0369a1;
    }

    html.dark .mp-table th {
        color: #7dd3fc;
    }

    .mp-table td {
        padding: 0.75rem 0.875rem;
        border-bottom: 1px solid rgba(148, 163, 184, 0.15);
        color: #334155;
    }

    html.dark .mp-table td {
        color: #cbd5e1;
        border-bottom-color: rgba(148, 163, 184, 0.12);
    }

    .mp-table tbody tr:last-child td {
        border-bottom: none;
    }

    .mp-table tfoot td {
        font-weight: 700;
        border-top: 2px solid rgba(14, 165, 233, 0.2);
        border-bottom: none;
        padding-top: 0.875rem;
    }

    .mp-empty {
        padding: 1.5rem;
        text-align: center;
        font-size: 0.875rem;
        color: #64748b;
        border-radius: 0.75rem;
        background: rgba(148, 163, 184, 0.08);
    }

    html.dark .mp-empty {
        color: #94a3b8;
        background: rgba(148, 163, 184, 0.06);
    }

    .mp-loan-header {
        margin-bottom: 1.5rem;
        padding: 1.5rem 1.25rem;
        border-radius: 1rem;
        text-align: center;
        background: linear-gradient(135deg, rgba(14, 165, 233, 0.1), rgba(2, 132, 199, 0.06));
        border: 1px solid rgba(14, 165, 233, 0.16);
    }

    html.dark .mp-loan-header {
        background: linear-gradient(135deg, rgba(56, 189, 248, 0.1), rgba(2, 132, 199, 0.08));
        border-color: rgba(56, 189, 248, 0.16);
    }

    .mp-loan-header-org {
        font-size: 0.6875rem;
        font-weight: 600;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #64748b;
    }

    html.dark .mp-loan-header-org {
        color: #94a3b8;
    }

    .mp-loan-header-address {
        margin-top: 0.25rem;
        font-size: 0.75rem;
        color: #94a3b8;
    }

    .mp-loan-header-title {
        margin-top: 0.875rem;
        font-size: 1.25rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #0f172a;
    }

    html.dark .mp-loan-header-title {
        color: #f1f5f9;
    }

    .mp-page-intro {
        margin-bottom: 0.25rem;
    }

    .mp-actions-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.625rem;
        justify-content: center;
        margin-top: 1rem;
    }

    .mp-modal-intro {
        margin: 0 0 1rem;
        font-size: 0.875rem;
        line-height: 1.55;
        color: #64748b;
    }

    html.dark .mp-modal-intro {
        color: #94a3b8;
    }

    .mp-modal-credit-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.125rem;
        border-radius: 0.875rem;
        background: rgba(255, 255, 255, 0.7);
        border: 1px solid rgba(14, 165, 233, 0.12);
    }

    html.dark .mp-modal-credit-row {
        background: rgba(30, 41, 59, 0.65);
        border-color: rgba(56, 189, 248, 0.12);
    }

    .mp-modal-stack {
        display: flex;
        flex-direction: column;
        gap: 0.875rem;
    }

    /* ── Mobile & PWA safe-area (Members Portal only) ── */
    .fi-panel-user.fi-body {
        padding-left: env(safe-area-inset-left, 0);
        padding-right: env(safe-area-inset-right, 0);
        padding-bottom: env(safe-area-inset-bottom, 0);
        -webkit-text-size-adjust: 100%;
    }

    .fi-panel-user .fi-topbar-ctn {
        padding-top: env(safe-area-inset-top, 0);
    }

    .fi-panel-user .fi-main-ctn {
        width: 100%;
        max-width: 72rem;
        margin-inline: auto;
    }

    .fi-panel-user .fi-ta-ctn,
    .fi-panel-user .mp-table-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .fi-panel-user .mp-table {
        min-width: 36rem;
    }

    .fi-panel-user .fi-ta-table {
        min-width: 40rem;
    }

  @media (max-width: 767px) {
        .fi-panel-user .fi-main {
            padding: 0.75rem;
        }

        .fi-panel-user .fi-page-main {
            gap: 1rem;
        }

        .fi-panel-user .fi-section:not(.fi-section-not-contained) > .fi-section-header,
        .fi-panel-user .fi-section:not(.fi-section-not-contained) > .fi-section-content-ctn > .fi-section-content {
            padding-inline: 0.875rem;
        }

        .fi-panel-user .fi-topbar-brand-name {
            max-width: 9.5rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .fi-panel-user .fi-sidebar-brand-logo-img {
            height: 4rem !important;
            max-height: 4rem;
        }

        .fi-panel-user .mp-hero {
            padding: 1.25rem 1rem;
            border-radius: 1rem;
        }

        .fi-panel-user .mp-hero-title {
            font-size: 1.375rem;
        }

        .fi-panel-user .mp-hero-subtitle {
            font-size: 0.875rem;
        }

        .fi-panel-user .mp-hero-meta {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .fi-panel-user .mp-stat-value {
            font-size: 1.375rem;
        }

        .fi-panel-user .mp-total-bar {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.375rem;
        }

        .fi-panel-user .mp-modal-credit-row {
            flex-direction: column;
            align-items: stretch;
        }

        .fi-panel-user .mp-modal-credit-row .mp-btn,
        .fi-panel-user .mp-credit-card .mp-btn,
        .fi-panel-user .mp-actions-row .mp-btn {
            width: 100%;
        }

        .fi-panel-user .mp-qr-card {
            max-width: none;
            padding: 1.25rem 1rem;
        }

        .fi-panel-user .mp-loan-header {
            padding: 1rem 0.875rem;
        }

        .fi-panel-user .mp-loan-header-title {
            font-size: 1rem;
            line-height: 1.35;
        }

        .fi-panel-user .mp-loan-header-address {
            line-height: 1.45;
        }

        .fi-panel-user .fi-fo-wizard,
        .fi-panel-user .fi-sc-grid {
            grid-template-columns: minmax(0, 1fr) !important;
        }

        .fi-panel-user .fi-fo-field-wrp-label span {
            line-height: 1.35;
        }

        .fi-panel-user .fi-modal-window {
            width: calc(100vw - 1rem) !important;
            max-width: calc(100vw - 1rem) !important;
            margin-inline: 0.5rem;
        }

        .fi-panel-user .fi-ta-actions .fi-btn {
            width: auto;
        }

        .fi-panel-user .fi-fo-field-wrp:has(.fi-fo-radio) .fi-fo-field-content-col {
            overflow-x: auto;
        }
    }

    /* Login page (member portal sign-in) */
    @media (max-width: 767px) {
        .fi-panel-user.fi-body:has(.fi-simple-layout) .fi-simple-layout {
            background-attachment: scroll;
        }

        .fi-panel-user.fi-body:has(.fi-simple-layout) .fi-simple-main {
            width: calc(100% - 1.5rem);
            max-width: 24rem;
            margin-inline: auto;
            padding: 1.25rem 1rem;
        }

        .fi-panel-user.fi-body:has(.fi-simple-layout) .fi-simple-main .fi-logo,
        .fi-panel-user.fi-body:has(.fi-simple-layout) .fi-simple-main img.fi-logo {
            height: 4.5rem !important;
            max-height: 4.5rem;
        }

        .fi-panel-user.fi-body:has(.fi-simple-layout) .fi-simple-header {
            margin-bottom: 1rem;
        }
    }
</style>
