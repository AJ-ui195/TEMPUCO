<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>&nbsp;</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 0.6in 0.7in;
            background: #fff;
            color: #000;
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
        }

        .letterhead {
            text-align: center;
            margin: 0 0 0.35in;
            overflow: hidden;
        }

        .letterhead img {
            float: left;
            width: 0.95in;
            height: 0.95in;
            object-fit: contain;
            margin: 0 0.12in 0.08in 0;
        }

        .letterhead .org {
            margin: 0 0 0.08em;
            font-weight: 700;
            font-size: 13pt;
            line-height: 1.25;
            text-transform: uppercase;
        }

        .letterhead .city,
        .letterhead .aka {
            margin: 0;
            font-weight: 700;
            font-size: 12pt;
            line-height: 1.3;
        }

        .intro {
            margin: 0 0 0.22in;
            font-size: 12pt;
            line-height: 1.35;
            text-align: left;
        }

        .intro .date {
            float: right;
            margin: 0 0 0.15em 1em;
            font-weight: 700;
            white-space: nowrap;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 12pt;
        }

        th, td {
            border: 1px solid #000;
            padding: 0.12em 0.35em;
            vertical-align: middle;
        }

        th {
            font-weight: 700;
            text-align: center;
        }

        th.name {
            letter-spacing: 0.35em;
        }

        th.installment {
            width: 1.7in;
            line-height: 1.15;
        }

        th.remarks,
        td.remarks {
            width: 1.45in;
        }

        td.num {
            width: 0.38in;
            text-align: center;
        }

        td.name { text-align: left; }
        td.amt { text-align: right; white-space: nowrap; }
        td.empty { text-align: center; padding: 0.6em; }

        tfoot td { font-weight: 700; }

        .thanks {
            margin: 0.35in 0 0.45in;
            font-size: 12pt;
        }

        .sign {
            font-size: 12pt;
        }

        .sign-line {
            width: 2.6in;
            border-bottom: 1px solid #000;
            margin: 0.55in 0 0.12in;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin-top: 1.25rem;
        }

        button {
            font: inherit;
            cursor: pointer;
            padding: 0.45rem 0.8rem;
            border-radius: 0.35rem;
            border: 1px solid transparent;
        }

        .btn-primary { color: #fff; background: #0284c7; }
        .btn-secondary { color: #374151; background: #fff; border-color: #d1d5db; }

        @page {
            size: letter portrait;
            margin: 0.6in 0.7in;
        }

        @media print {
            body { padding: 0; }
            .actions { display: none; }
            .letterhead img { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <header class="letterhead">
        <img src="{{ asset('images/DICNHSLOGO1.png') }}" alt="">
        <p class="org">{{ __('DIGOS CITY NATIONAL HIGH SCHOOL TEACHERS AND EMPLOYEES MULTI-PURPOSE COOPERATIVE') }}</p>
        <p class="city">{{ __('DIGOS CITY') }}</p>
        <p class="aka">{{ __('( DICNHS-TEMPUCO )') }}</p>
    </header>

    <p class="intro">
        <span class="date">{{ now()->format('F j, Y') }}</span>
        {{ __('I have the honor to submit herewith the list of teachers who availed for Automatic Payroll Deduction System (APDS) to wit:') }}
    </p>

    <table>
        <thead>
            <tr>
                <th class="num"></th>
                <th class="name">{{ __('NAME') }}</th>
                <th class="installment">{!! __('Monthly<br>Installment') !!}</th>
                <th class="remarks">{{ __('Remarks') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($listed as $index => $row)
                <tr>
                    <td class="num">{{ $index + 1 }}</td>
                    <td class="name">{{ $row['loan']->member?->name ?? __('Unknown member') }}</td>
                    <td class="amt">
                        @if ($row['installment'] > 0.005)
                            {{ number_format($row['installment'], 2) }}
                        @endif
                    </td>
                    <td class="remarks"></td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="empty">{{ __('No accounts on the APDS list.') }}</td>
                </tr>
            @endforelse
        </tbody>
        @if ($listed->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="2"></td>
                    <td class="amt">{{ number_format($totalInstallment, 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>

    <p class="thanks">{{ __('Thank you very much.') }}</p>
    <div class="sign">
        <div>{{ __('PREPARED BY:') }}</div>
        <div class="sign-line"></div>
        <div><strong>{{ __('Treasurer') }}</strong></div>
    </div>

    <div class="actions">
        <button type="button" class="btn-primary" onclick="window.print()">{{ __('Print') }}</button>
        <button type="button" class="btn-secondary" onclick="window.close()">{{ __('Close') }}</button>
    </div>

    <script>
        window.addEventListener('load', () => window.print());
    </script>
</body>
</html>
