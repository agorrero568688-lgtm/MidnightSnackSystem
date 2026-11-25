<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-2 text-xs">
            <span class="badge badge-slate">{{ $totalActive }} active</span>
            <span class="badge {{ $totalUnavailable > 0 ? 'badge-amber' : 'badge-slate' }}">{{ $totalUnavailable }} sold out</span>
        </div>
        <a href="{{ route('products.create') }}" class="btn btn-primary">
            <x-icon name="plus" class="h-4 w-4" /> New product
        </a>
    </div>

    <div class="card">
        <div class="card-body flex flex-wrap gap-3 border-b border-ink-100">
            <div class="relative min-w-56 flex-1">
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-ink-400" />
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search name, SKU or barcode"
                    class="input pl-9">
            </div>
            <select wire:model.live="category" class="input w-full sm:w-48">
                <option value="all">All categories</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="availability" class="input w-full sm:w-44">
                <option value="all">Any availability</option>
                <option value="available">Available</option>
                <option value="unavailable">Sold out</option>
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
                        <th>Availability</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr wire:key="product-{{ $product->id }}">
                            <td>
                                <p class="font-semibold text-ink-900">{{ $product->name }}</p>
                                <p class="font-mono text-[11px] text-ink-500">
                                    {{ $product->sku }}@if ($product->barcode) · {{ $product->barcode }}@endif
                                </p>
                            </td>
                            <td class="text-ink-600">{{ $product->category?->name ?? '—' }}</td>
                            <td class="text-right text-ink-600">{{ \App\Support\Money::format($product->cost_price) }}</td>
                            <td class="text-right font-semibold">{{ \App\Support\Money::format($product->selling_price) }}</td>
                            <td class="text-xs">
                                @php
                                    $cost = \App\Support\Money::cents($product->cost_price);
                                    $price = \App\Support\Money::cents($product->selling_price);
                                    $margin = $cost > 0 && $price > 0 ? (($price - $cost) / $price) * 100 : null;
                                @endphp
                                @if ($margin === null)
                                    <span class="text-ink-400">—</span>
                                @else
                                    <span class="{{ $margin >= 20 ? 'text-emerald-600' : 'text-ink-500' }}">{{ number_format($margin, 1) }}%</span>
                                @endif
                            </td>
                            <td>
                                @if ($product->is_available)
                                    <span class="badge badge-green">Available</span>
                                @else
                                    <span class="badge badge-red">Sold out</span>
                                @endif
                            </td>
                            <td>
                                @if ($product->is_active)
                                    <span class="badge badge-green">Active</span>
                                @else
                                    <span class="badge badge-slate">Hidden</span>
                                @endif
                                @if ($product->is_online)
                                    <span class="badge badge-sky">Online</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-ghost">Edit</a>
                                    <button wire:click="toggleAvailable({{ $product->id }})" class="btn btn-sm btn-ghost">
                                        {{ $product->is_available ? 'Sold out' : 'Mark available' }}
                                    </button>
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
                                <a href="{{ route('products.create') }}" class="font-semibold text-brand-700">Add your first product →</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($products->hasPages())
            <div class="border-t border-ink-100 px-4 py-3">{{ $products->links() }}</div>
        @endif
    </div>
</div>
