<style>
    .create-type-picker {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.875rem;
        padding: 0.25rem 0 0.15rem;
    }

    @media (min-width: 640px) {
        .create-type-picker {
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
    }

    .create-type-card {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.85rem;
        width: 100%;
        margin: 0;
        padding: 1.15rem 1.2rem;
        text-align: left;
        cursor: pointer;
        border-radius: 0.9rem;
        border: 1px solid rgba(255, 255, 255, 0.12);
        background: rgba(255, 255, 255, 0.04);
        color: inherit;
        box-shadow: none;
        transition:
            border-color 0.15s ease,
            background-color 0.15s ease,
            transform 0.15s ease;
    }

    .create-type-card:hover {
        border-color: rgba(245, 158, 11, 0.55);
        background: rgba(245, 158, 11, 0.08);
        transform: translateY(-1px);
    }

    .create-type-card:focus-visible {
        outline: 2px solid rgb(245 158 11);
        outline-offset: 2px;
    }

    .create-type-card__icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 0.7rem;
        flex-shrink: 0;
    }

    .create-type-card__icon svg {
        width: 1.4rem;
        height: 1.4rem;
    }

    .create-type-card__icon--members {
        background: rgba(245, 158, 11, 0.16);
        color: rgb(251 191 36);
    }

    .create-type-card__icon--admin {
        background: rgba(148, 163, 184, 0.18);
        color: rgb(203 213 225);
    }

    .create-type-card__body {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        min-width: 0;
    }

    .create-type-card__title {
        display: block;
        font-size: 1rem;
        font-weight: 650;
        line-height: 1.25;
        color: rgb(248 250 252);
    }

    .create-type-card__desc {
        display: block;
        font-size: 0.875rem;
        line-height: 1.45;
        color: rgb(148 163 184);
    }

    html:not(.dark) .create-type-card {
        border-color: rgb(226 232 240);
        background: rgb(255 255 255);
    }

    html:not(.dark) .create-type-card:hover {
        border-color: rgb(245 158 11);
        background: rgb(255 251 235);
    }

    html:not(.dark) .create-type-card__title {
        color: rgb(15 23 42);
    }

    html:not(.dark) .create-type-card__desc {
        color: rgb(100 116 139);
    }

    html:not(.dark) .create-type-card__icon--members {
        background: rgba(245, 158, 11, 0.14);
        color: rgb(217 119 6);
    }

    html:not(.dark) .create-type-card__icon--admin {
        background: rgba(100, 116, 139, 0.12);
        color: rgb(71 85 105);
    }
</style>

<div class="create-type-picker">
    <button
        type="button"
        wire:click="replaceMountedAction('createMember')"
        class="create-type-card"
    >
        <span class="create-type-card__icon create-type-card__icon--members" aria-hidden="true">
            <x-filament::icon icon="heroicon-o-users" />
        </span>
        <span class="create-type-card__body">
            <span class="create-type-card__title">{{ __('Members') }}</span>
            <span class="create-type-card__desc">
                {{ __('Full member profile and QR code.') }}
            </span>
        </span>
    </button>

    <button
        type="button"
        wire:click="replaceMountedAction('createAdmin')"
        class="create-type-card"
    >
        <span class="create-type-card__icon create-type-card__icon--admin" aria-hidden="true">
            <x-filament::icon icon="heroicon-o-user-circle" />
        </span>
        <span class="create-type-card__body">
            <span class="create-type-card__title">{{ __('Admin') }}</span>
            <span class="create-type-card__desc">
                {{ __('Staff account listed under Staff: Admin, grocery, or canteen cashier.') }}
            </span>
        </span>
    </button>
</div>
