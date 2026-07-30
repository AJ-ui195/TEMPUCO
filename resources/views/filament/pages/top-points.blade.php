<x-filament-panels::page>
    @php
        $members = $this->getTopMembers();
    @endphp

    <style>
        .tp-card {
            border-radius: 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.55);
            background: rgba(255, 255, 255, 0.55);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            box-shadow:
                0 8px 32px rgba(15, 23, 42, 0.08),
                inset 0 1px 0 rgba(255, 255, 255, 0.6);
            overflow: hidden;
        }

        html.dark .tp-card {
            border-color: rgba(255, 255, 255, 0.12);
            background: rgba(30, 41, 59, 0.55);
            box-shadow:
                0 8px 32px rgba(0, 0, 0, 0.25),
                inset 0 1px 0 rgba(255, 255, 255, 0.08);
        }

        .tp-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.35);
        }

        html.dark .tp-header {
            border-bottom-color: rgba(255, 255, 255, 0.1);
        }

        .tp-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            color: rgb(15 23 42);
        }

        html.dark .tp-title {
            color: #fff;
        }

        .tp-subtitle {
            margin: 0.35rem 0 0;
            font-size: 0.8125rem;
            color: rgb(100 116 139);
        }

        html.dark .tp-subtitle {
            color: rgb(148 163 184);
        }

        .tp-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .tp-table th,
        .tp-table td {
            padding: 0.875rem 1.25rem;
            vertical-align: middle;
            border-bottom: 1px solid rgba(148, 163, 184, 0.25);
        }

        html.dark .tp-table th,
        html.dark .tp-table td {
            border-bottom-color: rgba(255, 255, 255, 0.08);
        }

        .tp-table tbody tr:last-child td {
            border-bottom: none;
        }

        .tp-table thead th {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: rgb(100 116 139);
            background: rgba(248, 250, 252, 0.8);
        }

        html.dark .tp-table thead th {
            color: rgb(148 163 184);
            background: rgba(15, 23, 42, 0.35);
        }

        .tp-col-rank {
            width: 5.5rem;
            text-align: left;
        }

        .tp-col-name {
            width: auto;
            text-align: left;
        }

        .tp-col-points {
            width: 7rem;
            text-align: right;
        }

        .tp-rank {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 2rem;
            height: 2rem;
            padding: 0 0.5rem;
            border-radius: 9999px;
            font-size: 0.8125rem;
            font-weight: 700;
            color: rgb(15 23 42);
            background: rgba(241, 245, 249, 0.95);
        }

        html.dark .tp-rank {
            color: #fff;
            background: rgba(51, 65, 85, 0.9);
        }

        .tp-rank--1 {
            color: #78350f;
            background: #fde68a;
        }

        .tp-rank--2 {
            color: #334155;
            background: #e2e8f0;
        }

        .tp-rank--3 {
            color: #7c2d12;
            background: #fdba74;
        }

        html.dark .tp-rank--1 {
            color: #fef3c7;
            background: rgba(180, 83, 9, 0.55);
        }

        html.dark .tp-rank--2 {
            color: #e2e8f0;
            background: rgba(100, 116, 139, 0.45);
        }

        html.dark .tp-rank--3 {
            color: #ffedd5;
            background: rgba(154, 52, 18, 0.5);
        }

        .tp-name {
            font-size: 0.9375rem;
            font-weight: 600;
            color: rgb(15 23 42);
        }

        html.dark .tp-name {
            color: #fff;
        }

        .tp-points {
            font-size: 1rem;
            font-weight: 700;
            color: rgb(180 83 9);
            font-variant-numeric: tabular-nums;
        }

        html.dark .tp-points {
            color: #fbbf24;
        }

        .tp-empty {
            padding: 2.5rem 1.25rem;
            text-align: center;
            font-size: 0.9375rem;
            color: rgb(100 116 139);
        }

        html.dark .tp-empty {
            color: rgb(148 163 184);
        }

        .tp-table tbody tr:hover td {
            background: rgba(248, 250, 252, 0.65);
        }

        html.dark .tp-table tbody tr:hover td {
            background: rgba(255, 255, 255, 0.04);
        }
    </style>

    <div class="tp-card">
        <div class="tp-header">
            <h2 class="tp-title">{{ __('Top 10 members by loyalty points') }}</h2>
            <p class="tp-subtitle">
                {{ __('Members with the highest points from POS purchases of ₱200 or more.') }}
            </p>
        </div>

        @if ($members->isEmpty())
            <div class="tp-empty">
                {{ __('No members have earned loyalty points yet.') }}
            </div>
        @else
            <table class="tp-table">
                <thead>
                    <tr>
                        <th class="tp-col-rank">{{ __('Rank') }}</th>
                        <th class="tp-col-name">{{ __('Name') }}</th>
                        <th class="tp-col-points">{{ __('Points') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($members as $index => $member)
                        @php($rank = $index + 1)
                        <tr>
                            <td class="tp-col-rank">
                                <span class="tp-rank {{ $rank <= 3 ? 'tp-rank--'.$rank : '' }}">
                                    #{{ $rank }}
                                </span>
                            </td>
                            <td class="tp-col-name">
                                <span class="tp-name">{{ $member->name }}</span>
                            </td>
                            <td class="tp-col-points">
                                <span class="tp-points">{{ number_format((int) $member->points) }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</x-filament-panels::page>
