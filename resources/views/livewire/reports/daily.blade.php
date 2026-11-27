<div class="space-y-5">
    <div class="card card-body flex flex-wrap items-end justify-between gap-3">
        <div>
            <label class="label">Report date</label>
            <input wire:model.live="date" type="date" class="input w-44">
        </div>
        <div class="flex gap-2">
            <a href="{{ route('reports.daily.export', ['date' => $day]) }}" class="btn btn-secondary">
                <x-icon name="download" class="h-4 w-4" /> Export CSV
            </a>
            <a href="{{ route('pos') }}" class="btn btn-primary">
                <x-icon name="cart" class="h-4 w-4" /> Open Cashier
            </a>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="card card-body">
            <p class="text-xs font-semibold tracking-wider text-ink-500 uppercase">Net sales</p>
            <p class="mt-1 text-2xl font-black">{{ $total }}</p>
        </div>
        <div class="card card-body">
            <p class="text-xs font-semibold tracking-wider text-ink-500 uppercase">Transactions</p>
            <p class="mt-1 text-2xl font-black">{{ number_format($transactions) }}</p>
        </div>
        <div class="card card-body">
            <p class="text-xs font-semibold tracking-wider text-ink-500 uppercase">Items sold</p>
            <p class="mt-1 text-2xl font-black">{{ number_format($itemsSold) }}</p>
        </div>
        <div class="card card-body">
            <p class="text-xs font-semibold tracking-wider text-ink-500 uppercase">Discounts</p>
            <p class="mt-1 text-2xl font-black">{{ $discounts }}</p>
        </div>
        <div class="card card-body">
            <p class="text-xs font-semibold tracking-wider text-ink-500 uppercase">Cash collected</p>
            <p class="mt-1 text-2xl font-black">{{ $cashSales }}</p>
        </div>
    </div>

    <div class="grid gap-5 xl:grid-cols-2">
        <div class="card">
            <div class="card-header">
                <h2 class="text-sm font-bold">Payment breakdown</h2>
                @if ($voidedCount > 0)
                    <span class="badge badge-red">{{ $voidedCount }} voided</span>
                @endif
            </div>
            <table class="data-table responsive-table">
                <thead>
                    <tr><th>Method</th><th class="text-right">Transactions</th><th class="text-right">Amount</th></tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td data-label="Method" class="font-medium">{{ $payment->payment_method->label() }}</td>
                            <td data-label="Transactions" class="text-right">{{ (int) $payment->count }}</td>
                            <td data-label="Amount" class="text-right font-semibold">{{ \App\Support\Money::format($payment->total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="empty-state">No completed sales on this day.</td></tr>
                    @endforelse
                </tbody>
                @if ($payments->isNotEmpty())
                    <tfoot class="bg-ink-50 text-sm font-bold">
                        <tr>
                            <td data-label="Total" class="px-4 py-3">Total</td>
                            <td data-label="Transactions" class="px-4 py-3 text-right">{{ number_format($transactions) }}</td>
                            <td data-label="Amount" class="px-4 py-3 text-right">{{ $total }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="text-sm font-bold">Top products</h2>
                <span class="text-xs text-ink-500">Gross before discounts: {{ $subtotal }}</span>
            </div>
            <div class="max-h-96 overflow-y-auto">
                <table class="data-table responsive-table">
                    <thead>
                        <tr><th>Product</th><th class="text-right">Qty</th><th class="text-right">Sales</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($topProducts as $product)
                            <tr>
                                <td data-label="Product" class="font-medium">{{ $product->name_snapshot }}</td>
                                <td data-label="Qty" class="text-right">{{ (int) $product->qty }}</td>
                                <td data-label="Sales" class="text-right">{{ \App\Support\Money::format($product->total) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="empty-state">Nothing sold on this day.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h2 class="text-sm font-bold">Shifts opened this day</h2></div>
        <table class="data-table responsive-table">
            <thead>
                <tr><th>Cashier</th><th class="hidden sm:table-cell">Opened</th><th class="hidden sm:table-cell">Closed</th><th class="text-right">Opening</th><th class="text-right">Expected</th><th class="text-right">Counted</th><th class="text-right">Variance</th></tr>
            </thead>
            <tbody>
                @forelse ($shifts as $shift)
                    <tr>
                        <td data-label="Cashier" class="font-medium">{{ $shift->user?->name }}</td>
                        <td data-label="Opened" class="hidden text-xs text-ink-600 sm:table-cell">{{ $shift->opened_at->format('h:i A') }}</td>
                        <td data-label="Closed" class="hidden text-xs text-ink-600 sm:table-cell">{{ $shift->closed_at?->format('h:i A') ?? 'Open' }}</td>
                        <td data-label="Opening" class="text-right">{{ \App\Support\Money::format($shift->opening_cash) }}</td>
                        <td data-label="Expected" class="text-right">{{ $shift->expected_cash !== null ? \App\Support\Money::format($shift->expected_cash) : '—' }}</td>
                        <td data-label="Counted" class="text-right">{{ $shift->closing_cash !== null ? \App\Support\Money::format($shift->closing_cash) : '—' }}</td>
                        <td data-label="Variance" class="text-right font-semibold {{ $shift->variance !== null && \App\Support\Money::cents($shift->variance) < 0 ? 'text-rose-600' : '' }}">
                            {{ $shift->variance !== null ? \App\Support\Money::format($shift->variance) : '—' }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty-state">No shifts opened on this day.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
