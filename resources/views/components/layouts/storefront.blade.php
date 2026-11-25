@props(['title' => 'Order online', 'showCart' => true])

@php
    $storeName = \App\Models\Setting::get('store_name');
    $storeAddress = \App\Models\Setting::get('store_address');
    $storePhone = \App\Models\Setting::get('store_phone');
    $storeHours = \App\Models\Setting::get('store_hours');
@endphp

<!DOCTYPE html>
<html lang="en" class="h-full scroll-smooth bg-canvas">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#111315">
    <title>{{ $title }} · {{ $storeName }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body x-data="orderCart()" class="min-h-full bg-canvas font-sans text-ink-900 antialiased">
    <header class="sticky top-0 z-30 border-b border-white/10 bg-ink-950/90 text-white backdrop-blur-xl">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
            <a href="{{ route('storefront') }}" class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-400 text-lg font-black text-ink-950 shadow-glow-brand">D</span>
                <span class="leading-tight">
                    <span class="block text-sm font-bold">{{ $storeName }}</span>
                    <span class="block text-[10px] font-semibold tracking-[0.18em] text-brand-300 uppercase">Order online</span>
                </span>
            </a>

            <nav class="flex items-center gap-1 sm:gap-2">
                <a href="{{ route('storefront') }}" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-ink-300 transition hover:bg-white/10 hover:text-white sm:block">Menu</a>
                <a href="{{ route('orders.lookup') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-ink-300 transition hover:bg-white/10 hover:text-white">Track order</a>

                @auth
                    <a href="{{ route(auth()->user()->homeRoute()) }}" class="hidden rounded-lg border border-white/15 px-3 py-2 text-sm font-semibold text-white transition hover:bg-white/10 sm:block">Staff</a>
                @else
                    <a href="{{ route('login') }}" class="hidden rounded-lg border border-white/15 px-3 py-2 text-sm font-semibold text-white transition hover:bg-white/10 sm:block">Staff login</a>
                @endauth

                @if ($showCart)
                    <button type="button" @click="openCart()" class="relative inline-flex items-center gap-2 rounded-xl bg-brand-400 px-3 py-2 text-sm font-bold text-ink-950 shadow-sm shadow-brand-500/25 transition hover:bg-brand-300">
                        <x-icon name="cart" class="h-4 w-4" />
                        <span class="hidden sm:inline">Cart</span>
                        <span x-show="count > 0" x-cloak x-text="count" class="absolute -top-2 -right-2 flex h-5 min-w-5 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white"></span>
                    </button>
                @endif
            </nav>
        </div>
    </header>

    {{ $slot }}

    <footer class="mt-16 border-t border-white/5 bg-gradient-to-b from-ink-950 to-ink-900 text-ink-400">
        <div class="mx-auto grid max-w-6xl gap-8 px-4 py-10 sm:px-6 md:grid-cols-3">
            <div>
                <div class="flex items-center gap-2 text-white">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-400 text-sm font-black text-ink-950">D</span>
                    <span class="font-bold">{{ $storeName }}</span>
                </div>
                <p class="mt-3 text-sm leading-relaxed">{{ $storeAddress ?: 'Cooked fresh, delivered warm.' }}</p>
            </div>
            <div class="text-sm">
                <p class="mb-2 text-[11px] font-bold tracking-wider text-brand-300 uppercase">Hours</p>
                <p>{{ $storeHours ?: 'Open 24/7, daily' }}</p>
                @if ($storePhone)
                    <p class="mt-2">{{ $storePhone }}</p>
                @endif
            </div>
            <div class="text-sm">
                <p class="mb-2 text-[11px] font-bold tracking-wider text-brand-300 uppercase">Quick links</p>
                <p><a href="{{ route('storefront') }}" class="hover:text-white">Menu</a></p>
                <p class="mt-1"><a href="{{ route('orders.lookup') }}" class="hover:text-white">Track an order</a></p>
                <p class="mt-1"><a href="{{ route('login') }}" class="hover:text-white">Staff login</a></p>
            </div>
        </div>
        <div class="border-t border-white/10 py-4 text-center text-xs text-ink-500">
            &copy; {{ date('Y') }} {{ $storeName }}. Salamat po!
        </div>
    </footer>

    @if ($showCart)
        <div x-show="open" x-cloak class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-label="Your cart">
            <div x-show="open" x-transition.opacity class="absolute inset-0 bg-ink-950/60" @click="closeCart()"></div>

            <aside x-show="open" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="absolute inset-y-0 right-0 flex w-full max-w-md flex-col bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-ink-100 px-5 py-4">
                    <div>
                        <h2 class="text-lg font-bold">Your cart</h2>
                        <p class="text-xs text-ink-500" x-text="count + (count === 1 ? ' item' : ' items')"></p>
                    </div>
                    <button type="button" @click="closeCart()" class="rounded-lg p-2 text-ink-500 hover:bg-ink-100">
                        <x-icon name="x" class="h-5 w-5" />
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-5 py-4">
                    <template x-if="items.length === 0">
                        <div class="py-16 text-center">
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-brand-100 text-brand-700">
                                <x-icon name="cart" class="h-8 w-8" />
                            </div>
                            <p class="mt-4 font-semibold">Your cart is empty</p>
                            <p class="mt-1 text-sm text-ink-500">Add something from the menu to get started.</p>
                            <button type="button" @click="closeCart()" class="btn btn-primary mt-5">Browse the menu</button>
                        </div>
                    </template>

                    <div class="space-y-4">
                        <template x-for="item in items" :key="item.id">
                            <div class="rounded-xl border border-ink-200 p-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold" x-text="item.name"></p>
                                        <p class="text-xs text-ink-500" x-text="money(item.price_cents) + ' each'"></p>
                                    </div>
                                    <button type="button" @click="remove(item.id)" class="rounded p-1 text-ink-400 hover:bg-rose-50 hover:text-rose-600" aria-label="Remove item">
                                        <x-icon name="trash" class="h-4 w-4" />
                                    </button>
                                </div>

                                <div class="mt-3 flex items-center justify-between gap-3">
                                    <div class="inline-flex items-center rounded-lg border border-ink-200">
                                        <button type="button" @click="dec(item.id)" class="px-2.5 py-1.5 text-ink-600 hover:bg-ink-50" aria-label="Less">
                                            <x-icon name="minus" class="h-3.5 w-3.5" />
                                        </button>
                                        <span class="min-w-8 text-center text-sm font-bold" x-text="item.qty"></span>
                                        <button type="button" @click="inc(item.id)" class="px-2.5 py-1.5 text-ink-600 hover:bg-ink-50" aria-label="More">
                                            <x-icon name="plus" class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                    <p class="text-sm font-bold" x-text="money(item.price_cents * item.qty)"></p>
                                </div>

                                <input type="text" maxlength="200" placeholder="Add a note (e.g. extra sauce, no onions)"
                                    :value="item.note" @input="item.note = $event.target.value"
                                    class="mt-2 w-full rounded-lg border border-ink-200 px-2.5 py-1.5 text-xs placeholder:text-ink-400 focus:border-violet-400 focus:outline-none">
                            </div>
                        </template>
                    </div>
                </div>

                <div class="border-t border-ink-100 bg-ink-50 px-5 py-4">
                    <div class="space-y-1.5 text-sm">
                        <div class="flex justify-between text-ink-600">
                            <span>Subtotal</span><span x-text="money(subtotalCents)"></span>
                        </div>
                        <div class="flex justify-between text-ink-600">
                            <span>Delivery fee</span>
                            <span x-text="orderType === 'pickup' ? 'Pickup' : (deliveryFeeCents === 0 ? 'Free delivery' : money(deliveryFeeCents))"></span>
                        </div>
                        <div class="flex justify-between border-t border-ink-200 pt-2 text-base font-black">
                            <span>Total</span><span x-text="money(totalCents)"></span>
                        </div>
                    </div>

                    <p x-show="belowMinimum" x-cloak class="mt-2 text-xs font-semibold text-rose-600"
                        x-text="'Minimum order is ' + money(config.minimum_order_cents) + '.'"></p>

                    <button type="button" @click="clearAll()" x-show="items.length > 0"
                        class="btn btn-danger w-full mt-3">Clear cart</button>

                    <a href="{{ route('orders.checkout') }}" @click="closeCart()"
                        class="btn btn-primary mt-3 w-full" :class="items.length === 0 && 'pointer-events-none opacity-40'">
                        Go to checkout
                    </a>
                    <p class="mt-2 text-center text-[11px] text-ink-500">Delivery fee is confirmed before you pay.</p>
                </div>
            </aside>
        </div>
    @endif

    <div x-show="toast" x-cloak x-transition
        class="fixed bottom-5 left-1/2 z-[60] -translate-x-1/2 rounded-full bg-ink-950 px-4 py-2 text-sm font-semibold text-white shadow-xl">
        <span x-text="toast"></span>
    </div>

    @livewireScripts
</body>
</html>
