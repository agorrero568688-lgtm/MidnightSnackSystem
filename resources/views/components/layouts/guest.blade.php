<!DOCTYPE html>
<html lang="en" class="h-full bg-ink-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#111315">
    @include('partials.favicon')
    <title>{{ $title }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="relative flex min-h-full items-center justify-center overflow-hidden bg-ink-950 p-4 text-ink-200">
    <div class="pointer-events-none absolute -top-32 -left-24 h-72 w-72 rounded-full bg-brand-400/15 blur-3xl"></div>
    <div class="pointer-events-none absolute -right-24 -bottom-32 h-80 w-80 rounded-full bg-violet-500/20 blur-3xl"></div>

    <div class="relative w-full max-w-sm">
        <div class="mb-8 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-400 text-3xl font-black text-ink-950 shadow-glow-brand">
                D
            </div>
            <h1 class="text-2xl font-bold text-white">Diding's Midnight Snack</h1>
            <p class="mt-1 text-sm text-ink-400">Point of Sale &amp; Inventory</p>
        </div>

        {{ $slot }}
    </div>
</body>
</html>
