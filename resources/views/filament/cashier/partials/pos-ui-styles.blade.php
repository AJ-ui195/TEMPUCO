@once
    @push('styles')
        <style>
            .fi-pos-ui {
                color: rgb(15 23 42);
            }

            .dark .fi-pos-ui {
                color: rgb(255 255 255);
            }

            .fi-pos-ui .pos-panel {
                border-radius: 0.75rem;
                border: 1px solid rgb(226 232 240);
                background: rgb(255 255 255);
                color: inherit;
            }

            .dark .fi-pos-ui .pos-panel {
                border-color: rgba(255, 255, 255, 0.1);
                background: rgb(17 24 39);
            }

            .fi-pos-ui .pos-muted {
                color: rgb(100 116 139);
            }

            .dark .fi-pos-ui .pos-muted {
                color: rgb(148 163 184);
            }

            .fi-pos-ui .pos-cart-header {
                border-bottom: 1px solid rgb(226 232 240);
                background: rgb(241 245 249);
            }

            .dark .fi-pos-ui .pos-cart-header {
                border-bottom-color: rgba(255, 255, 255, 0.1);
                background: rgb(31 41 55);
            }

            .fi-pos-ui .pos-input,
            .fi-pos-ui .pos-select {
                width: 100%;
                box-sizing: border-box;
                border-radius: 0.5rem;
                border: 1px solid rgb(203 213 225);
                background: rgb(255 255 255);
                color: rgb(15 23 42);
                padding: 0.5rem 0.75rem;
                font-size: 0.875rem;
            }

            .dark .fi-pos-ui .pos-input,
            .dark .fi-pos-ui .pos-select {
                border-color: rgb(75 85 99);
                background: rgb(3 7 18);
                color: rgb(255 255 255);
            }

            .fi-pos-ui .pos-table thead tr {
                background: rgb(248 250 252);
                color: rgb(51 65 85);
            }

            .dark .fi-pos-ui .pos-table thead tr {
                background: rgba(31, 41, 55, 0.8);
                color: rgb(203 213 225);
            }

            .fi-pos-ui .pos-table tbody tr {
                border-top: 1px solid rgb(226 232 240);
            }

            .dark .fi-pos-ui .pos-table tbody tr {
                border-top-color: rgba(255, 255, 255, 0.1);
            }

            .fi-pos-ui .pos-table tbody tr.pos-line-item td {
                background: rgb(248 250 252);
            }

            .dark .fi-pos-ui .pos-table tbody tr.pos-line-item td {
                background: rgba(31, 41, 55, 0.5);
            }

            .fi-pos-ui .pos-stat {
                padding: 1rem 1.25rem;
            }

            .fi-pos-ui .pos-stat-value {
                font-size: 1.5rem;
                font-weight: 700;
                line-height: 1.2;
            }

            .fi-pos-ui .pos-stat-label {
                font-size: 0.8125rem;
                font-weight: 600;
            }

            .fi-pos-ui .pos-period-btn {
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
                font-weight: 600;
                border-radius: 0.5rem;
                border: 1px solid rgb(203 213 225);
                background: rgb(255 255 255);
                color: rgb(51 65 85);
                cursor: pointer;
            }

            .dark .fi-pos-ui .pos-period-btn {
                border-color: rgb(75 85 99);
                background: rgb(31 41 55);
                color: rgb(226 232 240);
            }

            .fi-pos-ui .pos-period-btn--active {
                border-color: rgb(2 132 199);
                background: rgb(2 132 199);
                color: #fff;
            }

            .dark .fi-pos-ui .pos-period-btn--active {
                border-color: rgb(14 165 233);
                background: rgb(14 165 233);
                color: #fff;
            }

            .fi-pos-ui .pos-btn-primary {
                display: inline-flex;
                align-items: center;
                gap: 0.375rem;
                padding: 0.625rem 1rem;
                font-size: 0.875rem;
                font-weight: 700;
                color: #fff;
                background: rgb(2 132 199);
                border: none;
                border-radius: 0.5rem;
                cursor: pointer;
            }

            .dark .fi-pos-ui .pos-btn-primary {
                background: rgb(14 165 233);
            }

            .fi-pos-ui .pos-btn-secondary {
                display: inline-flex;
                align-items: center;
                gap: 0.375rem;
                padding: 0.625rem 1rem;
                font-size: 0.875rem;
                font-weight: 600;
                color: rgb(100 116 139);
                background: transparent;
                border: 1px solid rgb(203 213 225);
                border-radius: 0.5rem;
                cursor: pointer;
            }

            .dark .fi-pos-ui .pos-btn-secondary {
                color: rgb(148 163 184);
                border-color: rgb(75 85 99);
            }

            .fi-pos-ui .pos-btn-danger {
                display: inline-flex;
                align-items: center;
                gap: 0.375rem;
                padding: 0.625rem 1rem;
                font-size: 0.875rem;
                font-weight: 700;
                color: #fff;
                background: rgb(220 38 38);
                border: none;
                border-radius: 0.5rem;
                cursor: pointer;
            }

            .fi-pos-ui .pos-btn-danger:hover {
                background: rgb(185 28 28);
            }
        </style>
    @endpush
@endonce
