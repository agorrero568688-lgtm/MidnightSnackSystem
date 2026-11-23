<x-layouts.storefront title="Order online">
    <script type="application/json" id="order-bootstrap">{!! json_encode($bootstrap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

    <main class="mx-auto max-w-6xl px-4 sm:px-6">
        {{-- Hero --}}
        <section class="relative mt-5 overflow-hidden rounded-3xl bg-slate-950 px-6 py-10 text-white shadow-xl sm:px-10 sm:py-14">
            <div class="absolute -top-24 -right-16 h-64 w-64 rounded-full bg-amber-400/20 blur-3xl"></div>
            <div class="absolute -bottom-32 -left-10 h-64 w-64 rounded-full bg-rose-500/10 blur-3xl"></div>

            <div class="relative max-w-2xl">
                <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-3 py-1 text-xs font-semibold text-amber-200">
                    <span class="h-1.5 w-1.5 rounded-full {{ $orderingOpen ? 'bg-emerald-400' : 'bg-rose-400' }}"></span>
                    {{ $orderingOpen ? 'Open for online orders' : 'Online ordering is paused' }}
                </span>

                <h1 class="mt-4 text-3xl leading-tight font-black tracking-tight sm:text-5xl">
                    Late-night cravings,<br><span class="text-amber-300">delivered hot.</span>
                </h1>

                <p class="mt-4 max-w-xl text-sm leading-relaxed text-slate-300 sm:text-base">
                    Order straight from {{ $config['store_name'] }}. Pay with GCash or cash — we will confirm your order before the kitchen starts.
                </p>

                <div class="mt-6 flex flex-wrap gap-2 text-sm">
                    @if ($config['delivery_enabled'])
                        <span class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-3 py-2 font-medium">
                            <x-icon name="truck" class="h-4 w-4 text-amber-300" /> Delivery {{ $config['delivery_estimate'] }}
                        </span>
                    @endif
                    @if ($config['pickup_enabled'])
                        <span class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-3 py-2 font-medium">
                            <x-icon name="store" class="h-4 w-4 text-amber-300" /> Pickup {{ $config['pickup_estimate'] }}
                        </span>
                    @endif
                    @if ($config['store_hours'])
                        <span class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-3 py-2 font-medium">
                            <x-icon name="clock" class="h-4 w-4 text-amber-300" /> {{ $config['store_hours'] }}
                        </span>
                    @endif
                </div>

                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <a href="#menu" class="btn btn-primary">Browse the menu</a>
                    <a href="{{ route('orders.lookup') }}" class="btn border border-white/20 text-white hover:bg-white/10">Track an order</a>
                </div>
            </div>
        </section>

        @unless ($orderingOpen)
            <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
                <p class="font-bold">We are not taking online orders right now.</p>
                <p class="mt-1">You can still browse the menu. Please come back during store hours{{ $config['store_hours'] ? ' ('.$config['store_hours'].')' : '' }}.</p>
            </div>
        @endunless

        {{-- Menu --}}
        <section id="menu" class="scroll-mt-24 pt-10">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-bold tracking-[0.2em] text-amber-600 uppercase">The menu</p>
                    <h2 class="mt-1 text-2xl font-black tracking-tight sm:text-3xl">What are you craving?</h2>
                </div>

                <div class="relative w-full sm:w-72">
                    <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input x-model="search" type="search" placeholder="Search the menu"
                        class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pr-3 pl-9 text-sm shadow-sm placeholder:text-slate-400 focus:border-amber-400 focus:ring-2 focus:ring-amber-200 focus:outline-none">
                </div>
            </div>

            <div class="mt-5 flex gap-2 overflow-x-auto pb-2">
                <button type="button" @click="activeCategory = 'all'"
                    :class="activeCategory === 'all' ? 'bg-slate-950 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50'"
                    class="rounded-full px-4 py-2 text-sm font-semibold whitespace-nowrap transition">All</button>
                @foreach ($sections as $section)
                    <button type="button" @click="activeCategory = {{ $section['id'] }}"
                        :class="activeCategory == {{ $section['id'] }} ? 'bg-slate-950 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50'"
                        class="rounded-full px-4 py-2 text-sm font-semibold whitespace-nowrap transition">{{ $section['name'] }}</button>
                @endforeach
            </div>

            @forelse ($sections as $section)
                <div x-show="activeCategory === 'all' || activeCategory == {{ $section['id'] }}" class="mt-8">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-lg font-bold">{{ $section['name'] }}</h3>
                        <span class="text-xs text-slate-500">{{ $section['products']->count() }} item{{ $section['products']->count() === 1 ? '' : 's' }}</span>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($section['products'] as $product)
                            @php
                                $image = $product->image_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($product->image_path) : null;
                            @endphp

                            <article x-show="productVisible({{ $product->id }})"
                                class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                                <div class="relative h-36 overflow-hidden bg-gradient-to-br from-amber-100 via-orange-100 to-rose-100">
                                    @if ($image)
                                        <img src="{{ $image }}" alt="{{ $product->name }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                    @else
                                        <div class="flex h-full items-center justify-center">
                                            <span class="text-5xl font-black text-amber-500/40">{{ mb_strtoupper(mb_substr($product->name, 0, 1)) }}</span>
                                        </div>
                                    @endif

                                    @if ($product->isOutOfStock())
                                        <span class="absolute top-3 left-3 rounded-full bg-slate-950/90 px-2.5 py-1 text-[10px] font-bold tracking-wider text-white uppercase">Sold out</span>
                                    @elseif ($product->isLowStock())
                                        <span class="absolute top-3 left-3 rounded-full bg-amber-400 px-2.5 py-1 text-[10px] font-bold tracking-wider text-slate-950 uppercase">Few left</span>
                                    @endif
                                </div>

                                <div class="flex flex-1 flex-col p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <h4 class="font-bold leading-snug">{{ $product->name }}</h4>
                                        <span class="shrink-0 font-black text-amber-600">{{ \App\Support\Money::format($product->selling_price) }}</span>
                                    </div>
                                    <p class="mt-1 line-clamp-2 text-xs leading-relaxed text-slate-500">
                                        {{ $product->description ?: 'Cooked to order and packed warm.' }}
                                    </p>

                                    <div class="mt-4 flex items-center justify-between gap-3">
                                        <span class="text-[11px] text-slate-400">per {{ $product->unit }}</span>

                                        <template x-if="qty({{ $product->id }}) === 0">
                                            <button type="button" @click="add({{ $product->id }})"
                                                @disabled($product->isOutOfStock() || ! $orderingOpen)
                                                class="inline-flex items-center gap-1.5 rounded-xl bg-slate-950 px-3.5 py-2 text-xs font-bold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40">
                                                <x-icon name="plus" class="h-3.5 w-3.5" /> Add
                                            </button>
                                        </template>

                                        <template x-if="qty({{ $product->id }}) > 0">
                                            <div class="inline-flex items-center rounded-xl bg-amber-400 text-slate-950">
                                                <button type="button" @click="dec({{ $product->id }})" class="px-2.5 py-2" aria-label="Less">
                                                    <x-icon name="minus" class="h-3.5 w-3.5" />
                                                </button>
                                                <span class="min-w-7 text-center text-xs font-black" x-text="qty({{ $product->id }})"></span>
                                                <button type="button" @click="inc({{ $product->id }})" class="px-2.5 py-2" aria-label="More">
                                                    <x-icon name="plus" class="h-3.5 w-3.5" />
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="mt-10 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                    <p class="text-lg font-bold">The menu is being prepared.</p>
                    <p class="mt-1 text-sm text-slate-500">Add products and tick “Show online” in the back office to publish them here.</p>
                </div>
            @endforelse
        </section>

        {{-- Reassurance strip --}}
        <section class="mt-14 grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700"><x-icon name="check" class="h-5 w-5" /></div>
                <p class="mt-3 font-bold">Confirmed by a human</p>
                <p class="mt-1 text-sm text-slate-500">Every order is checked by our staff before it is cooked.</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-100 text-sky-700"><x-icon name="banknote" class="h-5 w-5" /></div>
                <p class="mt-3 font-bold">GCash or cash</p>
                <p class="mt-1 text-sm text-slate-500">Pay online with GCash, or pay the rider or counter in cash.</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-700"><x-icon name="clock" class="h-5 w-5" /></div>
                <p class="mt-3 font-bold">Follow every step</p>
                <p class="mt-1 text-sm text-slate-500">Your tracking link updates as the kitchen and rider move.</p>
            </div>
        </section>
    </main>
</x-layouts.storefront>
