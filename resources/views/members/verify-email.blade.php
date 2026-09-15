<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — {{ config('app.name', 'TEMPUCO') }}</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, sans-serif; background: #f1f5f9; color: #0f172a; margin: 0; }
        .wrap { max-width: 36rem; margin: 4rem auto; padding: 0 1.25rem; }
        .card { background: #fff; border-radius: 1rem; padding: 2rem; box-shadow: 0 10px 30px rgba(15, 23, 42, .08); }
        h1 { font-size: 1.35rem; margin: 0 0 .75rem; }
        p { line-height: 1.55; color: #334155; }
        .ok { color: #047857; }
        .err { color: #b91c1c; }
        a { color: #0369a1; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <h1 class="{{ $ok ? 'ok' : 'err' }}">{{ $title }}</h1>
            <p>{{ $message }}</p>
            @if ($ok)
                <p><a href="{{ url('/portal/login') }}">{{ __('Open the Members Portal') }}</a></p>
            @endif
        </div>
    </div>
</body>
</html>
