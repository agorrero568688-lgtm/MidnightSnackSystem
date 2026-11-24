<div class="space-y-5">
    {{-- Stat cards --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card card-body">
            <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Sales today</p>
            <p class="mt-1 text-3xl font-black text-slate-900">{{ $todayTotal }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $todayCount }} transaction{{ $todayCount === 1 ? '' : 's' }}</p>
        </div>
        <div class="card card-body">
            <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Items sold</p>
            <p class="mt-1 text-3xl font-black text-slate-900">{{ number_format($itemsSold) }}</p>
            <p class="mt-1 text-xs text-slate-500">Across all completed sales</p>
        </div>
        <div class="card card-body">
            <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Discounts given</p>
            <p class="mt-1 text-3xl font-black text-slate-900">{{ $todayDiscounts }}</p>
            <p class="mt-1 text-xs text-slate-500">Senior &amp; PWD</p>
        </div>
        <a href="{{ route('inventory.index') }}" class="card card-body transition hover:border-amber-300 hover:shadow">
            <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Low stock</p>
            <p class="mt-1 text-3xl font-black {{ $lowStockCount > 0 ? 'text-rose-600' : 'text-slate-900' }}">{{ $lowStockCount }}</p>
            <p class="mt-1 text-xs text-slate-500">Ingredients at or below reorder level</p>
        </a>
    </div>

    <div class="grid gap-5 xl:grid-cols-3">
        {{-- Recent sales --}}
        <div class="card xl:col-span-2">
            <div class="card-header">
                <h2 class="text-sm font-bold">Recent sales</h2>
                <a href="{{ route('sales.index') }}" class="text-xs font-semibold text-amber-600 hover:text-amber-700">View all →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="data-table">
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
                                <td class="font-mono text-xs font-semibold">{{ $sale->reference_no }}</td>
                                <td>{{ $sale->user?->name }}</td>
                                <td>{{ (int) $sale->items_sum_qty }}</td>
                                <td class="font-semibold">{{ \App\Support\Money::format($sale->total) }}</td>
                                <td>
                                    @if ($sale->isVoided())
                                        <span class="badge badge-red">Voided</span>
                                    @else
                                        <span class="badge badge-green">Completed</span>
                                    @endif
                                </td>
                                <td class="text-xs text-slate-500">{{ $sale->created_at->format('M d, h:i A') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="empty-state">No sales yet. Open the till to start.</td></tr>
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
            <ul class="divide-y divide-slate-100">
                @forelse ($topProducts as $index => $product)
                    <li class="flex items-center gap-3 px-5 py-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-xs font-bold text-slate-500">{{ $index + 1 }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ $product->name_snapshot }}</p>
                            <p class="text-xs text-slate-500">{{ (int) $product->qty }} sold</p>
                        </div>
                        <span class="text-sm font-semibold">{{ \App\Support\Money::format($product->total) }}</span>
                    </li>
                @empty
                    <li class="empty-state">Nothing sold yet today.</li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- Low stock --}}
    <div class="card">
        <div class="card-header">
            <h2 class="text-sm font-bold">Needs restocking</h2>
            <a href="{{ route('inventory.index') }}" class="text-xs font-semibold text-amber-600 hover:text-amber-700">Manage inventory →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr><th>Ingredient</th><th>Category</th><th>Quantity</th><th>Reorder level</th><th>Cost value</th></tr>
                </thead>
                <tbody>
                    @forelse ($lowStock as $inventory)
                        <tr>
                            <td class="font-medium">{{ $inventory->name }}</td>
                            <td class="text-slate-500">{{ $inventory->category }}</td>
                            <td>
                                <span class="badge {{ $inventory->isOutOfStock() ? 'badge-red' : 'badge-amber' }}">{{ $inventory->quantityLabel() }} {{ $inventory->unit }}</span>
                            </td>
                            <td class="text-slate-500">{{ $inventory->reorderLabel() }} {{ $inventory->unit }}</td>
                            <td>{{ \App\Support\Money::formatCents($inventory->stockValueCents()) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-state">Everything is above its reorder level.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
