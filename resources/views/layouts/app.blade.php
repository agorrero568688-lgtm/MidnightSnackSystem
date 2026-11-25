<!DOCTYPE html>
<html lang="en" class="h-full bg-canvas">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#111315">
    @include('partials.favicon')
    <title>{{ $title ?? 'Dashboard' }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-ink-900 antialiased">
    @php
        $nav = [
            ['route' => 'orders.index', 'label' => 'Online orders', 'icon' => 'bell', 'roles' => ['admin', 'cashier']],
            ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'grid', 'roles' => ['admin']],
            ['route' => 'pos', 'label' => 'POS Terminal', 'icon' => 'cart', 'roles' => ['admin', 'cashier']],
            ['route' => 'products.index', 'label' => 'Products', 'icon' => 'cube', 'roles' => ['admin']],
            ['route' => 'categories.index', 'label' => 'Categories', 'icon' => 'tag', 'roles' => ['admin']],
            ['route' => 'inventory.index', 'label' => 'Inventory', 'icon' => 'archive', 'roles' => ['admin']],
            ['route' => 'sales.index', 'label' => 'Sales', 'icon' => 'receipt', 'roles' => ['admin']],
            ['route' => 'shifts.index', 'label' => 'Shifts', 'icon' => 'clock', 'roles' => ['admin']],
            ['route' => 'reports.daily', 'label' => 'Reports', 'icon' => 'chart', 'roles' => ['admin']],
            ['route' => 'users.index', 'label' => 'Users', 'icon' => 'users', 'roles' => ['admin']],
            ['route' => 'settings.edit', 'label' => 'Settings', 'icon' => 'settings', 'roles' => ['admin']],
        ];

        $role = auth()->user()->role->value;
        $nav = array_values(array_filter($nav, fn (array $item) => in_array($role, $item['roles'], true)));
    @endphp

    <div x-data="{ sidebar: false }" class="min-h-full">
        <div x-show="sidebar" x-cloak @click="sidebar = false" class="fixed inset-0 z-30 bg-ink-950/60 backdrop-blur-sm lg:hidden"></div>

        <aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-white/5 bg-gradient-to-b from-ink-950 via-ink-950 to-ink-900 text-ink-300 transition-transform duration-200 lg:translate-x-0"
            :class="sidebar && 'translate-x-0!'">
            <div class="flex items-center gap-3 px-5 py-5">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-400 text-lg font-black text-ink-950 shadow-glow-brand">
                    D
                </div>
                <div class="leading-tight">
                    <p class="text-sm font-bold text-white">Diding's</p>
                    <p class="text-[11px] tracking-[0.18em] text-brand-400/90 uppercase">Midnight Snack</p>
                </div>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-2">
                @foreach ($nav as $item)
                    @php $active = request()->routeIs($item['route']); @endphp
                    <a href="{{ route($item['route']) }}"
                        class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ $active ? 'bg-brand-400 font-semibold text-ink-950 shadow-sm shadow-brand-500/30' : 'text-ink-300 hover:bg-white/5 hover:text-white' }}">
                        <x-icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="border-t border-white/5 p-3">
                <div class="mb-2 flex items-center gap-3 rounded-xl px-2 py-1.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-ink-800 text-sm font-bold text-brand-300 ring-1 ring-white/10">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-white">{{ auth()->user()->name }}</p>
                        <p class="text-[11px] text-ink-400">{{ auth()->user()->role->label() }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-ink-400 transition hover:bg-white/5 hover:text-white">
                        <x-icon name="logout" class="h-5 w-5" /> Sign out
                    </button>
                </form>
            </div>
        </aside>

        <div class="lg:pl-64">
            <header class="sticky top-0 z-20 flex items-center justify-between gap-4 border-b border-line bg-white/85 px-4 py-3 backdrop-blur-xl sm:px-6">
                <div class="flex items-center gap-3">
                    <button @click="sidebar = true" class="btn btn-ghost p-2 lg:hidden">
                        <x-icon name="menu" />
                    </button>
                    <div>
                        <h1 class="text-base font-bold text-ink-950 sm:text-lg">{{ $title ?? 'Dashboard' }}</h1>
                        @isset($subtitle)
                            <p class="text-xs text-ink-500">{{ $subtitle }}</p>
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
