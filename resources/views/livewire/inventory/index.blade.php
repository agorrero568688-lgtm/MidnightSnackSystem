<div class="space-y-5">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card card-body">
            <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Tracked products</p>
            <p class="mt-1 text-3xl font-black">{{ $trackedCount }}</p>
        </div>
        <div class="card card-body">
            <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Low stock</p>
            <p class="mt-1 text-3xl font-black {{ $lowCount > 0 ? 'text-amber-600' : '' }}">{{ $lowCount }}</p>
        </div>
        <div class="card card-body">
            <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Out of stock</p>
            <p class="mt-1 text-3xl font-black {{ $outCount > 0 ? 'text-rose-600' : '' }}">{{ $outCount }}</p>
        </div>
        <div class="card card-body">
            <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Stock value (cost)</p>
            <p class="mt-1 text-3xl font-black">{{ \App\Support\Money::formatCents($stockValue) }}</p>
        </div>
    </div>

    <div class="grid gap-5 xl:grid-cols-3">
        <div class="card h-fit">
            <div class="card-header">
                <h2 class="text-sm font-bold">Record a movement</h2>
            </div>
            <form wire:submit="saveMovement" class="card-body space-y-4">
                <div>
                    <label class="label">Product</label>
                    <select wire:model.live="productId" class="input">
                        <option value="">Choose a product…</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} · {{ $product->sku }}</option>
                        @endforeach
                    </select>
                    @error('productId') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label">Movement</label>
                    <div class="grid grid-cols-3 gap-1.5">
                        @foreach (['in' => 'Stock in', 'adjust' => 'Set count', 'waste' => 'Wastage'] as $value => $label)
                            <button type="button" wire:click="$set('mode', '{{ $value }}')"
                                class="rounded-lg px-2 py-2 text-xs font-semibold transition {{ $mode === $value ? 'bg-amber-400 text-slate-950' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="label">{{ $mode === 'adjust' ? 'New stock count' : 'Quantity' }}</label>
                    <input wire:model="qty" type="number" min="0" class="input" placeholder="0">
                    @error('qty') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    @if ($selectedProduct)
                        <p class="mt-1 text-[11px] text-slate-500">
                            Current: <span class="font-semibold text-slate-700">{{ $selectedProduct->name }}</span>
                            · {{ $selectedProduct->stock_qty }} {{ $selectedProduct->unit }}
                        </p>
                    @endif
                </div>

                <div>
                    <label class="label">Note <span class="font-normal normal-case text-slate-400">(optional)</span></label>
                    <input wire:model="note" type="text" class="input" placeholder="Supplier, invoice no., reason…">
                    @error('note') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="btn btn-primary w-full" wire:loading.attr="disabled">
                    <span wire:loading.remove>Save movement</span>
                    <span wire:loading>Saving…</span>
                </button>
            </form>
        </div>

        <div class="card xl:col-span-2">
            <div class="card-header">
                <div class="flex gap-1 rounded-xl bg-slate-100 p-1">
                    <button wire:click="$set('tab', 'levels')"
                        class="rounded-lg px-3 py-1.5 text-xs font-semibold {{ $tab === 'levels' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500' }}">Stock levels</button>
                    <button wire:click="$set('tab', 'log')"
                        class="rounded-lg px-3 py-1.5 text-xs font-semibold {{ $tab === 'log' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500' }}">Movement log</button>
                </div>

                @if ($tab === 'levels')
                    <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search products" class="input input-sm w-48">
                @else
                    <select wire:model.live="logType" class="input input-sm w-40">
                        <option value="all">All movements</option>
                        @foreach (\App\Enums\StockMovementType::cases() as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                @endif
            </div>

            <div class="overflow-x-auto">
                @if ($tab === 'levels')
                    <table class="data-table">
                        <thead>
                            <tr><th>Product</th><th>Category</th><th>Stock</th><th>Value</th><th>Last movement</th><th></th></tr>
                        </thead>
                        <tbody>
                            @forelse ($levels as $product)
                                <tr wire:key="level-{{ $product->id }}">
                                    <td>
                                        <p class="font-semibold">{{ $product->name }}</p>
                                        <p class="font-mono text-[11px] text-slate-500">{{ $product->sku }}</p>
                                    </td>
                                    <td class="text-slate-600">{{ $product->category?->name ?? '—' }}</td>
                                    <td>
                                        @if (! $product->track_stock)
                                            <span class="badge badge-slate">Unlimited</span>
                                        @elseif ($product->stock_qty <= 0)
                                            <span class="badge badge-red">Out</span>
                                        @elseif ($product->isLowStock())
                                            <span class="badge badge-amber">{{ $product->stock_qty }} {{ $product->unit }}</span>
                                        @else
                                            <span class="badge badge-green">{{ $product->stock_qty }} {{ $product->unit }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ \App\Support\Money::formatCents(\App\Support\Money::cents($product->cost_price) * max(0, $product->stock_qty)) }}
                                    </td>
                                    <td class="text-xs text-slate-500">
                                        {{ $product->movements_max_created_at ? \Illuminate\Support\Carbon::parse($product->movements_max_created_at)->diffForHumans() : 'Never' }}
                                    </td>
                                    <td class="text-right">
                                        <button wire:click="selectProduct({{ $product->id }}, 'in')" class="btn btn-sm btn-ghost">Restock</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="empty-state">No products match.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                @else
                    <table class="data-table">
                        <thead>
                            <tr><th>When</th><th>Product</th><th>Type</th><th class="text-right">Change</th><th class="text-right">After</th><th>By</th><th>Note</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($movements as $movement)
                                <tr wire:key="movement-{{ $movement->id }}">
                                    <td class="text-xs whitespace-nowrap text-slate-500">{{ $movement->created_at->format('M d, h:i A') }}</td>
                                    <td class="font-medium">{{ $movement->product?->name ?? 'Deleted product' }}</td>
                                    <td><span class="badge badge-{{ $movement->type->color() }}">{{ $movement->type->label() }}</span></td>
                                    <td class="text-right font-semibold {{ $movement->qty_change >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                        {{ $movement->qty_change > 0 ? '+' : '' }}{{ $movement->qty_change }}
                                    </td>
                                    <td class="text-right text-slate-600">{{ $movement->qty_after }}</td>
                                    <td class="text-xs text-slate-500">{{ $movement->user?->name ?? 'System' }}</td>
                                    <td class="max-w-48 truncate text-xs text-slate-500">{{ $movement->note ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="empty-state">No stock movements recorded yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>
