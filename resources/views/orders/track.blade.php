@php
    $steps = [
        ['key' => 'pending', 'label' => 'Order received', 'hint' => $order->placed_at],
        ['key' => 'confirmed', 'label' => 'Confirmed', 'hint' => $order->confirmed_at],
        ['key' => 'preparing', 'label' => 'In the kitchen', 'hint' => null],
        ['key' => 'ready', 'label' => 'Ready', 'hint' => $order->ready_at],
    ];

    if ($order->isDelivery()) {
        $steps[] = ['key' => 'out_for_delivery', 'label' => 'On the way', 'hint' => null];
    }

    $steps[] = ['key' => 'completed', 'label' => 'Completed', 'hint' => $order->completed_at];

    $flow = array_column($steps, 'key');
    $current = $order->status === \App\Enums\OrderStatus::Cancelled
        ? -1
        : array_search($order->status->value, $flow, true);
    $current = $current === false ? 0 : $current;
@endphp

<x-layouts.storefront :show-cart="false" title="Track {{ $order->reference_no }}">
    <main class="mx-auto max-w-4xl px-4 py-8 sm:px-6">
        @if (session('status'))
            <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <a href="{{ route('storefront') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-900">
                    <x-icon name="arrow-left" class="h-3.5 w-3.5" /> Back to the menu
                </a>
                <h1 class="mt-2 text-3xl font-black tracking-tight">Order {{ $order->reference_no }}</h1>
                <p class="mt-1 text-sm text-slate-500">
                    Placed {{ $order->placed_at?->format('M d, Y h:i A') }} · {{ $order->order_type->label() }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span class="badge badge-{{ $order->payment_status->color() }}">{{ $order->payment_status->label() }}</span>
                <span class="badge badge-{{ $order->status->color() }}">{{ $order->status->label() }}</span>
            </div>
        </div>

        @if ($order->status === \App\Enums\OrderStatus::Cancelled)
            <div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800">
                <p class="font-bold">This order was cancelled.</p>
                @if ($order->cancellation_reason)
                    <p class="mt-1">{{ $order->cancellation_reason }}</p>
                @endif
                <p class="mt-2 text-xs">If you already paid, the store will process your refund. Call us if you need help.</p>
            </div>
        @endif

        {{-- Status timeline --}}
        <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-bold">Order progress</h2>
                <button type="button" onclick="window.location.reload()" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-900">
                    <x-icon name="refresh" class="h-3.5 w-3.5" /> Refresh
                </button>
            </div>

            <ol class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($steps as $index => $step)
                    @php
                        $done = $order->status === \App\Enums\OrderStatus::Completed ? true : ($order->status !== \App\Enums\OrderStatus::Cancelled && $index < $current);
                        $active = $order->status !== \App\Enums\OrderStatus::Cancelled && $index === $current;
                    @endphp
                    <li class="flex items-start gap-3 rounded-xl border px-3.5 py-3 {{ $active ? 'border-amber-300 bg-amber-50' : ($done ? 'border-emerald-200 bg-emerald-50/60' : 'border-slate-200 bg-slate-50/60') }}">
                        <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[11px] font-black
                            {{ $active ? 'bg-amber-400 text-slate-950' : ($done ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-500') }}">
                            @if ($done)
                                <x-icon name="check" class="h-3.5 w-3.5" />
                            @else
                                {{ $index + 1 }}
                            @endif
                        </span>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold {{ $active ? 'text-amber-900' : ($done ? 'text-emerald-900' : 'text-slate-600') }}">{{ $step['label'] }}</span>
                            @if ($step['hint'])
                                <span class="block text-[11px] text-slate-500">{{ $step['hint']->format('M d, h:i A') }}</span>
                            @elseif ($active)
                                <span class="block text-[11px] text-amber-700">Happening now</span>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ol>

            <p class="mt-4 text-xs text-slate-500">
                @if ($order->status === \App\Enums\OrderStatus::Completed)
                    Salamat po! We hope you enjoyed it.
                @else
                    We'll call you when your order is ready{{ $order->isDelivery() ? ', so the rider can pick you up' : '' }}.
                @endif
            </p>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-5">
            <div class="space-y-6 lg:col-span-3">
                {{-- Items --}}
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-5 py-4">
                        <h2 class="font-bold">Your order</h2>
                    </div>
                    <ul class="divide-y divide-slate-100">
                        @foreach ($order->items as $item)
                            <li class="flex items-start justify-between gap-3 px-5 py-3.5">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold"><span class="text-slate-500">{{ $item->qty }}×</span> {{ $item->name_snapshot }}</p>
                                    @if ($item->note)
                                        <p class="mt-0.5 text-[11px] text-amber-700">“{{ $item->note }}”</p>
                                    @endif
                                </div>
                                <span class="shrink-0 text-sm font-semibold">{{ \App\Support\Money::format($item->line_total) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <div class="space-y-1.5 border-t border-slate-100 bg-slate-50 px-5 py-4 text-sm">
                        <div class="flex justify-between text-slate-600">
                            <span>Subtotal</span><span>{{ \App\Support\Money::format($order->subtotal) }}</span>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>Delivery fee</span>
                            <span>{{ $order->isPickup() ? 'Pickup' : ($order->delivery_fee > 0 ? \App\Support\Money::format($order->delivery_fee) : 'Free') }}</span>
                        </div>
                        @if ($order->discount_amount > 0)
                            <div class="flex justify-between text-violet-700">
                                <span>{{ $order->discount_type->label() }}</span>
                                <span>-{{ \App\Support\Money::format($order->discount_amount) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-black">
                            <span>Total</span><span>{{ \App\Support\Money::format($order->total) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Payment --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="font-bold">Payment</h2>
                        <span class="badge badge-{{ $order->payment_status->color() }}">{{ $order->payment_status->label() }}</span>
                    </div>

                    <div class="mt-3 grid gap-2 text-sm text-slate-600 sm:grid-cols-2">
                        <p><span class="text-slate-400">Method:</span> {{ $order->payment_method->label() }}</p>
                        <p><span class="text-slate-400">Amount:</span> <span class="font-semibold text-slate-900">{{ \App\Support\Money::format($order->total) }}</span></p>
                        @if ($order->paid_at)
                            <p><span class="text-slate-400">Paid:</span> {{ $order->paid_at->format('M d, h:i A') }}</p>
                        @endif
                    </div>

                    @if ($order->payment_method->isGcash() && ! $order->isPaid() && ! $order->isFinal())
                        <div class="mt-4 rounded-xl border border-sky-200 bg-sky-50 p-4">
                            <p class="text-sm font-bold text-sky-900">Send your GCash payment</p>
                            @if ($config['gcash_name'] || $config['gcash_number'])
                                <p class="mt-1 text-sm text-sky-800">
                                    {{ $config['gcash_name'] ?: 'Account name' }}
                                    @if ($config['gcash_number'])
                                        · <span class="font-mono font-bold">{{ $config['gcash_number'] }}</span>
                                    @endif
                                </p>
                            @endif
                            <x-gcash-qr :src="$config['gcash_qr_url']" caption="Tap to enlarge" class="mt-3" />

                            @if ($order->payment_proof_path)
                                <a href="{{ route('orders.proof.public', ['token' => $order->public_token]) }}" target="_blank" class="mt-3 inline-block">
                                    <img src="{{ route('orders.proof.public', ['token' => $order->public_token]) }}" alt="Uploaded GCash receipt"
                                        class="h-32 w-auto rounded-lg ring-1 ring-sky-200">
                                </a>
                            @endif

                            <p class="mt-2 text-xs text-sky-700">
                                @if ($order->payment_status === \App\Enums\PaymentStatus::AwaitingVerification)
                                    We received your receipt and will verify it shortly. You can replace the screenshot below if you need to.
                                @else
                                    Paid already? Upload a screenshot of your GCash receipt so we can verify it.
                                @endif
                            </p>

                            <form action="{{ route('orders.payment', ['token' => $order->public_token]) }}" method="POST" enctype="multipart/form-data" class="mt-3 space-y-3">
                                @csrf
                                <div>
                                    <label class="label text-sky-900!">{{ $order->payment_proof_path ? 'Replace receipt screenshot' : 'GCash receipt screenshot' }}</label>
                                    <input name="proof" type="file" accept="image/jpeg,image/png,image/webp" required
                                        class="block w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-white file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-sky-700">
                                    <p class="mt-1 text-[11px] text-sky-700">JPG, PNG or WebP · up to 4 MB.</p>
                                    @error('proof') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm">Upload receipt</button>
                            </form>
                        </div>
                    @elseif ($order->payment_method->isCash() && ! $order->isPaid() && ! $order->isFinal())
                        <p class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900">
                            Pay {{ $order->isDelivery() ? 'the rider when your order arrives' : 'at the counter when you pick up' }}. We will confirm the order by phone first.
                        </p>
                    @endif
                </div>
            </div>

            <aside class="space-y-6 lg:col-span-2">
                {{-- Delivery details --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-bold">{{ $order->isDelivery() ? 'Delivering to' : 'Pickup details' }}</h2>
                    <div class="mt-3 space-y-2 text-sm text-slate-600">
                        <p class="font-semibold text-slate-900">{{ $order->customer_name }}</p>
                        <p>{{ $order->customer_phone }}</p>
                        @if ($order->isDelivery())
                            <p class="leading-relaxed">{{ $order->delivery_address }}</p>
                        @else
                            <p class="leading-relaxed">{{ $config['store_address'] ?: 'Ask us for the counter location.' }}</p>
                        @endif
                        @if ($order->notes)
                            <p class="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-900">“{{ $order->notes }}”</p>
                        @endif
                    </div>
                </div>

                {{-- Store --}}
                <div class="rounded-2xl bg-slate-950 p-5 text-slate-300 shadow-sm">
                    <h2 class="font-bold text-white">{{ $config['store_name'] }}</h2>
                    <div class="mt-3 space-y-1.5 text-sm">
                        @if ($config['store_address']) <p>{{ $config['store_address'] }}</p> @endif
                        @if ($config['store_phone'])
                            <p><a href="tel:{{ $config['store_phone'] }}" class="font-semibold text-amber-300 hover:text-amber-200">{{ $config['store_phone'] }}</a></p>
                        @endif
                        @if ($config['store_hours']) <p class="text-slate-400">{{ $config['store_hours'] }}</p> @endif
                    </div>
                    <a href="{{ route('storefront') }}" class="btn btn-primary mt-4 w-full">Order again</a>
                </div>

                <div class="rounded-2xl border border-dashed border-slate-300 p-4 text-center text-xs text-slate-500">
                    Bookmark this page to follow your order. Do not share the link — it shows your delivery details.
                </div>
            </aside>
        </div>
    </main>
</x-layouts.storefront>
