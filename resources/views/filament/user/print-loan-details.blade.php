<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $documentTitle }} — {{ $borrowerName ?: '#'.$loan->id }}</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 1.25rem 0.75rem 2rem;
            background: #e8edf2;
            color: #111;
            font-size: 12.5px;
            line-height: 1.35;
        }

        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            justify-content: center;
            margin: 0 auto 1rem;
            max-width: 210mm;
        }

        button {
            font-family: system-ui, -apple-system, sans-serif;
            cursor: pointer;
            padding: 0.6rem 1.1rem;
            border-radius: 0.45rem;
            border: 1px solid transparent;
            font-size: 14px;
        }

        .btn-primary { color: #fff; background: #0284c7; }
        .btn-secondary { color: #334155; background: #fff; border-color: #cbd5e1; }

        .page {
            width: 210mm;
            height: 297mm;
            max-width: 210mm;
            margin: 0 auto 1.25rem;
            padding: 16mm 18mm 14mm;
            background: #fff;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
            overflow: hidden;
        }

        .center { text-align: center; }
        .bold { font-weight: 700; }
        .small { font-size: 11px; }
        .mt { margin-top: 0.7rem; }

        /* Disclosure statement */
        .disclosure-page {
            font-family: "Times New Roman", Times, "Liberation Serif", serif;
        }

        .creditor {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .creditor-note { margin: 0.1rem 0 0; font-size: 11px; }

        .doc-title {
            margin: 0.7rem 0 0;
            font-size: 14.5px;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .doc-sub { margin: 0.15rem 0 0.85rem; font-size: 11.5px; }

        .fill-row {
            display: flex;
            align-items: baseline;
            gap: 0.45rem;
            margin-top: 0.35rem;
        }

        .fill-label {
            font-weight: 700;
            font-size: 12px;
            white-space: nowrap;
        }

        .fill-line {
            flex: 1;
            min-height: 1.15em;
            border-bottom: 1px solid #111;
            padding: 0 0.25rem 0.05rem;
            font-size: 13px;
        }

        table.charges {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0.45rem;
            table-layout: fixed;
        }

        table.charges th,
        table.charges td {
            vertical-align: top;
            padding: 0.18rem 0.28rem;
        }

        table.charges th {
            font-size: 9.5px;
            font-weight: 700;
            text-align: center;
            line-height: 1.2;
            border-bottom: 1px solid #111;
        }

        table.charges .desc { width: 62%; }
        table.charges .col { width: 19%; }

        td.item { font-weight: 700; padding-top: 0.4rem; }
        td.sub { padding-left: 1.1rem; }
        td.sub-deep { padding-left: 1.6rem; font-size: 11.5px; }

        td.amt {
            text-align: right;
            font-variant-numeric: tabular-nums;
            border-bottom: 1px solid #111;
            height: 1.25rem;
            white-space: nowrap;
        }

        .leaders {
            display: block;
            border-bottom: 1px dotted #333;
            min-height: 1.1em;
        }

        .inline-u {
            display: inline-block;
            min-width: 2.4rem;
            border-bottom: 1px solid #111;
            text-align: center;
            padding: 0 0.2rem;
            font-weight: 700;
        }

        .inline-u.date { min-width: 3.6rem; }
        .checks { margin-top: 0.15rem; font-size: 11.5px; }
        .box { display: inline-block; min-width: 0.85em; text-align: center; }

        .summary-label { font-weight: 700; padding-top: 0.45rem; }
        .summary-amt {
            text-align: right;
            font-weight: 700;
            border-bottom: 2px solid #111;
            padding-top: 0.45rem;
        }

        .pct-row {
            display: flex;
            align-items: baseline;
            gap: 0.4rem;
            margin-top: 0.45rem;
        }

        .pct-row .fill-line { max-width: 4.5rem; text-align: center; font-weight: 700; }

        .sched { margin-top: 0.35rem; padding-left: 1.1rem; }
        .sched-line { display: flex; align-items: baseline; gap: 0.35rem; margin-top: 0.2rem; }
        .sched-line .fill-line { flex: 0 1 auto; min-width: 4rem; }

        .collateral { margin-top: 0.45rem; }
        .collateral-opts { margin-top: 0.2rem; padding-left: 1.1rem; }

        .extra-table {
            width: 70%;
            margin-top: 0.3rem;
            margin-left: 1.1rem;
            border-collapse: collapse;
        }

        .extra-table th,
        .extra-table td {
            border: 1px solid #111;
            padding: 0.28rem 0.4rem;
            text-align: left;
        }

        .extra-table th { font-size: 11px; text-align: center; }
        .extra-table td { height: 1.35rem; }

        .sig-pair {
            display: flex;
            gap: 1.5rem;
            margin-top: 1.1rem;
        }

        .sig-pair > div { flex: 1; }
        .sig-line { border-bottom: 1px solid #111; min-height: 1.4rem; }
        .sig-caption { margin-top: 0.15rem; font-size: 11px; text-align: center; }

        .ack {
            margin-top: 1.1rem;
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            text-align: justify;
            line-height: 1.45;
        }

        .borrower-sig {
            display: flex;
            gap: 1.5rem;
            margin-top: 1.4rem;
            align-items: flex-end;
        }

        .borrower-sig .date { flex: 0 0 32%; }
        .borrower-sig .sign { flex: 1; }

        /* Quick loan application */
        .quick-page {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            line-height: 1.4;
        }

        .letterhead {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.35rem;
        }

        .letterhead-logo {
            flex: 0 0 78px;
            width: 78px;
            height: 78px;
            object-fit: contain;
        }

        .letterhead-text {
            flex: 1;
            text-align: center;
        }

        .letterhead-text p { margin: 0; font-size: 11.5px; }
        .letterhead-text .org-name {
            margin-top: 0.15rem;
            font-size: 12.5px;
            font-weight: 700;
            line-height: 1.25;
        }
        .letterhead-text .org-short {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.04em;
        }

        .letterhead-spacer { flex: 0 0 78px; }

        .form-title {
            margin: 0.85rem 0 1.1rem;
            text-align: center;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.06em;
        }

        .app-section { margin-top: 1.15rem; }
        .app-section:first-of-type { margin-top: 0; }

        .app-h {
            margin: 0 0 0.5rem;
            font-size: 13.5px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .field-row {
            display: flex;
            align-items: baseline;
            gap: 0.4rem;
            margin-top: 0.42rem;
            flex-wrap: wrap;
        }

        .field-label { white-space: nowrap; }
        .field-line {
            flex: 0 0 auto;
            display: inline-block;
            width: auto;
            max-width: 100%;
            min-width: 2.25rem;
            border-bottom: 1px solid #111;
            min-height: 1.15em;
            padding: 0 0.55em 0.05rem;
            white-space: nowrap;
        }
        .field-line.mid,
        .field-line.short {
            flex: 0 0 auto;
            min-width: 2.25rem;
        }

        .breakdown-h { margin: 0.7rem 0 0.15rem; font-weight: 700; }
        .breakdown { padding-left: 1.25rem; }

        .app-body { margin: 0; }
        .app-body.pad { padding-left: 1rem; }

        .chosen {
            font-weight: 700;
            text-decoration: underline;
        }

        .u-short {
            display: inline-block;
            min-width: 6.5rem;
            border-bottom: 1px solid #111;
            padding: 0 0.2rem;
        }

        .terms { margin: 0.35rem 0 0; padding-left: 1.4rem; }
        .terms li { margin-top: 0.2rem; }

        .rule {
            border: 0;
            border-top: 1px solid #111;
            margin: 1.35rem 0 0.35rem;
        }

        .office { margin-top: 0.85rem; }
        .office-row {
            display: flex;
            justify-content: space-between;
            gap: 1.5rem;
            margin-top: 0.95rem;
        }
        .office-row.tight { margin-top: 1rem; }

        .office-col { flex: 1; min-width: 0; }
        .office-label { font-size: 13px; }
        .office-line {
            display: inline-block;
            min-width: 11rem;
            border-bottom: 1px solid #111;
            min-height: 1.15em;
            padding: 0 0.2rem;
        }

        .office-sub {
            display: block;
            margin-top: 0.15rem;
            font-size: 11px;
            color: #222;
        }

        .officer-name {
            display: block;
            margin-top: 0.15rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .officer-title { display: block; font-size: 12px; }

        .remarks-line {
            display: inline-block;
            min-width: 8rem;
            width: auto;
            max-width: 16rem;
            border-bottom: 1px solid #111;
            min-height: 1.2em;
            padding: 0 0.45em 0.05rem;
            vertical-align: baseline;
        }

        @page {
            size: A4 portrait;
            margin: 0;
        }

        @media print {
            html, body {
                width: 210mm;
                height: auto;
                padding: 0;
                margin: 0;
                background: #fff;
            }

            .toolbar { display: none; }

            .page {
                width: 210mm;
                height: 297mm;
                max-width: 210mm;
                margin: 0;
                padding: 16mm 18mm 14mm;
                box-shadow: none;
                overflow: hidden;
                break-after: page;
                page-break-after: always;
            }

            .page:last-of-type {
                break-after: auto;
                page-break-after: auto;
            }

            .letterhead-logo {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
        }

        @media (max-width: 720px) {
            body { padding: 0.75rem 0.4rem 1.5rem; }
            .page {
                width: 100%;
                max-width: 100%;
                height: auto;
                min-height: 297mm;
                padding: 0.7rem 0.75rem 1rem;
                overflow: visible;
            }
            .letterhead { flex-wrap: wrap; justify-content: center; }
            .letterhead-spacer { display: none; }
            table.charges .desc { width: 54%; }
            .office-row { flex-direction: column; gap: 0.85rem; }
            .borrower-sig { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" class="btn-primary" onclick="window.print()">
            {{ __('Print / Download PDF') }}
        </button>
        <button type="button" class="btn-secondary" onclick="window.close()">
            {{ __('Close') }}
        </button>
    </div>

    @if ($isQuickLoan)
        @include('filament.user.partials.print-quick-loan-form')
    @else
        @include('filament.user.partials.print-disclosure-statement')
    @endif

    @if ($autoPrint ?? false)
        <script>
            window.addEventListener('load', () => window.print());
        </script>
    @endif
</body>
</html>
