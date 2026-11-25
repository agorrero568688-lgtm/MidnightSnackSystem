<div x-data="posTerminal()" class="flex h-screen flex-col overflow-hidden bg-ink-950 text-ink-100">
    <script type="application/json" id="pos-bootstrap">{!! json_encode($bootstrap, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>

    {{-- Top bar --}}
    <header class="flex items-center justify-between gap-4 border-b border-ink-800 bg-ink-900/70 px-4 py-2.5">
        <div class="flex min-w-0 items-center gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-400 text-base font-black text-ink-950">
                D
            </div>
            <div class="min-w-0 leading-tight">
                <p class="truncate text-sm font-bold text-white">{{ \App\Models\Setting::get('store_name') }}</p>
                <p class="truncate text-[11px] text-ink-400">Cashier · {{ $user->name }}</p>
            </div>

            <span class="badge" :class="hasShift ? 'bg-emerald-500/15 text-emerald-300' : 'bg-rose-500/15 text-rose-300'">
                <span class="h-1.5 w-1.5 rounded-full" :class="hasShift ? 'bg-emerald-400' : 'bg-rose-400'"></span>
                <span x-text="hasShift ? 'Shift open' : 'No shift'"></span>
            </span>
            <span class="hidden text-xs text-ink-400 lg:block" x-text="shift ? shift.opened_at : ''"></span>
        </div>

        <div class="flex items-center gap-2">
            <span class="hidden font-mono text-sm text-ink-300 sm:block" x-text="now"></span>

            <button x-show="hasShift" x-cloak @click="showCloseShift = true" class="btn btn-sm border border-ink-700 text-ink-300 hover:bg-ink-800">
                <x-icon name="clock" class="h-4 w-4" /> Close shift
            </button>

            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button @click="open = !open" class="btn btn-sm border border-ink-700 text-ink-300 hover:bg-ink-800">
                    Parked
                    <span class="rounded-full bg-brand-400 px-1.5 text-[10px] font-bold text-ink-950" x-text="parked.length"></span>
                </button>
                <div x-cloak x-show="open" x-transition
                    class="absolute right-0 z-30 mt-2 w-80 overflow-hidden rounded-xl border border-ink-700 bg-ink-900 shadow-2xl">
                    <p class="border-b border-ink-800 px-3 py-2 text-xs font-semibold tracking-wide text-ink-400 uppercase">Parked sales</p>
                    <template x-if="parked.length === 0">
                        <p class="px-3 py-4 text-center text-xs text-ink-500">Nothing parked.</p>
                    </template>
                    <template x-for="(sale, index) in parked" :key="sale.id">
                        <div class="flex items-center justify-between gap-2 border-b border-ink-800 px-3 py-2 last:border-0">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium" x-text="sale.cart.length + ' item(s)'"></p>
                                <p class="text-[11px] text-ink-500" x-text="money(sale.cart.reduce((s, i) => s + i.price_cents * i.qty, 0))"></p>
                            </div>
                            <div class="flex gap-1">
                                <button @click="resumeSale(index); open = false" class="btn btn-sm btn-primary">Resume</button>
                                <button @click="discardParked(index)" class="btn btn-sm text-ink-500 hover:text-rose-400">
                                    <x-icon name="trash" class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            @if ($isAdmin)
                <a href="{{ route('dashboard') }}" class="btn btn-sm border border-ink-700 text-ink-300 hover:bg-ink-800">
                    Back office
                </a>
            @endif

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-sm text-ink-400 hover:bg-ink-800 hover:text-white">
                    <x-icon name="logout" class="h-4 w-4" /> Exit
                </button>
            </form>
        </div>
    </header>

    <div class="flex min-h-0 flex-1 flex-col lg:flex-row">
        {{-- Catalogue --}}
        <section class="flex min-h-0 flex-1 flex-col">
            <div class="space-y-3 border-b border-ink-800 bg-ink-900/40 p-4">
                <div class="flex gap-3">
                    <div class="relative flex-1">
                        <x-icon name="cart" class="pointer-events-none absolute top-1/2 left-3.5 h-5 w-5 -translate-y-1/2 text-brand-400" />
                        <input x-ref="scan" x-model="scan" @keydown.enter.prevent="handleScan()"
                            type="text" inputmode="numeric" autocomplete="off"
                            placeholder="Scan barcode or type SKU, then press Enter"
                            class="w-full rounded-xl border border-ink-700 bg-ink-950 py-3 pr-4 pl-11 text-base font-medium text-white placeholder:text-ink-500 focus:border-violet-400 focus:ring-2 focus:ring-violet-400/30 focus:outline-none">
                    </div>
                    <div class="relative">
                        <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-ink-500" />
                        <input x-model="search" type="search" placeholder="Search name"
                            class="h-full w-44 rounded-xl border border-ink-700 bg-ink-950 py-3 pr-3 pl-9 text-sm text-white placeholder:text-ink-500 focus:border-violet-400 focus:outline-none xl:w-64">
                    </div>
                </div>

                <div class="flex gap-2 overflow-x-auto pb-1">
                    <button @click="activeCategory = 'all'"
                        class="rounded-full px-3.5 py-1.5 text-xs font-semibold whitespace-nowrap transition"
                        :class="activeCategory === 'all' ? 'bg-brand-400 text-ink-950' : 'bg-ink-800 text-ink-300 hover:bg-ink-700'">
                        All items
                    </button>
                    <template x-for="category in categories" :key="category.id">
                        <button @click="activeCategory = category.id"
                            class="rounded-full px-3.5 py-1.5 text-xs font-semibold whitespace-nowrap transition"
                            :class="String(activeCategory) === String(category.id) ? 'bg-brand-400 text-ink-950' : 'bg-ink-800 text-ink-300 hover:bg-ink-700'"
                            x-text="category.name"></button>
                    </template>
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto p-4">
                <template x-if="filteredProducts.length === 0">
                    <div class="flex h-full items-center justify-center text-sm text-ink-500">
                        No products match your search.
                    </div>
                </template>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
                    <template x-for="product in filteredProducts" :key="product.id">
                        <button @click="addProduct(product)" :disabled="isSoldOut(product)"
                            class="group relative flex flex-col rounded-2xl border border-ink-800 bg-ink-900 p-3 text-left transition hover:border-brand-400/60 hover:bg-ink-800 disabled:cursor-not-allowed disabled:opacity-50">
                            <div class="mb-2 flex items-start justify-between gap-2">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-brand-300 to-brand-500 text-base font-black text-ink-950">
                                    <span x-text="product.name.charAt(0).toUpperCase()"></span>
                                </div>
                                <span x-show="!product.is_available" class="badge bg-rose-500/15 text-rose-300">Sold out</span>
                            </div>
                            <p class="line-clamp-2 min-h-[2.5rem] text-sm font-semibold text-white" x-text="product.name"></p>
                            <div class="mt-2 flex items-end justify-between">
                                <span class="text-base font-bold text-brand-300" x-text="money(product.price_cents)"></span>
                                <span class="text-[10px] tracking-wide text-ink-500 uppercase" x-text="product.sku"></span>
                            </div>
                        </button>
                    </template>
                </div>
            </div>
        </section>

        {{-- Cart --}}
        <aside class="flex min-h-0 w-full flex-col border-t border-ink-800 bg-ink-900 lg:w-[380px] lg:border-t-0 lg:border-l xl:w-[420px]">
            <div class="flex items-center justify-between border-b border-ink-800 px-4 py-3">
                <h2 class="text-sm font-bold tracking-wide text-white uppercase">Current sale</h2>
                <span class="badge bg-ink-800 text-ink-300"><span x-text="cartCount"></span> item(s)</span>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto">
                <template x-if="cart.length === 0">
                    <div class="flex h-full flex-col items-center justify-center gap-2 px-6 py-12 text-center">
                        <x-icon name="cart" class="h-10 w-10 text-ink-700" />
                        <p class="text-sm text-ink-500">Scan a barcode or tap a product to start.</p>
                        <p class="text-[11px] text-ink-600">F2 scan · F4 amount · Esc clear</p>
                    </div>
                </template>

                <template x-for="item in cart" :key="item.id">
                    <div class="border-b border-ink-800/70 px-4 py-3">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-white" x-text="item.name"></p>
                                <p class="text-[11px] text-ink-500" x-text="money(item.price_cents) + ' each'"></p>
                            </div>
                            <button @click="removeItem(item)" class="rounded-lg p-1 text-ink-500 transition hover:bg-ink-800 hover:text-rose-400">
                                <x-icon name="x" class="h-4 w-4" />
                            </button>
                        </div>
                        <div class="mt-2 flex items-center justify-between">
                            <div class="flex items-center gap-1 rounded-lg border border-ink-700 bg-ink-950">
                                <button @click="decrement(item)" class="px-2.5 py-1.5 text-ink-300 hover:text-white">
                                    <x-icon name="minus" class="h-4 w-4" />
                                </button>
                                <span class="w-8 text-center text-sm font-bold" x-text="item.qty"></span>
                                <button @click="increment(item)" class="px-2.5 py-1.5 text-ink-300 hover:text-white">
                                    <x-icon name="plus" class="h-4 w-4" />
                                </button>
                            </div>
                            <span class="text-sm font-bold text-brand-300" x-text="money(item.price_cents * item.qty)"></span>
                        </div>
                    </div>
                </template>
            </div>

            <div class="space-y-3 border-t border-ink-800 bg-ink-950/60 px-4 py-3">
                <div class="space-y-1.5 text-sm">
                    <div class="flex justify-between text-ink-400">
                        <span>Subtotal</span><span x-text="money(subtotalCents)"></span>
                    </div>
                    <div x-show="discountType !== 'none'" class="flex justify-between text-emerald-400">
                        <span x-text="discountType === 'senior' ? 'Senior discount (20%)' : 'PWD discount (20%)'"></span>
                        <span x-text="'-' + money(discountCents)"></span>
                    </div>
                    <div class="flex items-baseline justify-between border-t border-ink-800 pt-2">
                        <span class="text-xs font-semibold tracking-wider text-ink-400 uppercase">Total</span>
                        <span class="text-3xl font-black text-brand-300" x-text="money(totalCents)"></span>
                    </div>
                </div>

                {{-- Discount --}}
                <div>
                    <button @click="showDiscount = !showDiscount" class="flex w-full items-center justify-between rounded-lg border border-ink-800 px-3 py-2 text-xs font-semibold text-ink-300 hover:bg-ink-900">
                        <span class="flex items-center gap-2"><x-icon name="tag" class="h-4 w-4" /> Discount</span>
                        <span class="text-brand-300" x-text="discountType === 'none' ? 'None' : (discountType === 'senior' ? 'Senior 20%' : 'PWD 20%')"></span>
                    </button>

                    <div x-cloak x-show="showDiscount" x-transition class="mt-2 space-y-2 rounded-xl border border-ink-800 bg-ink-900 p-3">
                        <select x-model="discountType" class="w-full rounded-lg border border-ink-700 bg-ink-950 px-2.5 py-2 text-sm text-white focus:border-violet-400 focus:outline-none">
                            <option value="none">No discount</option>
                            <option value="senior">Senior citizen (20%)</option>
                            <option value="pwd">PWD (20%)</option>
                        </select>
                        <template x-if="discountType !== 'none'">
                            <div class="space-y-2">
                                <input x-model="customerName" type="text" placeholder="Customer name"
                                    class="w-full rounded-lg border border-ink-700 bg-ink-950 px-2.5 py-2 text-sm text-white placeholder:text-ink-500 focus:border-violet-400 focus:outline-none">
                                <input x-model="discountId" type="text" placeholder="OSCA / PWD ID number"
                                    class="w-full rounded-lg border border-ink-700 bg-ink-950 px-2.5 py-2 text-sm text-white placeholder:text-ink-500 focus:border-violet-400 focus:outline-none">
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Payment --}}
                <div class="grid grid-cols-4 gap-1.5">
                    <template x-for="method in [['cash','Cash'],['gcash','GCash'],['maya','Maya'],['bank','Bank']]" :key="method[0]">
                        <button @click="paymentMethod = method[0]"
                            class="rounded-lg px-2 py-2 text-xs font-semibold transition"
                            :class="paymentMethod === method[0] ? 'bg-brand-400 text-ink-950' : 'bg-ink-900 text-ink-400 hover:bg-ink-800'"
                            x-text="method[1]"></button>
                    </template>
                </div>

                <div x-show="paymentMethod === 'cash'" class="space-y-2">
                    <div class="flex gap-2">
                        <input x-ref="tendered" x-model="tendered" type="text" inputmode="decimal" placeholder="Cash received"
                            class="w-full rounded-lg border border-ink-700 bg-ink-950 px-3 py-2.5 text-lg font-bold text-white placeholder:font-normal placeholder:text-ink-500 focus:border-violet-400 focus:outline-none">
                        <button @click="tenderExact()" class="btn btn-sm border border-ink-700 text-ink-300">Exact</button>
                    </div>
                    <div class="grid grid-cols-6 gap-1">
                        <template x-for="amount in [20, 50, 100, 200, 500, 1000]" :key="amount">
                            <button @click="setTendered(amount * 100)" class="rounded-lg bg-ink-900 py-1.5 text-[11px] font-semibold text-ink-300 hover:bg-ink-800" x-text="amount"></button>
                        </template>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-ink-400">Change</span>
                        <span class="font-bold" :class="changeCents > 0 ? 'text-emerald-400' : 'text-ink-300'" x-text="money(changeCents)"></span>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button @click="parkSale()" class="btn flex-1 border border-ink-700 text-ink-300 hover:bg-ink-800">Park</button>
                    <button @click="clearCart()" class="btn flex-1 border border-ink-700 text-ink-300 hover:bg-ink-800">Clear</button>
                </div>

                <button @click="pay()" :disabled="!canPay"
                    class="btn w-full bg-brand-400 py-4 text-lg font-black text-ink-950 shadow-lg shadow-brand-500/25 hover:bg-brand-300 disabled:opacity-40">
                    <span x-show="!busy">Charge <span x-text="money(totalCents)"></span></span>
                    <span x-show="busy" x-cloak>Processing…</span>
                </button>
            </div>
        </aside>
    </div>

    {{-- Error toast --}}
    <div x-cloak x-show="error" x-transition
        class="fixed top-16 left-1/2 z-50 -translate-x-1/2 rounded-xl border border-rose-500/40 bg-rose-500/15 px-5 py-3 text-sm font-semibold text-rose-200 shadow-2xl backdrop-blur">
        <span class="flex items-center gap-2"><x-icon name="alert" class="h-4 w-4" /> <span x-text="error"></span></span>
    </div>

    {{-- Shift open overlay --}}
    <div x-cloak x-show="ready && !hasShift && config.require_shift"
        class="fixed inset-0 z-40 flex items-center justify-center bg-ink-950/90 p-4 backdrop-blur-sm">
        <div class="w-full max-w-sm rounded-2xl border border-ink-800 bg-ink-900 p-6 shadow-2xl">
            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-brand-400/15 text-brand-300">
                <x-icon name="lock" class="h-6 w-6" />
            </div>
            <h3 class="text-lg font-bold text-white">Open your shift</h3>
            <p class="mt-1 mb-4 text-sm text-ink-400">Enter the cash in the drawer to start selling. Sales will be tied to this shift.</p>

            <div class="mb-4 rounded-xl border border-ink-800 bg-ink-950 px-3 py-2 text-sm text-ink-400">
                Cashier · <span class="font-semibold text-ink-200">{{ $user->name }}</span>
            </div>

            <label class="label text-ink-400!">Opening cash</label>
            <input x-model="openingCash" type="text" inputmode="decimal" placeholder="0.00"
                class="mb-2 w-full rounded-xl border border-ink-700 bg-ink-950 px-3 py-3 text-xl font-bold text-white focus:border-violet-400 focus:outline-none">

            <p x-show="shiftError" x-cloak class="mb-2 text-sm text-rose-400" x-text="shiftError"></p>

            <button @click="openShift()" :disabled="shiftBusy" class="btn btn-primary w-full py-3">
                <span x-show="!shiftBusy">Start shift</span>
                <span x-show="shiftBusy" x-cloak>Opening…</span>
            </button>
        </div>
    </div>

    {{-- Close shift modal --}}
    <div x-cloak x-show="showCloseShift" class="fixed inset-0 z-40 flex items-center justify-center bg-ink-950/90 p-4 backdrop-blur-sm">
        <div class="w-full max-w-sm rounded-2xl border border-ink-800 bg-ink-900 p-6 shadow-2xl">
            <h3 class="text-lg font-bold text-white">Close shift</h3>
            <p class="mt-1 mb-4 text-sm text-ink-400">Count the cash in the drawer and enter the total.</p>

            <div class="mb-4 space-y-1 rounded-xl border border-ink-800 bg-ink-950 p-3 text-sm">
                <div class="flex justify-between text-ink-400"><span>Opening cash</span><span x-text="money(shift?.opening_cents || 0)"></span></div>
                <div class="flex justify-between text-ink-400"><span>Expected in drawer</span><span class="font-semibold text-white" x-text="money(shift?.expected_cents || 0)"></span></div>
                <div class="flex justify-between border-t border-ink-800 pt-1 text-ink-400">
                    <span>Variance</span>
                    <span class="font-semibold"
                        :class="(toCents(countedCash) - (shift?.expected_cents || 0)) === 0 ? 'text-ink-300' : ((toCents(countedCash) - (shift?.expected_cents || 0)) < 0 ? 'text-rose-400' : 'text-emerald-400')"
                        x-text="money(toCents(countedCash) - (shift?.expected_cents || 0))"></span>
                </div>
            </div>

            <label class="label text-ink-400!">Counted cash</label>
            <input x-model="countedCash" type="text" inputmode="decimal" placeholder="0.00"
                class="mb-2 w-full rounded-xl border border-ink-700 bg-ink-950 px-3 py-3 text-xl font-bold text-white focus:border-violet-400 focus:outline-none">

            <p x-show="shiftError" x-cloak class="mb-2 text-sm text-rose-400" x-text="shiftError"></p>

            <div class="mt-3 flex gap-2">
                <button @click="showCloseShift = false" class="btn flex-1 border border-ink-700 text-ink-300">Cancel</button>
                <button @click="closeShift()" :disabled="shiftBusy" class="btn flex-1 btn-primary">
                    <span x-show="!shiftBusy">Close shift</span>
                    <span x-show="shiftBusy" x-cloak>Closing…</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Success --}}
    <div x-cloak x-show="success" class="fixed inset-0 z-50 flex items-center justify-center bg-ink-950/90 p-4 backdrop-blur-sm">
        <div class="w-full max-w-sm rounded-2xl border border-emerald-500/30 bg-ink-900 p-6 text-center shadow-2xl">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-400">
                <x-icon name="check" class="h-8 w-8" />
            </div>
            <h3 class="text-lg font-bold text-white">Sale complete</h3>
            <p class="mt-1 font-mono text-sm text-brand-300" x-text="success?.reference_no"></p>

            <div class="mt-4 space-y-1 rounded-xl border border-ink-800 bg-ink-950 p-4 text-sm">
                <div class="flex justify-between text-ink-400"><span>Total</span><span class="text-lg font-black text-white" x-text="money(success?.total_cents || 0)"></span></div>
                <div x-show="success?.payment_method === 'cash'" class="flex justify-between text-ink-400"><span>Cash</span><span x-text="money(success?.tendered_cents || 0)"></span></div>
                <div x-show="success?.payment_method === 'cash'" class="flex justify-between text-ink-400"><span>Change</span><span class="font-bold text-emerald-400" x-text="money(success?.change_cents || 0)"></span></div>
            </div>

            <div class="mt-5 flex gap-2">
                <button @click="printReceipt()" class="btn flex-1 border border-ink-700 text-ink-200">
                    <x-icon name="print" class="h-4 w-4" /> Receipt
                </button>
                <button @click="newSale()" class="btn flex-1 btn-primary">New sale</button>
            </div>
        </div>
    </div>
</div>
