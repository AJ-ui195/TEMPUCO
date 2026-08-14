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

        .kicker {
            margin: 0 0 0.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #0284c7;
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

        .qr-frame {
            position: relative;
            display: inline-flex;
            padding: 1.15rem;
            margin-top: 1.5rem;
            border-radius: 1.15rem;
            background:
                linear-gradient(#fff, #fff) padding-box,
                linear-gradient(180deg, #38bdf8, #0284c7) border-box;
            border: 2px solid transparent;
            box-shadow: 0 10px 24px rgba(14, 116, 144, 0.12);
        }

        .qr-pad {
            display: flex;
            width: 16rem;
            height: 16rem;
            padding: 0.65rem;
            border-radius: 0.65rem;
            border: 1.5px dashed rgba(14, 165, 233, 0.35);
            background: #fff;
        }

        .qr-corner {
            position: absolute;
            width: 1.35rem;
            height: 1.35rem;
            pointer-events: none;
            border: 3px solid #0284c7;
        }

        .qr-corner--tl {
            top: 0.45rem;
            left: 0.45rem;
            border-right: 0;
            border-bottom: 0;
            border-radius: 0.35rem 0 0 0;
        }

        .qr-corner--tr {
            top: 0.45rem;
            right: 0.45rem;
            border-left: 0;
            border-bottom: 0;
            border-radius: 0 0.35rem 0 0;
        }

        .qr-corner--bl {
            bottom: 0.45rem;
            left: 0.45rem;
            border-right: 0;
            border-top: 0;
            border-radius: 0 0 0 0.35rem;
        }

        .qr-corner--br {
            bottom: 0.45rem;
            right: 0.45rem;
            border-left: 0;
            border-top: 0;
            border-radius: 0 0 0.35rem 0;
        }

        .qr-code {
            width: 100%;
            height: 100%;
        }

        .qr-code svg {
            display: block;
            width: 100%;
            height: 100%;
        }

        .scan-label {
            margin: 0.75rem 0 0;
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #0284c7;
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

            .card,
            .qr-frame {
                border: none;
                box-shadow: none;
            }

            .qr-frame {
                background: #fff;
                border: 2px solid #0284c7;
            }
        }
    </style>
</head>
<body>
    <div class="card">
        <p class="kicker">{{ __('TEMPUCO Member') }}</p>
        <h1>{{ $user->name }}</h1>

        <div class="qr-frame">
            <span class="qr-corner qr-corner--tl" aria-hidden="true"></span>
            <span class="qr-corner qr-corner--tr" aria-hidden="true"></span>
            <span class="qr-corner qr-corner--bl" aria-hidden="true"></span>
            <span class="qr-corner qr-corner--br" aria-hidden="true"></span>
            <div class="qr-pad">
                <div class="qr-code" aria-label="{{ __('Member QR code') }}">
                    {!! $qrSvg !!}
                </div>
            </div>
        </div>

        <p class="scan-label">{{ __('Scan to identify') }}</p>

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
