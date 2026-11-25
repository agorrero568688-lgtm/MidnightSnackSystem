<x-layouts.storefront title="Order online">
    <script type="application/json" id="order-bootstrap">{!! json_encode($bootstrap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

    <main class="mx-auto max-w-6xl px-4 sm:px-6">
        {{-- Hero --}}
        <section class="relative mt-5 overflow-hidden rounded-3xl bg-ink-950 px-6 py-10 text-white shadow-xl sm:px-10 sm:py-14">
            <div class="absolute -top-24 -right-16 h-64 w-64 rounded-full bg-brand-400/20 blur-3xl"></div>
            <div class="absolute -bottom-32 -left-10 h-64 w-64 rounded-full bg-violet-500/15 blur-3xl"></div>

            <div class="relative max-w-2xl">
                <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-3 py-1 text-xs font-semibold text-brand-200">
                    <span class="h-1.5 w-1.5 rounded-full {{ $orderingOpen ? 'bg-emerald-400' : 'bg-rose-400' }}"></span>
                    {{ $orderingOpen ? 'Open for online orders' : 'Online ordering is paused' }}
                </span>

                <h1 class="mt-4 text-3xl leading-tight font-black tracking-tight sm:text-5xl">
                    Late-night cravings,<br><span class="text-brand-300">delivered hot.</span>
                </h1>

                <p class="mt-4 max-w-xl text-sm leading-relaxed text-ink-300 sm:text-base">
                    Order straight from {{ $config['store_name'] }}. Pay with GCash or cash — we will confirm your order before the kitchen starts.
                </p>

                <div class="mt-6 flex flex-wrap gap-2 text-sm">
                    @if ($config['store_hours'])
                        <span class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-3 py-2 font-medium">
                            <x-icon name="clock" class="h-4 w-4 text-brand-300" /> {{ $config['store_hours'] }}
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
            <div class="mt-5 rounded-2xl border border-brand-200 bg-brand-50 px-5 py-4 text-sm text-brand-900">
                <p class="font-bold">We are not taking online orders right now.</p>
                <p class="mt-1">You can still browse the menu. Please come back during store hours{{ $config['store_hours'] ? ' ('.$config['store_hours'].')' : '' }}.</p>
            </div>
        @endunless

        {{-- Menu --}}
        <section id="menu" class="scroll-mt-24 pt-10">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-bold tracking-[0.2em] text-brand-700 uppercase">The menu</p>
                    <h2 class="mt-1 text-2xl font-black tracking-tight sm:text-3xl">What are you craving?</h2>
                </div>

                <div class="relative w-full sm:w-72">
                    <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-ink-400" />
                    <input x-model="search" type="search" placeholder="Search the menu"
                        class="w-full rounded-xl border border-ink-300 bg-white py-2.5 pr-3 pl-9 text-sm shadow-sm placeholder:text-ink-400 focus:border-violet-400 focus:ring-2 focus:ring-violet-200 focus:outline-none">
                </div>
            </div>

            <div class="mt-5 flex gap-2 overflow-x-auto pb-2">
                <button type="button" @click="activeCategory = 'all'"
                    :class="activeCategory === 'all' ? 'bg-brand-400 text-ink-950 shadow-sm shadow-brand-500/25' : 'bg-white text-ink-600 ring-1 ring-ink-200 hover:bg-ink-50'"
                    class="rounded-full px-4 py-2 text-sm font-semibold whitespace-nowrap transition">All</button>
                @foreach ($sections as $section)
                    <button type="button" @click="activeCategory = {{ $section['id'] }}"
                        :class="activeCategory == {{ $section['id'] }} ? 'bg-brand-400 text-ink-950 shadow-sm shadow-brand-500/25' : 'bg-white text-ink-600 ring-1 ring-ink-200 hover:bg-ink-50'"
                        class="rounded-full px-4 py-2 text-sm font-semibold whitespace-nowrap transition">{{ $section['name'] }}</button>
                @endforeach
            </div>

            @forelse ($sections as $section)
                <div x-show="activeCategory === 'all' || activeCategory == {{ $section['id'] }}" class="mt-8">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-lg font-bold">{{ $section['name'] }}</h3>
                        <span class="text-xs text-ink-500">{{ $section['products']->count() }} item{{ $section['products']->count() === 1 ? '' : 's' }}</span>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($section['products'] as $product)
                            @php
                                $image = $product->image_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($product->image_path) : null;
                                $categoryName = $product->category?->name ?? 'Kitchen special';
                            @endphp

                            <article x-show="productVisible({{ $product->id }})"
                                @click="openProduct({{ $product->id }})"
                                @keydown.enter.self.prevent="openProduct({{ $product->id }})"
                                @keydown.space.self.prevent="openProduct({{ $product->id }})"
                                role="button" tabindex="0" aria-label="View details for {{ $product->name }}"
                                class="group flex cursor-pointer flex-col overflow-hidden rounded-2xl border border-ink-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:border-brand-300 hover:shadow-lift focus-visible:ring-2 focus-visible:ring-violet-400 focus-visible:ring-offset-2 focus-visible:outline-none">
                                <div class="relative h-44 overflow-hidden bg-ink-100 sm:h-48">
                                    <x-product-visual :image="$image" :name="$product->name" :category="$categoryName" class="h-full w-full" />

                                    <div class="pointer-events-none absolute inset-x-0 top-0 flex items-start justify-between gap-2 p-3">
                                        <span class="rounded-full bg-white/90 px-2.5 py-1 text-[10px] font-bold tracking-wider text-ink-700 uppercase shadow-sm backdrop-blur">
                                            {{ $categoryName }}
                                        </span>

                                        @if (! $product->is_available)
                                            <span class="rounded-full bg-ink-950/90 px-2.5 py-1 text-[10px] font-bold tracking-wider text-white uppercase">Sold out</span>
                                        @endif
                                    </div>

                                    <div class="pointer-events-none absolute inset-x-0 bottom-0 flex items-center justify-between bg-gradient-to-t from-ink-950/60 to-transparent px-3 pt-8 pb-2 opacity-0 transition duration-300 group-hover:opacity-100 group-focus-visible:opacity-100">
                                        <span class="text-[11px] font-semibold text-white">Tap for details</span>
                                        <span class="rounded-full bg-white/20 p-1 text-white"><x-icon name="arrow-right" class="h-3.5 w-3.5" /></span>
                                    </div>
                                </div>

                                <div class="flex flex-1 flex-col p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <h4 class="font-bold leading-snug">{{ $product->name }}</h4>
                                        <span class="shrink-0 text-base font-black text-brand-700">{{ \App\Support\Money::format($product->selling_price) }}</span>
                                    </div>
                                    <p class="mt-1 line-clamp-2 text-xs leading-relaxed text-ink-500">
                                        {{ $product->description ?: 'Cooked to order and packed warm.' }}
                                    </p>

                                    <div class="mt-auto flex items-center justify-between gap-3 pt-4">
                                        <span class="text-[11px] text-ink-400">per {{ $product->unit }}</span>

                                        <template x-if="qty({{ $product->id }}) === 0">
                                            <button type="button" @click.stop="add({{ $product->id }})"
                                                @disabled(! $product->is_available || ! $orderingOpen)
                                                class="inline-flex items-center gap-1.5 rounded-xl bg-brand-400 px-3.5 py-2 text-xs font-bold text-ink-950 shadow-sm shadow-brand-500/25 transition hover:bg-brand-300 disabled:cursor-not-allowed disabled:opacity-40">
                                                <x-icon name="plus" class="h-3.5 w-3.5" /> Add
                                            </button>
                                        </template>

                                        <template x-if="qty({{ $product->id }}) > 0">
                                            <div @click.stop class="inline-flex items-center rounded-xl bg-brand-400 text-ink-950">
                                                <button type="button" @click.stop="dec({{ $product->id }})" class="px-2.5 py-2" aria-label="Less">
                                                    <x-icon name="minus" class="h-3.5 w-3.5" />
                                                </button>
                                                <span class="min-w-7 text-center text-xs font-black" x-text="qty({{ $product->id }})"></span>
                                                <button type="button" @click.stop="inc({{ $product->id }})" class="px-2.5 py-2" aria-label="More">
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
                <div class="mt-10 rounded-2xl border border-dashed border-ink-300 bg-white px-6 py-16 text-center">
                    <p class="text-lg font-bold">The menu is being prepared.</p>
                    <p class="mt-1 text-sm text-ink-500">Add products and tick “Show online” in the back office to publish them here.</p>
                </div>
            @endforelse
        </section>

        {{-- Reassurance strip --}}
        <section class="mt-14 grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-ink-200 bg-white p-5">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700"><x-icon name="check" class="h-5 w-5" /></div>
                <p class="mt-3 font-bold">Confirmed by a human</p>
                <p class="mt-1 text-sm text-ink-500">Every order is checked by our staff before it is cooked.</p>
            </div>
            <div class="rounded-2xl border border-ink-200 bg-white p-5">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-100 text-violet-700"><x-icon name="banknote" class="h-5 w-5" /></div>
                <p class="mt-3 font-bold">GCash or cash</p>
                <p class="mt-1 text-sm text-ink-500">Pay online with GCash, or pay the rider or counter in cash.</p>
            </div>
            <div class="rounded-2xl border border-ink-200 bg-white p-5">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-100 text-brand-700"><x-icon name="clock" class="h-5 w-5" /></div>
                <p class="mt-3 font-bold">Follow every step</p>
                <p class="mt-1 text-sm text-ink-500">Your tracking link updates as the kitchen and rider move.</p>
            </div>
        </section>
    </main>

    {{-- Product details modal --}}
    <div x-show="productOpen" x-cloak @keydown.escape.window="closeProduct()"
        class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-labelledby="product-modal-title">
        <div x-show="productOpen" x-transition.opacity class="absolute inset-0 bg-ink-950/70 backdrop-blur-sm" @click="closeProduct()"></div>

        <div class="absolute inset-0 flex items-end justify-center sm:items-center sm:p-6">
            <div x-show="productOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-y-8 opacity-0 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="translate-y-0 opacity-100 sm:scale-100"
                x-transition:leave-end="translate-y-8 opacity-0 sm:translate-y-0 sm:scale-95"
                class="relative flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-t-3xl bg-white shadow-2xl sm:rounded-3xl">
                <div class="relative h-56 shrink-0 overflow-hidden bg-ink-100 sm:h-72">
                    <template x-if="activeProduct?.image">
                        <img :src="activeProduct.image" :alt="activeProduct.name" class="h-full w-full object-cover">
                    </template>

                    <div x-show="!activeProduct?.image" :class="themeFor(activeProduct?.category).gradient"
                        class="relative flex h-full w-full items-center justify-center overflow-hidden bg-gradient-to-br">
                        <div class="absolute -top-8 -right-6 h-32 w-32 rounded-full blur-2xl"
                            :class="themeFor(activeProduct?.category).glow"></div>
                        <div class="absolute -bottom-10 -left-6 h-32 w-32 rounded-full bg-white/50 blur-2xl"></div>

                        <span class="relative flex h-24 w-24 items-center justify-center rounded-full bg-white/55 shadow-sm ring-1 ring-white/70 backdrop-blur-sm">
                            <template x-if="themeFor(activeProduct?.category).icon === 'cup'">
                                <x-icon name="cup" class="h-12 w-12" x-bind:class="themeFor(activeProduct?.category).ink" />
                            </template>
                            <template x-if="themeFor(activeProduct?.category).icon === 'cookie'">
                                <x-icon name="cookie" class="h-12 w-12" x-bind:class="themeFor(activeProduct?.category).ink" />
                            </template>
                            <template x-if="themeFor(activeProduct?.category).icon === 'bowl'">
                                <x-icon name="bowl" class="h-12 w-12" x-bind:class="themeFor(activeProduct?.category).ink" />
                            </template>
                            <template x-if="themeFor(activeProduct?.category).icon === 'utensils'">
                                <x-icon name="utensils" class="h-12 w-12" x-bind:class="themeFor(activeProduct?.category).ink" />
                            </template>
                        </span>

                        <span class="absolute right-5 bottom-2 text-7xl leading-none font-black text-ink-950/10 select-none"
                            x-text="(activeProduct?.name || '?').charAt(0).toUpperCase()"></span>
                    </div>

                    <div class="absolute inset-x-0 top-0 flex items-start justify-between gap-2 p-4">
                        <span class="rounded-full bg-white/90 px-3 py-1 text-[10px] font-bold tracking-wider text-ink-700 uppercase shadow-sm backdrop-blur"
                            x-text="activeProduct?.category || 'Kitchen special'"></span>
                        <button type="button" x-ref="modalClose" @click="closeProduct()"
                            class="rounded-full bg-white/90 p-2 text-ink-700 shadow-sm backdrop-blur transition hover:bg-white hover:text-ink-950"
                            aria-label="Close product details">
                            <x-icon name="x" class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto px-5 py-5 sm:px-7">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 id="product-modal-title" class="text-2xl font-black tracking-tight" x-text="activeProduct?.name"></h2>
                            <p class="mt-1 text-xs font-semibold"
                                :class="activeProduct?.available ? 'text-emerald-700' : 'text-rose-600'"
                                x-text="activeProduct?.available ? 'Available now' : 'Sold out'"></p>
                        </div>
                        <div class="text-right">
                            <p class="text-xl font-black text-brand-700" x-text="money(activeProduct?.price_cents)"></p>
                            <p class="text-[11px] text-ink-400" x-text="'per ' + (activeProduct?.unit || 'serving')"></p>
                        </div>
                    </div>

                    <div class="mt-4 rounded-2xl bg-ink-50 px-4 py-3 text-sm leading-relaxed text-ink-600">
                        <p class="text-[11px] font-bold tracking-wider text-ink-500 uppercase">What you get</p>
                        <p class="mt-1" x-text="activeProduct?.description || 'Cooked to order and packed warm.'"></p>
                    </div>

                    <p x-show="!config.ordering_open" x-cloak
                        class="mt-3 rounded-xl bg-brand-50 px-3 py-2 text-xs font-semibold text-brand-900">
                        Online ordering is paused right now.
                    </p>

                    <div class="mt-5">
                        <label class="label" for="product-note">
                            Special instructions <span class="font-normal text-ink-400 normal-case">(optional)</span>
                        </label>
                        <input id="product-note" x-model="modalNote" type="text" maxlength="200"
                            placeholder="e.g. no onions, extra spicy, extra sauce" class="input">
                        <p class="mt-1 text-[11px] text-ink-500">We'll pass this to the kitchen.</p>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center justify-between gap-4">
                        <div class="inline-flex items-center rounded-xl border border-ink-200 bg-white">
                            <button type="button" @click="modalDec()"
                                class="px-3 py-2.5 text-ink-600 transition hover:bg-ink-50" aria-label="Decrease quantity">
                                <x-icon name="minus" class="h-4 w-4" />
                            </button>
                            <span class="min-w-10 text-center text-sm font-black" x-text="modalQty"></span>
                            <button type="button" @click="modalInc()"
                                class="px-3 py-2.5 text-ink-600 transition hover:bg-ink-50" aria-label="Increase quantity">
                                <x-icon name="plus" class="h-4 w-4" />
                            </button>
                        </div>

                        <p class="text-xs text-ink-500">
                            Total <span class="ml-1 text-lg font-black text-ink-900" x-text="money(modalTotalCents)"></span>
                        </p>
                    </div>
                </div>

                <div class="shrink-0 border-t border-ink-100 bg-white px-5 py-4 sm:px-7">
                    <button type="button" @click="addFromModal()"
                        :disabled="!activeProduct?.available || !config.ordering_open"
                        class="btn btn-primary w-full py-3 text-sm font-black">
                        <x-icon name="cart" class="h-4 w-4" />
                        <span x-text="(qty(activeProduct?.id) > 0 ? 'Update cart' : 'Add to cart') + ' — ' + money(modalTotalCents)"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-layouts.storefront>
