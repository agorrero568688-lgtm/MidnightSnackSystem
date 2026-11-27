<div class="space-y-5">
    {{-- Stat cards --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div class="card card-body">
            <div class="flex items-start justify-between gap-3">
                <p class="text-xs font-semibold tracking-wider text-ink-500 uppercase">Sales today</p>
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-100 text-brand-700">
                    <x-icon name="banknote" class="h-4 w-4" />
                </span>
            </div>
            <p class="mt-1 text-3xl font-black text-ink-950">{{ $todayTotal }}</p>
            <p class="mt-1 text-xs text-ink-500">{{ $todayCount }} transaction{{ $todayCount === 1 ? '' : 's' }}</p>
        </div>
        <div class="card card-body">
            <div class="flex items-start justify-between gap-3">
                <p class="text-xs font-semibold tracking-wider text-ink-500 uppercase">Items sold</p>
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-ink-100 text-ink-600">
                    <x-icon name="cube" class="h-4 w-4" />
                </span>
            </div>
            <p class="mt-1 text-3xl font-black text-ink-950">{{ number_format($itemsSold) }}</p>
            <p class="mt-1 text-xs text-ink-500">Across all completed sales</p>
        </div>
        <div class="card card-body">
            <div class="flex items-start justify-between gap-3">
                <p class="text-xs font-semibold tracking-wider text-ink-500 uppercase">Discounts given</p>
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-100 text-violet-700">
                    <x-icon name="tag" class="h-4 w-4" />
                </span>
            </div>
            <p class="mt-1 text-3xl font-black text-ink-950">{{ $todayDiscounts }}</p>
            <p class="mt-1 text-xs text-ink-500">Senior &amp; PWD</p>
        </div>
    </div>

    <div class="grid gap-5 xl:grid-cols-3">
        {{-- Recent sales --}}
        <div class="card xl:col-span-2">
            <div class="card-header">
                <h2 class="text-sm font-bold">Recent sales</h2>
                <a href="{{ route('sales.index') }}" class="text-xs font-semibold text-brand-700 hover:text-brand-900">View all →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="data-table responsive-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Cashier</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentSales as $sale)
                            <tr>
                                <td data-label="Reference" class="font-mono text-xs font-semibold">{{ $sale->reference_no }}</td>
                                <td data-label="Cashier">{{ $sale->user?->name }}</td>
                                <td data-label="Items">{{ (int) $sale->items_sum_qty }}</td>
                                <td data-label="Total" class="font-semibold">{{ \App\Support\Money::format($sale->total) }}</td>
                                <td data-label="Status">
                                    @if ($sale->isVoided())
                                        <span class="badge badge-red">Voided</span>
                                    @else
                                        <span class="badge badge-green">Completed</span>
                                    @endif
                                </td>
                                <td data-label="Time" class="text-xs text-ink-500">{{ $sale->created_at->format('M d, h:i A') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="empty-state">No sales yet. Open Cashier to start.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Top products --}}
        <div class="card">
            <div class="card-header">
                <h2 class="text-sm font-bold">Top sellers today</h2>
            </div>
            <ul class="divide-y divide-ink-100">
                @forelse ($topProducts as $index => $product)
                    <li class="flex items-center gap-3 px-5 py-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-ink-100 text-xs font-bold text-ink-500">{{ $index + 1 }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ $product->name_snapshot }}</p>
                            <p class="text-xs text-ink-500">{{ (int) $product->qty }} sold</p>
                        </div>
                        <span class="text-sm font-semibold">{{ \App\Support\Money::format($product->total) }}</span>
                    </li>
                @empty
                    <li class="empty-state">Nothing sold yet today.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
