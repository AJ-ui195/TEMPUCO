<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Credit payment receipt') }} — {{ $payment->reference }}</title>
    <style>
        @include('filament.cashier.partials.receipt-print-styles')

        body { padding: 1rem; background: #f3f4f6; }

        .pos-receipt-paper { border: 1px solid #d1d5db; }

        .actions {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin-top: 1rem;
        }

        button {
            font: inherit;
            cursor: pointer;
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            border: 1px solid transparent;
        }

        .btn-primary { color: #fff; background: #0284c7; }
        .btn-secondary { color: #374151; background: #fff; border-color: #d1d5db; }

        @media print {
            .pos-receipt-paper { border: none; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    @include('filament.cashier.partials.credit-payment-receipt-body', [
        'payment' => $payment,
        'cashier' => $cashier,
        'balanceBefore' => $balanceBefore,
        'balanceAfter' => $balanceAfter,
    ])

    <div class="actions">
        <button type="button" class="btn-primary" onclick="window.print()">{{ __('Print') }}</button>
        <button type="button" class="btn-secondary" onclick="window.close()">{{ __('Close') }}</button>
    </div>

    @if ($autoPrint ?? false)
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
