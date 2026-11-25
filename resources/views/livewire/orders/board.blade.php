<div x-data="orderBoard()" @order-alert.window="onOrderAlert($event.detail)" wire:poll.15s="pollOrders"
    wire:keydown.escape.window="closeOrder" class="space-y-5">
    {{-- Toolbar --}}
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div class="flex flex-wrap gap-1.5 rounded-xl bg-ink-100 p-1">
            @foreach (['active' => 'Live board', 'today' => 'Today', 'all' => 'All orders', 'completed' => 'Completed'] as $key => $label)
                <button type="button" wire:click="setFilter('{{ $key }}')"
                    class="rounded-lg px-3 py-1.5 text-xs font-bold transition {{ $filter === $key ? 'bg-brand-400 text-ink-950 shadow-sm shadow-brand-500/25' : 'text-ink-600 hover:bg-white' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <div class="relative w-full sm:w-64">
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-ink-400" />
                <input wire:model.live.debounce.400ms="search" type="search" placeholder="Reference, name or phone" class="input py-2 pl-9 text-xs">
            </div>

            <button type="button" @click="toggleSound()"
                class="btn btn-secondary btn-sm" :class="sound ? 'text-emerald-700' : 'text-ink-400'">
                <x-icon name="bell" class="h-3.5 w-3.5" />
                <span x-text="sound ? 'Sound on' : 'Sound off'"></span>
            </button>

            <button type="button" @click="enableNotifications()" x-show="!notifications" class="btn btn-secondary btn-sm">
                Enable browser alerts
            </button>
            <span x-show="notifications" x-cloak class="badge badge-green">Browser alerts on</span>

            <button type="button" @click="testAlert()" class="btn btn-ghost btn-sm">Test alert</button>
            <a href="{{ route('storefront') }}" target="_blank" class="btn btn-ghost btn-sm">Storefront ↗</a>
            @if ($selected)
                <span class="mx-3 h-6 border-r border-ink-200"></span>
                <span class="text-xs text-ink-400">
                    Feedback: <a href="{{ route('feedback.show', $selected->public_token) }}" class="font-semibold text-brand-700 hover:text-brand-500">view</a>
                </span>
            @endif
        </div>
    </div>

    @if ($flashError)
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $flashError }}</div>
    @endif
    @if ($notice)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ $notice }}</div>
    @endif

    {{-- Stats --}}
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card card-body">
            <p class="text-xs font-semibold tracking-wider text-ink-500 uppercase">New orders</p>
            <p class="mt-1 text-3xl font-black {{ $pendingCount > 0 ? 'text-brand-700' : 'text-ink-900' }}">{{ $pendingCount }}</p>
            <p class="mt-1 text-xs text-ink-500">Waiting for a human to confirm</p>
        </div>
        <div class="card card-body">
            <p class="text-xs font-semibold tracking-wider text-ink-500 uppercase">To verify</p>
            <p class="mt-1 text-3xl font-black {{ $awaitingPayment > 0 ? 'text-violet-600' : 'text-ink-900' }}">{{ $awaitingPayment }}</p>
            <p class="mt-1 text-xs text-ink-500">GCash receipts waiting for a check</p>
        </div>
        <div class="card card-body">
            <p class="text-xs font-semibold tracking-wider text-ink-500 uppercase">In progress</p>
            <p class="mt-1 text-3xl font-black text-ink-900">{{ $activeCount }}</p>
            <p class="mt-1 text-xs text-ink-500">Everything not yet closed</p>
        </div>
        <div class="card card-body">
            <p class="text-xs font-semibold tracking-wider text-ink-500 uppercase">Completed today</p>
            <p class="mt-1 text-3xl font-black text-ink-900">{{ $todayCompleted }}</p>
            <p class="mt-1 text-xs text-ink-500">{{ $todayRevenue }} in online sales</p>
        </div>
    </div>

    @if ($filter === 'active')
        {{-- Kanban --}}
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            @foreach ($columns as $column)
                <div class="flex min-h-44 flex-col rounded-2xl bg-ink-100/80 p-2.5 ring-1 ring-ink-200/60">
                    <div class="flex items-center justify-between gap-2 px-1.5 py-1.5">
                        <span class="text-[11px] font-black tracking-wider text-ink-600 uppercase">{{ $column['status']->boardLabel() }}</span>
                        <span class="badge badge-{{ $column['status']->color() }}">{{ $column['orders']->count() }}</span>
                    </div>

                    <div class="space-y-2">
                        @forelse ($column['orders'] as $order)
                            <button type="button" wire:key="order-{{ $order->id }}" wire:click="openOrder({{ $order->id }})"
                                class="w-full rounded-xl border border-ink-200 bg-white p-3 text-left shadow-sm transition hover:border-brand-300 hover:shadow">
                                <div class="flex items-start justify-between gap-2">
                                    <span class="font-mono text-[11px] font-bold text-ink-500">{{ $order->reference_no }}</span>
                                    <span class="badge badge-{{ $order->payment_status->color() }}">{{ $order->payment_status->shortLabel() }}</span>
                                </div>

                                <p class="mt-1.5 truncate text-sm font-bold">{{ $order->customer_name }}</p>

                                <div class="mt-1 flex flex-wrap items-center gap-1.5 text-[11px] text-ink-500">
                                    <span class="inline-flex items-center gap-1">
                                        <x-icon :name="$order->order_type->icon()" class="h-3 w-3" />
                                        {{ $order->order_type->label() }}
                                    </span>
                                    <span>·</span>
                                    <span>{{ $order->items_count }} item{{ $order->items_count === 1 ? '' : 's' }}</span>
                                </div>

                                <div class="mt-2 flex items-end justify-between gap-2 border-t border-ink-100 pt-2">
                                    <span class="text-sm font-black">{{ \App\Support\Money::format($order->total) }}</span>
                                    <span class="text-[10px] text-ink-400">{{ $order->placedAtLabel() }}</span>
                                </div>

                                @if ($order->notes)
                                    <p class="mt-1.5 line-clamp-2 rounded-lg bg-brand-50 px-2 py-1 text-[10px] text-brand-800">“{{ $order->notes }}”</p>
                                @endif

                                <div class="mt-2 flex items-center gap-1.5 text-[10px] font-semibold text-ink-400">
                                    <span class="inline-flex items-center gap-1">
                                        <x-icon name="banknote" class="h-3 w-3" /> {{ $order->payment_method->shortLabel() }}
                                    </span>
                                    <span>·</span>
                                    <span>Open →</span>
                                </div>
                            </button>
                        @empty
                            <p class="px-2 py-6 text-center text-[11px] text-ink-400">Nothing here.</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    @else
        {{-- List --}}
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Placed</th>
                            <th>Customer</th>
                            <th>Type</th>
                            <th class="text-right">Items</th>
                            <th>Payment</th>
                            <th class="text-right">Total</th>
                            <th>Status</th>
                            <th class="text-right">Open</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr wire:key="list-order-{{ $order->id }}">
                                <td class="font-mono text-xs font-semibold">{{ $order->reference_no }}</td>
                                <td class="text-xs whitespace-nowrap text-ink-500">{{ $order->placedAtLabel() }}</td>
                                <td>
                                    <p class="font-medium">{{ $order->customer_name }}</p>
                                    <p class="text-[11px] text-ink-500">{{ $order->customer_phone }}</p>
                                </td>
                                <td><span class="badge badge-slate">{{ $order->order_type->label() }}</span></td>
                                <td class="text-right">{{ $order->items_count }}</td>
                                <td>
                                    <span class="badge badge-{{ $order->payment_status->color() }}">{{ $order->payment_status->label() }}</span>
                                    <p class="mt-0.5 text-[10px] text-ink-400">{{ $order->payment_method->shortLabel() }}</p>
                                </td>
                                <td class="text-right font-semibold">{{ \App\Support\Money::format($order->total) }}</td>
                                <td><span class="badge badge-{{ $order->status->color() }}">{{ $order->status->label() }}</span></td>
                                <td class="text-right">
                                    <button type="button" wire:click="openOrder({{ $order->id }})" class="btn btn-ghost btn-sm">Open</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="empty-state">No orders match this view.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Detail --}}
    @if ($selected)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-ink-950/60 p-3 backdrop-blur-sm sm:p-6">
            <div class="my-4 w-full max-w-3xl rounded-2xl bg-white shadow-2xl">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-ink-100 px-5 py-4">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-mono text-lg font-black">{{ $selected->reference_no }}</h2>
                            <span class="badge badge-{{ $selected->status->color() }}">{{ $selected->status->label() }}</span>
                            <span class="badge badge-{{ $selected->payment_status->color() }}">{{ $selected->payment_status->label() }}</span>
                        </div>
                        <p class="mt-1 text-xs text-ink-500">
                            {{ $selected->order_type->label() }} · {{ $selected->placedAtLabel() }} · {{ $selected->payment_method->label() }}
                        </p>
                    </div>
                    <button type="button" wire:click="closeOrder" class="rounded-lg p-2 text-ink-400 hover:bg-ink-100 hover:text-ink-700">
                        <x-icon name="x" class="h-5 w-5" />
                    </button>
                </div>

                <div class="max-h-[75vh] space-y-5 overflow-y-auto px-5 py-5">
                    @if ($flashError)
                        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $flashError }}</div>
                    @endif

                    {{-- Customer --}}
                    <div class="grid gap-3 rounded-xl border border-ink-200 bg-ink-50 p-4 text-sm sm:grid-cols-2">
                        <div>
                            <p class="text-[10px] font-bold tracking-wider text-ink-400 uppercase">Customer</p>
                            <p class="mt-1 font-semibold">{{ $selected->customer_name }}</p>
                            <p class="text-ink-600">{{ $selected->customer_phone }}</p>
                            @if ($selected->customer_email)
                                <p class="text-xs text-ink-500">{{ $selected->customer_email }}</p>
                            @endif
                        </div>
                        <div>
                            <p class="text-[10px] font-bold tracking-wider text-ink-400 uppercase">{{ $selected->isDelivery() ? 'Deliver to' : 'Pickup' }}</p>
                            <p class="mt-1 leading-relaxed text-ink-700">
                                {{ $selected->isDelivery() ? $selected->delivery_address : 'Customer collects at the counter.' }}
                            </p>
                            @if ($selected->notes)
                                <p class="mt-2 rounded-lg bg-brand-100 px-2.5 py-1.5 text-xs text-brand-900">“{{ $selected->notes }}”</p>
                            @endif
                        </div>
                    </div>

                    {{-- Items --}}
                    <div class="overflow-hidden rounded-xl border border-ink-200">
                        <table class="data-table">
                            <thead>
                                <tr><th>Item</th><th class="text-right">Qty</th><th class="text-right">Price</th><th class="text-right">Total</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($selected->items as $item)
                                    <tr>
                                        <td>
                                            <p class="font-medium">{{ $item->name_snapshot }}</p>
                                            @if ($item->note)
                                                <p class="text-[11px] text-brand-700">“{{ $item->note }}”</p>
                                            @endif
                                        </td>
                                        <td class="text-right">{{ $item->qty }}</td>
                                        <td class="text-right text-ink-500">{{ \App\Support\Money::format($item->unit_price) }}</td>
                                        <td class="text-right font-semibold">{{ \App\Support\Money::format($item->line_total) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="space-y-1 border-t border-ink-100 bg-ink-50 px-4 py-3 text-sm">
                            <div class="flex justify-between text-ink-600"><span>Subtotal</span><span>{{ \App\Support\Money::format($selected->subtotal) }}</span></div>
                            <div class="flex justify-between text-ink-600"><span>Delivery fee</span><span>{{ $selected->isPickup() ? 'Pickup' : ($selected->delivery_fee > 0 ? \App\Support\Money::format($selected->delivery_fee) : 'Free') }}</span></div>
                            @if ($selected->discount_amount > 0)
                                <div class="flex justify-between text-violet-700"><span>{{ $selected->discount_type->label() }}</span><span>-{{ \App\Support\Money::format($selected->discount_amount) }}</span></div>
                            @endif
                            <div class="flex justify-between border-t border-ink-200 pt-2 text-base font-black"><span>Total</span><span>{{ \App\Support\Money::format($selected->total) }}</span></div>
                        </div>
                    </div>

                    {{-- Workflow --}}
                    <div class="rounded-xl border border-ink-200 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h3 class="text-sm font-bold">Next step</h3>
                            @if ($selected->confirmedBy)
                                <span class="text-[11px] text-ink-500">Confirmed by {{ $selected->confirmedBy->name }}</span>
                            @endif
                            @if ($selected->ready_at)
                                <span class="text-[11px] text-ink-500">Ready since {{ $selected->ready_at->format('M d, h:i A') }}</span>
                            @endif
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2">
                            @if ($selected->status === \App\Enums\OrderStatus::Pending)
                                <button type="button" wire:click="confirmOrder" wire:loading.attr="disabled" class="btn btn-primary btn-sm">Confirm order</button>
                            @elseif ($selected->status === \App\Enums\OrderStatus::Confirmed)
                                <button type="button" wire:click="startPreparing" wire:loading.attr="disabled" class="btn btn-primary btn-sm">Start preparing</button>
                            @elseif ($selected->status === \App\Enums\OrderStatus::Preparing)
                                <button type="button" wire:click="markReady" wire:loading.attr="disabled" class="btn btn-primary btn-sm">Mark ready</button>
                            @elseif ($selected->status === \App\Enums\OrderStatus::Ready && $selected->isDelivery())
                                <button type="button" wire:click="dispatchDelivery" wire:loading.attr="disabled" class="btn btn-primary btn-sm">Hand to rider</button>
                            @endif

                            @if (in_array($selected->status, [\App\Enums\OrderStatus::Ready, \App\Enums\OrderStatus::OutForDelivery], true))
                                <button type="button" wire:click="completeOrder" wire:loading.attr="disabled" @disabled(! $selected->isPaid())
                                    title="{{ $selected->isPaid() ? 'Close the order and record the sale' : 'Record the payment first' }}"
                                    class="btn btn-dark btn-sm">Complete &amp; record sale</button>
                            @endif

                            @if ($selected->sale_id)
                                <a href="{{ route('sales.receipt', $selected->sale_id) }}" target="_blank" class="btn btn-secondary btn-sm">Print receipt</a>
                            @endif

                            @if ($selected->status->isActive())
                                <button type="button" wire:click="$set('showCancel', true)" class="btn btn-sm text-rose-600 hover:bg-rose-50">Cancel order</button>
                            @endif
                        </div>

                        @if (in_array($selected->status, [\App\Enums\OrderStatus::Ready, \App\Enums\OrderStatus::OutForDelivery], true) && ! $selected->isPaid())
                            <p class="mt-2 text-xs font-semibold text-brand-700">Record the payment below before closing the order.</p>
                        @endif

                        @if ($showCancel)
                            <div class="mt-3 rounded-xl border border-rose-200 bg-rose-50 p-3">
                                <label class="label text-rose-800!">Why is it being cancelled?</label>
                                <textarea wire:model="cancelReason" rows="2" class="input" placeholder="e.g. Customer called to cancel"></textarea>
                                @error('cancelReason') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                <div class="mt-2 flex justify-end gap-2">
                                    <button type="button" wire:click="$set('showCancel', false)" class="btn btn-secondary btn-sm">Keep order</button>
                                    <button type="button" wire:click="cancelOrder" wire:loading.attr="disabled" class="btn btn-danger btn-sm">Cancel order</button>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Payment --}}
                    <div class="rounded-xl border border-ink-200 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h3 class="text-sm font-bold">Payment</h3>
                            <span class="badge badge-{{ $selected->payment_status->color() }}">{{ $selected->payment_status->label() }}</span>
                        </div>

                        <div class="mt-2 grid gap-1 text-xs text-ink-600 sm:grid-cols-2">
                            <p><span class="text-ink-400">Method:</span> {{ $selected->payment_method->label() }}</p>
                            <p><span class="text-ink-400">Amount:</span> {{ \App\Support\Money::format($selected->total) }}</p>
                            @if ($selected->payment_reference)
                                <p><span class="text-ink-400">GCash ref:</span> <span class="font-mono font-semibold">{{ $selected->payment_reference }}</span></p>
                            @endif
                            @if ($selected->paid_at)
                                <p><span class="text-ink-400">Paid:</span> {{ $selected->paid_at->format('M d, h:i A') }}</p>
                            @endif
                            @if ($selected->paymentVerifiedBy)
                                <p><span class="text-ink-400">Verified by:</span> {{ $selected->paymentVerifiedBy->name }}</p>
                            @endif
                        </div>

                        @if ($selected->payment_proof_path)
                            <a href="{{ route('orders.proof', $selected) }}" target="_blank" class="group mt-3 inline-block">
                                <img src="{{ route('orders.proof', $selected) }}" alt="GCash receipt"
                                    class="h-28 w-28 rounded-lg object-cover ring-1 ring-ink-200 transition group-hover:ring-brand-400">
                                <span class="mt-1 block text-[11px] font-semibold text-violet-700">Open full receipt</span>
                            </a>
                        @elseif ($selected->payment_method->isGcash() && ! $selected->isPaid())
                            <p class="mt-3 rounded-lg bg-brand-50 px-3 py-2 text-xs font-semibold text-brand-800">No receipt screenshot uploaded yet.</p>
                        @endif

                        <div class="mt-3 flex flex-wrap gap-2">
                            @if (! $selected->isPaid() && $selected->status !== \App\Enums\OrderStatus::Cancelled && $selected->payment_status !== \App\Enums\PaymentStatus::Refunded)
                                <button type="button" wire:click="markPaid" wire:loading.attr="disabled" class="btn btn-primary btn-sm">
                                    {{ $selected->payment_method->isGcash() ? 'Mark GCash paid' : 'Mark cash received' }}
                                </button>
                            @endif

                            @if ($selected->status === \App\Enums\OrderStatus::Cancelled && $selected->isPaid())
                                <button type="button" wire:click="markRefunded" wire:loading.attr="disabled" class="btn btn-secondary btn-sm">Record refund</button>
                            @endif
                        </div>

                        @if ($selected->payment_method->isGcash() && ! $selected->isPaid() && $selected->status !== \App\Enums\OrderStatus::Cancelled)
                            <p class="mt-2 text-xs text-ink-500">Compare the receipt with your GCash transactions first. Marking it paid locks the total.</p>
                        @endif
                    </div>

                    {{-- Discount --}}
                    <div class="rounded-xl border border-ink-200 p-4">
                        <h3 class="text-sm font-bold">Senior / PWD discount</h3>
                        <p class="mt-1 text-xs text-ink-500">Only apply after checking the customer's OSCA/PWD ID. The 20% comes off the item subtotal, not the delivery fee.</p>

                        @if ($selected->isPaid())
                            <p class="mt-2 rounded-lg bg-brand-50 px-3 py-2 text-xs text-brand-800">The payment is recorded. Refund and re-open the order if the total must change.</p>
                        @else
                            <div class="mt-3 grid gap-3 sm:grid-cols-3">
                                <div>
                                    <label class="label">Discount</label>
                                    <select wire:model="discountType" class="input input-sm">
                                        @foreach ($discountOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="label">Verified name</label>
                                    <input wire:model="discountName" type="text" maxlength="100" class="input input-sm">
                                </div>
                                <div>
                                    <label class="label">OSCA / PWD ID</label>
                                    <input wire:model="discountIdNumber" type="text" maxlength="50" class="input input-sm">
                                </div>
                            </div>
                            <div class="mt-2 flex justify-end">
                                <button type="button" wire:click="applyDiscount" wire:loading.attr="disabled" class="btn btn-secondary btn-sm">Apply discount</button>
                            </div>
                        @endif
                    </div>

                    {{-- Staff notes --}}
                    <div class="rounded-xl border border-ink-200 p-4">
                        <h3 class="text-sm font-bold">Staff notes</h3>
                        <textarea wire:model="staffNotes" rows="2" maxlength="1000" class="input mt-2" placeholder="Called the customer, rider name, refund reference…"></textarea>
                        <div class="mt-2 flex justify-end">
                            <button type="button" wire:click="saveNotes" wire:loading.attr="disabled" class="btn btn-secondary btn-sm">Save note</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
