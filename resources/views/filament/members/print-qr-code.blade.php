<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Member QR code') }} — {{ $user->name }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: #111827;
            background: #f9fafb;
        }

        .card {
            width: min(100%, 24rem);
            padding: 2rem;
            text-align: center;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
        }

        h1 {
            margin: 0 0 0.25rem;
            font-size: 1.5rem;
            line-height: 1.3;
        }

        p {
            margin: 0.25rem 0;
            color: #4b5563;
        }

        .member-id {
            margin-top: 0.75rem;
            font-size: 0.875rem;
            color: #6b7280;
        }

        .qr-code {
            width: 16rem;
            height: 16rem;
            margin: 1.5rem auto 0;
        }

        .qr-code svg {
            display: block;
            width: 100%;
            height: 100%;
        }

        .actions {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            margin-top: 1.5rem;
        }

        button {
            font: inherit;
            cursor: pointer;
            padding: 0.625rem 1rem;
            border-radius: 0.5rem;
            border: 1px solid transparent;
        }

        .btn-primary {
            color: #fff;
            background: #d97706;
        }

        .btn-secondary {
            color: #374151;
            background: #fff;
            border-color: #d1d5db;
        }

        @media print {
            body {
                background: #fff;
            }

            .actions {
                display: none;
            }

            .card {
                border: none;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ $user->name }}</h1>
        <p>{{ $user->email }}</p>
        @if (filled($user->cellphone))
            <p>{{ $user->cellphone }}</p>
        @endif
        @if (filled($user->address))
            <p>{{ $user->address }}</p>
        @endif

        <div class="qr-code" aria-label="{{ __('Member QR code') }}">
            {!! $qrSvg !!}
        </div>

        <p class="member-id">{{ __('Member ID') }}: {{ $user->id }}</p>

        <div class="actions">
            <button type="button" class="btn-primary" onclick="window.print()">
                {{ __('Print') }}
            </button>
            <button type="button" class="btn-secondary" onclick="window.close()">
                {{ __('Close') }}
            </button>
        </div>
    </div>

    @if ($autoPrint ?? false)
        <script>
            window.addEventListener('load', () => window.print());
        </script>
    @endif
</body>
</html>
