<x-layouts.storefront title="Checkout">
    <script type="application/json" id="order-bootstrap">{!! json_encode($bootstrap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

    <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <a href="{{ route('storefront') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-900">
                    <x-icon name="arrow-left" class="h-3.5 w-3.5" /> Back to the menu
                </a>
                <h1 class="mt-2 text-3xl font-black tracking-tight">Checkout</h1>
                <p class="mt-1 text-sm text-slate-500">Tell us where to send it and how you would like to pay.</p>
            </div>
            <div class="rounded-xl bg-white px-4 py-2 text-sm shadow-sm ring-1 ring-slate-200">
                <span class="text-slate-500">Total</span>
                <span class="ml-2 text-lg font-black" x-text="money(totalCents)"></span>
            </div>
        </div>

        @unless ($orderingOpen)
            <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
                Online ordering is paused right now, so the place-order button is disabled.
            </div>
        @endunless

        <div x-show="items.length === 0" x-cloak class="mt-8 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                <x-icon name="cart" class="h-8 w-8" />
            </div>
            <p class="mt-4 text-lg font-bold">Your cart is empty</p>
            <p class="mt-1 text-sm text-slate-500">Pick something from the menu first.</p>
            <a href="{{ route('storefront') }}" class="btn btn-primary mt-5">Browse the menu</a>
        </div>

        <div x-show="items.length > 0" x-cloak class="mt-6 grid gap-6 lg:grid-cols-5">
            <form action="{{ route('orders.store') }}" method="POST" @submit.prevent="submit" class="space-y-5 lg:col-span-3">
                @csrf
                <input type="hidden" name="items" :value="JSON.stringify(items.map((item) => ({ product_id: item.id, qty: item.qty, note: item.note })))">
                <input type="hidden" name="order_type" :value="orderType">
                <input type="hidden" name="payment_method" :value="paymentMethod">

                <div x-show="formError" x-cloak class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">
                    <span x-text="formError"></span>
                </div>

                {{-- Delivery or pickup --}}
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-950 text-xs font-black text-white">1</span>
                        <h2 class="font-bold">How would you like it?</h2>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        @if ($config['delivery_enabled'])
                            <button type="button" @click="setOrderType('delivery')"
                                :class="orderType === 'delivery' ? 'border-amber-400 bg-amber-50 ring-2 ring-amber-200' : 'border-slate-200 bg-white hover:border-slate-300'"
                                class="rounded-xl border p-4 text-left transition">
                                <div class="flex items-center gap-2">
                                    <x-icon name="truck" class="h-5 w-5 text-amber-600" />
                                    <p class="mt-2 font-bold">Delivery</p>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">To your door. Fee is shown before you pay.</p>
                            </button>
                        @endif

                        @if ($config['pickup_enabled'])
                            <button type="button" @click="setOrderType('pickup')"
                                :class="orderType === 'pickup' ? 'border-amber-400 bg-amber-50 ring-2 ring-amber-200' : 'border-slate-200 bg-white hover:border-slate-300'"
                                class="rounded-xl border p-4 text-left transition">
                                <div class="flex items-center gap-2">
                                    <x-icon name="store" class="h-5 w-5 text-amber-600" />
                                    <p class="mt-2 font-bold">Pickup</p>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">Collect at the counter. No delivery fee.</p>
                            </button>
                        @endif
                    </div>
                </section>

                {{-- Contact --}}
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-950 text-xs font-black text-white">2</span>
                        <h2 class="font-bold">Who is it for?</h2>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-1">
                            <label class="label">Name</label>
                            <input x-model="customerName" type="text" autocomplete="name" maxlength="100" placeholder="Your name"
                                class="input" :class="error('customer_name') && 'border-rose-400'">
                            <p class="mt-1 text-xs text-rose-600" x-text="error('customer_name')"></p>
                        </div>

                        <div class="sm:col-span-1">
                            <label class="label">Mobile number</label>
                            <input x-model="customerPhone" type="tel" autocomplete="tel" maxlength="30" placeholder="0917 123 4567"
                                class="input" :class="error('customer_phone') && 'border-rose-400'">
                            <p class="mt-1 text-xs text-rose-600" x-text="error('customer_phone')"></p>
                            <p class="mt-1 text-[11px] text-slate-500">The rider or cashier will call this number.</p>
                        </div>

                        <div class="sm:col-span-2" x-show="orderType === 'delivery'" x-cloak>
                            <label class="label">Delivery address</label>
                            <textarea x-model="address" rows="2" maxlength="300" placeholder="House / unit, street, barangay, city, landmark"
                                class="input" :class="error('delivery_address') && 'border-rose-400'"></textarea>
                            <p class="mt-1 text-xs text-rose-600" x-text="error('delivery_address')"></p>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="label">Email <span class="text-slate-400 normal-case">(optional)</span></label>
                            <input x-model="customerEmail" type="email" autocomplete="email" maxlength="150" placeholder="you@example.com"
                                class="input" :class="error('customer_email') && 'border-rose-400'">
                            <p class="mt-1 text-xs text-rose-600" x-text="error('customer_email')"></p>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="label">Notes for the kitchen <span class="text-slate-400 normal-case">(optional)</span></label>
                            <textarea x-model="notes" rows="2" maxlength="500" placeholder="Allergies, gate code, “call when outside”…"
                                class="input"></textarea>
                        </div>
                    </div>
                </section>

                {{-- Payment --}}
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-950 text-xs font-black text-white">3</span>
                        <h2 class="font-bold">How will you pay?</h2>
                    </div>

                    <div class="mt-4 space-y-2.5">
                        <template x-for="option in paymentOptions" :key="option.value">
                            <button type="button" @click="paymentMethod = option.value"
                                :class="paymentMethod === option.value ? 'border-amber-400 bg-amber-50 ring-2 ring-amber-200' : 'border-slate-200 bg-white hover:border-slate-300'"
                                class="flex w-full items-start gap-3 rounded-xl border p-4 text-left transition">
                                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2"
                                    :class="paymentMethod === option.value ? 'border-amber-500 bg-amber-500' : 'border-slate-300'">
                                    <span class="h-1.5 w-1.5 rounded-full bg-white" x-show="paymentMethod === option.value"></span>
                                </span>
                                <span>
                                    <span class="block text-sm font-bold" x-text="option.label"></span>
                                    <span class="mt-0.5 block text-xs text-slate-500" x-text="option.description"></span>
                                </span>
                            </button>
                        </template>
                    </div>

                    <div x-show="paymentMethod === 'gcash'" x-cloak class="mt-4 rounded-xl border border-sky-200 bg-sky-50 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-sky-900">Send your GCash payment</p>
                                @if ($config['gcash_name'] || $config['gcash_number'])
                                    <p class="mt-1 text-sm text-sky-800">
                                        {{ $config['gcash_name'] ?: 'Account name' }}
                                        @if ($config['gcash_number'])
                                            · <span class="font-mono font-bold">{{ $config['gcash_number'] }}</span>
                                            <button type="button" @click="copy('{{ $config['gcash_number'] }}')" class="ml-1 inline-flex items-center gap-1 rounded bg-white px-1.5 py-0.5 text-[10px] font-bold text-sky-700 ring-1 ring-sky-200">
                                                <x-icon name="copy" class="h-3 w-3" /> <span x-text="copied ? 'Copied' : 'Copy'"></span>
                                            </button>
                                        @endif
                                    </p>
                                @else
                                    <p class="mt-1 text-sm text-sky-800">GCash details are sent when we confirm your order. You can also add your reference later on the tracking page.</p>
                                @endif

                                <p class="mt-2 text-xs text-sky-700">
                                    You can place the order now and send the reference afterwards — your tracking page will remind you.
                                </p>
                            </div>

                            @if ($config['gcash_qr_url'])
                                <img src="{{ $config['gcash_qr_url'] }}" alt="GCash QR code" class="h-28 w-28 rounded-lg bg-white object-contain p-1 ring-1 ring-sky-200">
                            @endif
                        </div>

                        <div class="mt-3">
                            <label class="label text-sky-900!">GCash reference number</label>
                            <input x-model="reference" type="text" maxlength="100" placeholder="e.g. 1234567890123"
                                class="input font-mono" :class="error('payment_reference') && 'border-rose-400'">
                            <p class="mt-1 text-xs text-rose-600" x-text="error('payment_reference')"></p>
                        </div>
                    </div>

                    <div x-show="paymentMethod !== 'gcash'" x-cloak class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                        Pay when you receive your order. We will call to confirm before the kitchen starts.
                    </div>
                </section>

                <button type="submit" @disabled(! $orderingOpen) :disabled="submitting"
                    class="btn btn-primary w-full py-3 text-base">
                    <span x-show="!submitting">Place order · <span x-text="money(totalCents)"></span></span>
                    <span x-show="submitting" x-cloak>Placing your order…</span>
                </button>

                <p class="text-center text-[11px] text-slate-500">
                    By placing the order you agree to be contacted about it. Prices are confirmed by the store.
                </p>
            </form>

            {{-- Summary --}}
            <aside class="lg:col-span-2">
                <div class="sticky top-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h2 class="font-bold">Order summary</h2>
                        <a href="{{ route('storefront') }}" class="text-xs font-semibold text-amber-600 hover:text-amber-700">Edit</a>
                    </div>

                    <div class="mt-4 space-y-3">
                        <template x-for="item in items" :key="item.id">
                            <div class="flex items-start justify-between gap-3 text-sm">
                                <div class="min-w-0">
                                    <p class="font-medium"><span x-text="item.qty"></span>× <span x-text="item.name"></span></p>
                                    <p x-show="item.note" class="mt-0.5 text-[11px] text-amber-700" x-text="'“' + item.note + '”'"></p>
                                </div>
                                <span class="shrink-0 font-semibold" x-text="money(item.price_cents * item.qty)"></span>
                            </div>
                        </template>
                    </div>

                    <div class="mt-5 space-y-1.5 border-t border-slate-100 pt-4 text-sm">
                        <div class="flex justify-between text-slate-600">
                            <span>Subtotal</span><span x-text="money(subtotalCents)"></span>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>Delivery fee</span>
                            <span x-text="orderType === 'pickup' ? 'Pickup' : (deliveryFeeCents === 0 ? 'Free' : money(deliveryFeeCents))"></span>
                        </div>
                        <div class="flex justify-between border-t border-slate-100 pt-2 text-base font-black">
                            <span>Total</span><span x-text="money(totalCents)"></span>
                        </div>
                    </div>

                    <p x-show="belowMinimum" x-cloak class="mt-3 rounded-lg bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700"
                        x-text="'Minimum order is ' + money(config.minimum_order_cents) + '. Add a little more to continue.'"></p>

                    <div class="mt-4 rounded-xl bg-slate-50 p-3 text-xs text-slate-500">
                        <p class="font-semibold text-slate-700">Need help?</p>
                        <p class="mt-1">
                            @if ($config['store_phone'])
                                Call or text {{ $config['store_phone'] }}.
                            @else
                                Message us after placing the order — your tracking page has the details.
                            @endif
                        </p>
                    </div>
                </div>
            </aside>
        </div>
    </main>
</x-layouts.storefront>
