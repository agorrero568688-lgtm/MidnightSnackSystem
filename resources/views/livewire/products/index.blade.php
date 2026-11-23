<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-2 text-xs">
            <span class="badge badge-slate">{{ $totalActive }} active</span>
            <span class="badge {{ $totalLow > 0 ? 'badge-amber' : 'badge-slate' }}">{{ $totalLow }} low stock</span>
        </div>
        <a href="{{ route('products.create') }}" class="btn btn-primary">
            <x-icon name="plus" class="h-4 w-4" /> New product
        </a>
    </div>

    <div class="card">
        <div class="card-body flex flex-wrap gap-3 border-b border-slate-100">
            <div class="relative min-w-56 flex-1">
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search name, SKU or barcode"
                    class="input pl-9">
            </div>
            <select wire:model.live="category" class="input w-full sm:w-48">
                <option value="all">All categories</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="stock" class="input w-full sm:w-44">
                <option value="all">Any stock level</option>
                <option value="low">Low stock</option>
                <option value="out">Out of stock</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th class="text-right">Cost</th>
                        <th class="text-right">Price</th>
                        <th>Margin</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr wire:key="product-{{ $product->id }}">
                            <td>
                                <p class="font-semibold text-slate-900">{{ $product->name }}</p>
                                <p class="font-mono text-[11px] text-slate-500">
                                    {{ $product->sku }}@if ($product->barcode) · {{ $product->barcode }}@endif
                                </p>
                            </td>
                            <td class="text-slate-600">{{ $product->category?->name ?? '—' }}</td>
                            <td class="text-right text-slate-600">{{ \App\Support\Money::format($product->cost_price) }}</td>
                            <td class="text-right font-semibold">{{ \App\Support\Money::format($product->selling_price) }}</td>
                            <td class="text-xs">
                                @php
                                    $cost = \App\Support\Money::cents($product->cost_price);
                                    $price = \App\Support\Money::cents($product->selling_price);
                                    $margin = $price > 0 ? (($price - $cost) / $price) * 100 : 0;
                                @endphp
                                <span class="{{ $margin >= 20 ? 'text-emerald-600' : 'text-slate-500' }}">{{ number_format($margin, 1) }}%</span>
                            </td>
                            <td>
                                @if (! $product->track_stock)
                                    <span class="badge badge-slate">Unlimited</span>
                                @elseif ($product->stock_qty <= 0)
                                    <span class="badge badge-red">Out</span>
                                @elseif ($product->isLowStock())
                                    <span class="badge badge-amber">{{ $product->stock_qty }}</span>
                                @else
                                    <span class="badge badge-green">{{ $product->stock_qty }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($product->is_active)
                                    <span class="badge badge-green">Active</span>
                                @else
                                    <span class="badge badge-slate">Hidden</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-ghost">Edit</a>
                                    <button wire:click="toggleActive({{ $product->id }})" class="btn btn-sm btn-ghost">
                                        {{ $product->is_active ? 'Hide' : 'Show' }}
                                    </button>
                                    <button wire:click="delete({{ $product->id }})"
                                        wire:confirm="Delete {{ $product->name }}? Past sales keep their snapshot."
                                        class="btn btn-sm text-rose-600 hover:bg-rose-50">Delete</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="empty-state">
                                No products found.
                                <a href="{{ route('products.create') }}" class="font-semibold text-amber-600">Add your first product →</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($products->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $products->links() }}</div>
        @endif
    </div>
</div>
