<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Delete all data · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full bg-slate-100 font-sans text-slate-900 antialiased">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3">
            <a href="{{ route('dashboard') }}" class="font-black text-amber-600">D</a>
            <span class="text-sm font-semibold">Diding's Midnight Snack</span>
            <nav class="ml-auto flex gap-1 text-xs">
                <a href="{{ route('dashboard') }}" class="btn btn-ghost btn-sm">Dashboard</a>
                <a href="{{ route('pos') }}" class="btn btn-ghost btn-sm">Till</a>
                <a href="{{ route('products.index') }}" class="btn btn-ghost btn-sm">Products</a>
                <a href="{{ route('sales.index') }}" class="btn btn-ghost btn-sm">Sales</a>
                <a href="{{ route('users.index') }}" class="btn btn-ghost btn-sm">Users</a>
                <a href="{{ route('settings.edit') }}" class="btn btn-ghost btn-sm">Settings</a>
            </nav>
        </div>
    </header>

    <div class="mx-auto w-full max-w-5xl px-4 py-8">
        @php
            $phrase = \App\Http\Controllers\DataDestructionController::CONFIRMATION_PHRASE;
            $total = array_sum($counts);
            $hasErrors = $errors->any();
        @endphp

        <div x-data="{ open: @js($hasErrors), phrase: '' }" class="relative">
            <div class="space-y-5">
                @if (session('data_deleted'))
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                        {{ session('data_deleted') }}
                    </div>
                @endif

                @if ($hasErrors)
                    <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">
                        {{ $errors->first('confirmation') }}
                    </div>
                @endif

                <div class="card card-body">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h1 class="text-2xl font-black tracking-tight">Delete all data</h1>
                            <p class="mt-1 text-sm text-slate-500">
                                This permanently deletes every product, order, sale, shift and user. There is no going
                                back — the data stays deleted even after a restart.
                            </p>
                        </div>

                        <button type="button" x-on:click="open = true" class="btn btn-danger" aria-label="Delete all data">
                            Delete everything permanently
                        </button>
                    </div>
                </div>

                <div class="card card-body">
                    <h2 class="text-sm font-bold">What will be deleted ({{ $total }} rows)</h2>

                    <table class="data-table mt-3">
                        <thead>
                            <tr><th>Table</th><th class="text-right">Rows</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($counts as $table => $count)
                                <tr>
                                    <td class="font-mono text-xs">{{ $table }}</td>
                                    <td class="text-right text-slate-600">{{ $count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Destructive confirmation modal --}}
            <div x-show="open" x-cloak x-transition.opacity
                class="fixed inset-0 z-60 flex items-center justify-center bg-slate-950/70 backdrop-blur-sm"
                role="dialog" aria-modal="true" aria-label="Confirm permanent deletion">
                <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl sm:p-8">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-black text-rose-900">Permanently delete everything?</h2>
                        <button type="button" x-on:click="open = false" class="text-slate-400 hover:text-slate-700">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M6 6l12 12M18 6 6 18" />
                            </svg>
                        </button>
                    </div>

                    <div class="mt-4 space-y-3">
                        <p class="text-sm text-slate-600">
                            This cannot be undone. All products, orders, sales, shifts and user accounts will be
                            removed from the database and your files deleted.
                        </p>

                        <p class="rounded-lg bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700">
                            To confirm, type exactly: <span class="font-mono text-xs text-rose-900">{{ $phrase }}</span>
                        </p>

                        <input type="text" name="confirmation" x-model="phrase"
                            :placeholder="{{ $phrase }}"
                            :class="phrase === {{ $phrase }} ? 'border-rose-400 text-rose-900' : 'border-slate-300 text-slate-400'"
                            class="w-full rounded-xl border bg-white px-3 py-2.5 text-center font-mono text-sm outline-none focus:ring-2 focus:ring-rose-200"
                            autocomplete="off" spellcheck="false">
                    </div>

                    <form method="POST" action="{{ route('data.destruct.store') }}" class="mt-6 flex gap-2">
                        @csrf
                        <input type="hidden" name="confirmation" :value="phrase">
                        <button type="button" x-on:click="open = false" class="btn btn-secondary flex-1">
                            Keep everything
                        </button>
                        <button type="submit" :disabled="phrase !== {{ $phrase }}"
                            class="btn btn-danger flex-1 disabled:cursor-not-allowed disabled:opacity-40">
                            Delete everything permanently
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
