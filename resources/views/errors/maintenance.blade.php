<!doctype html>
@php
    $heading = $heading ?? 'We will be back soon';
    $description = $description ?? 'The application is temporarily unavailable while maintenance is in progress.';
    $scheduled_window = $scheduled_window ?? '';
    $support_contact = $support_contact ?? '';
    $brand = $brand ?? config('app.name');
@endphp
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $heading }}</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            background: #f8fafc;
            color: #172033;
            font: 16px/1.6 system-ui, -apple-system, "Segoe UI", sans-serif;
        }
        main {
            box-sizing: border-box;
            display: grid;
            min-height: 100vh;
            place-items: center;
            padding: 1.5rem;
        }
        section {
            max-width: 32rem;
            text-align: center;
        }
        section > * { margin: 0; }
        section > * + * { margin-top: .75rem; }
        svg { width: 2.5rem; height: 2.5rem; margin-inline: auto; color: #64748b; }
        .label { color: #64748b; font-size: .875rem; font-weight: 600; }
        h1 { font-size: 1.5rem; line-height: 2rem; }
        p { color: #64748b; }
        a { color: inherit; text-underline-offset: 4px; }
        .details p { margin: .5rem 0; }
        .brand { margin-top: 2rem; font-size: .875rem; }
    </style>
</head>
<body>
<main><section>
    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10" />
        <path d="M12 8v4m0 4h.01" />
    </svg>
    <p class="label">Error 503</p>
    <h1>{{ $heading }}</h1>
    <p>{{ $description }}</p>
    @if ($scheduled_window || $support_contact)<div class="details">
        @if ($scheduled_window)<p>Estimated completion: {{ $scheduled_window }}</p>@endif
        @if ($support_contact)<p>Support: {{ $support_contact }}</p>@endif
    </div>@endif
    <p><a href="/">Return to the home page</a></p>
    <p class="brand">{{ $brand }}</p>
</section></main>
</body>
</html>
