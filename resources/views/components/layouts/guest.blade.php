<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Sign in' }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="mb-8 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-400 text-3xl font-black text-slate-950 shadow-lg shadow-amber-500/20">
                D
            </div>
            <h1 class="text-2xl font-bold text-white">Diding's Midnight Snack</h1>
            <p class="mt-1 text-sm text-slate-400">Point of Sale &amp; Inventory</p>
        </div>

        {{ $slot }}
    </div>
</body>
</html>
