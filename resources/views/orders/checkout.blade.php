<x-layouts.storefront title="Checkout">
    <script type="application/json" id="order-bootstrap">{!! json_encode($bootstrap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

    <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <a href="{{ route('storefront') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-ink-500 hover:text-ink-900">
                    <x-icon name="arrow-left" class="h-3.5 w-3.5" /> Back to the menu
                </a>
                <h1 class="mt-2 text-3xl font-black tracking-tight">Checkout</h1>
                <p class="mt-1 text-sm text-ink-500">Tell us where to send it and how you would like to pay.</p>
            </div>
            <div class="rounded-xl bg-white px-4 py-2 text-sm shadow-sm ring-1 ring-ink-200">
                <span class="text-ink-500">Total</span>
                <span class="ml-2 text-lg font-black" x-text="money(totalCents)"></span>
            </div>
        </div>

        @unless ($orderingOpen)
            <div class="mt-5 rounded-2xl border border-brand-200 bg-brand-50 px-5 py-4 text-sm text-brand-900">
                Online ordering is paused right now, so the place-order button is disabled.
            </div>
        @endunless

        <div x-show="items.length === 0" x-cloak class="mt-8 rounded-2xl border border-dashed border-ink-300 bg-white px-6 py-16 text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-brand-100 text-brand-700">
                <x-icon name="cart" class="h-8 w-8" />
            </div>
            <p class="mt-4 text-lg font-bold">Your cart is empty</p>
            <p class="mt-1 text-sm text-ink-500">Pick something from the menu first.</p>
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
                <section class="rounded-2xl border border-ink-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-400 text-xs font-black text-ink-950 shadow-sm shadow-brand-500/25">1</span>
                        <h2 class="font-bold">How would you like it?</h2>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        @if ($config['delivery_enabled'])
                            <button type="button" @click="setOrderType('delivery')"
                                :class="orderType === 'delivery' ? 'border-brand-400 bg-brand-50 ring-2 ring-brand-200' : 'border-ink-200 bg-white hover:border-ink-300'"
                                class="rounded-xl border p-4 text-left transition">
                                <div class="flex items-center gap-2">
                                    <x-icon name="truck" class="h-5 w-5 text-brand-700" />
                                    <p class="mt-2 font-bold">Delivery</p>
                                </div>
                                <p class="mt-1 text-xs text-ink-500">To your door. Fee is shown before you pay.</p>
                            </button>
                        @endif

                        @if ($config['pickup_enabled'])
                            <button type="button" @click="setOrderType('pickup')"
                                :class="orderType === 'pickup' ? 'border-brand-400 bg-brand-50 ring-2 ring-brand-200' : 'border-ink-200 bg-white hover:border-ink-300'"
                                class="rounded-xl border p-4 text-left transition">
                                <div class="flex items-center gap-2">
                                    <x-icon name="store" class="h-5 w-5 text-brand-700" />
                                    <p class="mt-2 font-bold">Pickup</p>
                                </div>
                                <p class="mt-1 text-xs text-ink-500">Collect at the counter. No delivery fee.</p>
                            </button>
                        @endif
                    </div>
                </section>

                {{-- Contact --}}
                <section class="rounded-2xl border border-ink-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-400 text-xs font-black text-ink-950 shadow-sm shadow-brand-500/25">2</span>
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
                                class="input" :class="error('customer_phone') && 'border-rose-400'"
                                x-on:input.debounce.600="fetchSuggestions">
                            <p class="mt-1 text-xs text-rose-600" x-text="error('customer_phone')"></p>
                            <p class="mt-1 text-[11px] text-ink-500">The rider or cashier will call this number.</p>
                        </div>

                        <div x-show="suggestions.length > 0" x-cloak class="mt-3 sm:col-span-2">
                            <div class="rounded-xl border border-brand-200 bg-brand-50 px-4 py-3">
                                <p class="text-sm font-bold text-brand-900">You've ordered from us before — want to repeat?</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <template x-for="s in suggestions" :key="s.id">
                                        <button type="button" @click="addToCart(s.id)"
                                            class="rounded-lg border border-brand-300 bg-white px-3 py-2 text-xs font-semibold text-brand-900 transition hover:bg-brand-100">
                                            <span x-text="s.name"></span>
                                            <span class="ml-1 font-mono text-ink-500" x-text="money(s.price_cents)"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div class="sm:col-span-2" x-show="orderType === 'delivery'" x-cloak>
                            <label class="label">Delivery address</label>
                            <textarea x-model="address" rows="2" maxlength="300" placeholder="House / unit, street, barangay, city, landmark"
                                class="input" :class="error('delivery_address') && 'border-rose-400'"></textarea>
                            <p class="mt-1 text-xs text-rose-600" x-text="error('delivery_address')"></p>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="label">Email <span class="text-ink-400 normal-case">(optional)</span></label>
                            <input x-model="customerEmail" type="email" autocomplete="email" maxlength="150" placeholder="you@example.com"
                                class="input" :class="error('customer_email') && 'border-rose-400'">
                            <p class="mt-1 text-xs text-rose-600" x-text="error('customer_email')"></p>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="label">Notes for the kitchen <span class="text-ink-400 normal-case">(optional)</span></label>
                            <textarea x-model="notes" rows="2" maxlength="500" placeholder="Allergies, gate code, “call when outside”…"
                                class="input"></textarea>
                        </div>
                    </div>
                </section>

                {{-- Payment --}}
                <section class="rounded-2xl border border-ink-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-400 text-xs font-black text-ink-950 shadow-sm shadow-brand-500/25">3</span>
                        <h2 class="font-bold">How will you pay?</h2>
                    </div>

                    <div class="mt-4 space-y-2.5">
                        <template x-for="option in paymentOptions" :key="option.value">
                            <button type="button" @click="paymentMethod = option.value"
                                :class="paymentMethod === option.value ? 'border-brand-400 bg-brand-50 ring-2 ring-brand-200' : 'border-ink-200 bg-white hover:border-ink-300'"
                                class="flex w-full items-start gap-3 rounded-xl border p-4 text-left transition">
                                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2"
                                    :class="paymentMethod === option.value ? 'border-brand-500 bg-brand-500' : 'border-ink-300'">
                                    <span class="h-1.5 w-1.5 rounded-full bg-white" x-show="paymentMethod === option.value"></span>
                                </span>
                                <span>
                                    <span class="block text-sm font-bold" x-text="option.label"></span>
                                    <span class="mt-0.5 block text-xs text-ink-500" x-text="option.description"></span>
                                </span>
                            </button>
                        </template>
                    </div>

                    <div x-show="paymentMethod === 'gcash'" x-cloak class="mt-4 rounded-xl border border-violet-200 bg-violet-50 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="min-w-0 break-words">
                                <p class="text-sm font-bold text-violet-900">Send your GCash payment</p>
                                @if ($config['gcash_name'] || $config['gcash_number'])
                                    <p class="mt-1 text-sm text-violet-800">
                                        {{ $config['gcash_name'] ?: 'Account name' }}
                                        @if ($config['gcash_number'])
                                            · <span class="font-mono font-bold">{{ $config['gcash_number'] }}</span>
                                            <button type="button" @click="copy('{{ $config['gcash_number'] }}')" class="ml-1 inline-flex items-center gap-1 rounded bg-white px-1.5 py-0.5 text-[10px] font-bold text-violet-700 ring-1 ring-violet-200">
                                                <x-icon name="copy" class="h-3 w-3" /> <span x-text="copied ? 'Copied' : 'Copy'"></span>
                                            </button>
                                        @endif
                                    </p>
                                @else
                                    <p class="mt-1 text-sm text-violet-800">GCash details are sent when we confirm your order.</p>
                                @endif

                                <p class="mt-2 text-xs text-violet-700">
                                    Scan the QR or send to the number above, then upload a screenshot of the receipt to place your order.
                                </p>
                            </div>

                            <x-gcash-qr :src="$config['gcash_qr_url']" />
                        </div>

                        <div class="mt-3">
                            <label class="label text-violet-900!">GCash receipt screenshot</label>

                            <div class="rounded-xl border-2 border-dashed border-violet-300 bg-white/70 p-3 text-center"
                                :class="error('payment_proof') && 'border-rose-400'">
                                <template x-if="proofPreview">
                                    <div class="space-y-2">
                                        <img :src="proofPreview" alt="GCash receipt preview" class="mx-auto max-h-56 rounded-lg object-contain ring-1 ring-violet-200">
                                        <p class="truncate text-xs font-medium text-ink-600" x-text="proofName"></p>
                                        <button type="button" @click="clearProof()" class="text-xs font-semibold text-rose-600 hover:text-rose-700">
                                            Remove screenshot
                                        </button>
                                    </div>
                                </template>

                                <template x-if="! proofPreview">
                                    <button type="button" @click="$refs.proofInput.click()" class="block w-full py-4">
                                        <x-icon name="receipt" class="mx-auto h-7 w-7 text-violet-500" />
                                        <span class="mt-2 block text-sm font-semibold text-violet-900">Upload receipt screenshot</span>
                                        <span class="mt-1 block text-xs text-violet-700">Tap to choose from your gallery · JPG, PNG or WebP · up to 4 MB</span>
                                    </button>
                                </template>

                                <input x-ref="proofInput" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="pickProof($event)">
                            </div>

                            <p class="mt-1 text-xs text-rose-600" x-text="error('payment_proof')"></p>
                            <p class="mt-1 text-[11px] text-violet-700">
                                Open GCash → Pay → screenshot the receipt, then upload it here. Required to place a GCash order.
                            </p>
                        </div>
                    </div>

                    <div x-show="paymentMethod !== 'gcash'" x-cloak class="mt-4 rounded-xl border border-ink-200 bg-ink-50 p-4 text-sm text-ink-600">
                        Pay when you receive your order. We will call to confirm before the kitchen starts.
                    </div>
                </section>

                <button type="submit" @disabled(! $orderingOpen) :disabled="submitting"
                    class="btn btn-primary w-full py-3 text-base">
                    <span x-show="!submitting">Place order · <span x-text="money(totalCents)"></span></span>
                    <span x-show="submitting" x-cloak>Placing your order…</span>
                </button>

                <p class="text-center text-[11px] text-ink-500">
                    By placing the order you agree to be contacted about it. Prices are confirmed by the store.
                </p>
            </form>

            {{-- Summary --}}
            <aside class="lg:col-span-2">
                <div class="sticky top-24 rounded-2xl border border-ink-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h2 class="font-bold">Order summary</h2>
                        <a href="{{ route('storefront') }}" class="text-xs font-semibold text-brand-700 hover:text-brand-900">Edit</a>
                    </div>

                    <div class="mt-4 space-y-3">
                        <template x-for="item in items" :key="item.id">
                            <div class="flex items-start justify-between gap-3 text-sm">
                                <div class="min-w-0 break-words">
                                    <p class="font-medium"><span x-text="item.qty"></span>× <span x-text="item.name"></span></p>
                                    <p x-show="item.note" class="mt-0.5 text-[11px] text-brand-700" x-text="'“' + item.note + '”'"></p>
                                </div>
                                <span class="shrink-0 font-semibold" x-text="money(item.price_cents * item.qty)"></span>
                            </div>
                        </template>
                    </div>

                    <div class="mt-5 space-y-1.5 border-t border-ink-100 pt-4 text-sm">
                        <div class="flex justify-between text-ink-600">
                            <span>Subtotal</span><span x-text="money(subtotalCents)"></span>
                        </div>
                        <div class="flex justify-between text-ink-600">
                            <span>Delivery fee</span>
                            <span x-text="orderType === 'pickup' ? 'Pickup' : (deliveryFeeCents === 0 ? 'Free delivery' : money(deliveryFeeCents))"></span>
                        </div>
                        <p x-show="isDeliveryFree && orderType === 'delivery'" x-cloak class="text-[11px] text-emerald-700">
                            Free delivery: <span x-text="config.delivery_free_barangays_text || ''"></span>
                        </p>
                        <div class="flex justify-between border-t border-ink-100 pt-2 text-base font-black">
                            <span>Total</span><span x-text="money(totalCents)"></span>
                        </div>
                    </div>

                    <p x-show="belowMinimum" x-cloak class="mt-3 rounded-lg bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700"
                        x-text="'Minimum order is ' + money(config.minimum_order_cents) + '. Add a little more to continue.'"></p>

                    <div class="mt-4 rounded-xl bg-ink-50 p-3 text-xs text-ink-500">
                        <p class="font-semibold text-ink-700">Need help?</p>
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
