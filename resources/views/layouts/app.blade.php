<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-slate-900 antialiased">
    @php
        $nav = [
            ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'grid'],
            ['route' => 'pos', 'label' => 'POS Terminal', 'icon' => 'cart'],
            ['route' => 'products.index', 'label' => 'Products', 'icon' => 'cube'],
            ['route' => 'categories.index', 'label' => 'Categories', 'icon' => 'tag'],
            ['route' => 'inventory.index', 'label' => 'Inventory', 'icon' => 'archive'],
            ['route' => 'sales.index', 'label' => 'Sales', 'icon' => 'receipt'],
            ['route' => 'shifts.index', 'label' => 'Shifts', 'icon' => 'clock'],
            ['route' => 'reports.daily', 'label' => 'Reports', 'icon' => 'chart'],
            ['route' => 'users.index', 'label' => 'Users', 'icon' => 'users'],
            ['route' => 'settings.edit', 'label' => 'Settings', 'icon' => 'settings'],
        ];
    @endphp

    <div x-data="{ sidebar: false }" class="min-h-full">
        <div x-show="sidebar" x-cloak @click="sidebar = false" class="fixed inset-0 z-30 bg-slate-950/50 lg:hidden"></div>

        <aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-slate-950 text-slate-300 transition-transform duration-200 lg:translate-x-0"
            :class="sidebar && 'translate-x-0!'">
            <div class="flex items-center gap-3 px-5 py-5">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-400 text-lg font-black text-slate-950">
                    D
                </div>
                <div class="leading-tight">
                    <p class="text-sm font-bold text-white">Diding's</p>
                    <p class="text-[11px] tracking-wider text-amber-400/90 uppercase">Midnight Snack</p>
                </div>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-2">
                @foreach ($nav as $item)
                    @php $active = request()->routeIs($item['route']); @endphp
                    <a href="{{ route($item['route']) }}"
                        class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ $active ? 'bg-amber-400 text-slate-950 shadow' : 'hover:bg-slate-900 hover:text-white' }}">
                        <x-icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="border-t border-slate-800 p-3">
                <div class="mb-2 flex items-center gap-3 rounded-xl px-2 py-1.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-800 text-sm font-bold text-amber-300">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-white">{{ auth()->user()->name }}</p>
                        <p class="text-[11px] text-slate-400">{{ auth()->user()->role->label() }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-400 transition hover:bg-slate-900 hover:text-white">
                        <x-icon name="logout" class="h-5 w-5" /> Sign out
                    </button>
                </form>
            </div>
        </aside>

        <div class="lg:pl-64">
            <header class="sticky top-0 z-20 flex items-center justify-between gap-4 border-b border-slate-200 bg-white/90 px-4 py-3 backdrop-blur sm:px-6">
                <div class="flex items-center gap-3">
                    <button @click="sidebar = true" class="btn btn-ghost p-2 lg:hidden">
                        <x-icon name="menu" />
                    </button>
                    <div>
                        <h1 class="text-base font-bold sm:text-lg">{{ $title ?? 'Dashboard' }}</h1>
                        @isset($subtitle)
                            <p class="text-xs text-slate-500">{{ $subtitle }}</p>
                        @endisset
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    {{ $actions ?? '' }}
                    <a href="{{ route('pos') }}" class="btn btn-primary btn-sm">
                        <x-icon name="cart" class="h-4 w-4" /> Open till
                    </a>
                </div>
            </header>

            <main class="p-4 sm:p-6">
                @if (session('status'))
                    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">
                        {{ session('error') }}
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
